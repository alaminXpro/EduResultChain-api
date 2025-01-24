<?php

namespace Vanguard\Http\Controllers\Web;

use Artisan;
use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Vanguard\Http\Controllers\Controller;

class InstallController extends Controller
{
    public const TMP_APP_KEY = 'lsXxrP1d6nUGpfGO6vQ1ezNy1KCuDD3o';

    public function index(): View
    {
        return view('install.start');
    }

    public function requirements(): View
    {
        $requirements = $this->getRequirements();
        $allLoaded = $this->allRequirementsLoaded();

        return view('install.requirements', compact('requirements', 'allLoaded'));
    }

    public function permissions(): View|RedirectResponse
    {
        if (! $this->allRequirementsLoaded()) {
            return redirect()->route('install.requirements');
        }

        $folders = $this->getPermissions();
        $allGranted = $this->allPermissionsGranted();

        return view('install.permissions', compact('folders', 'allGranted'));
    }

    public function databaseInfo(): View|RedirectResponse
    {
        if (! $this->allRequirementsLoaded()) {
            return redirect()->route('install.requirements');
        }

        if (! $this->allPermissionsGranted()) {
            return redirect()->route('install.permissions');
        }

        return view('install.database');
    }

    public function installation(Request $request): View|RedirectResponse
    {
        if (! $this->allRequirementsLoaded()) {
            return redirect()->route('install.requirements');
        }

        if (! $this->allPermissionsGranted()) {
            return redirect()->route('install.permissions');
        }

        $dbCredentials = $request->only('host', 'username', 'password', 'database', 'prefix');

        if (! $this->dbCredentialsAreValid($dbCredentials)) {
            return redirect()->route('install.database')
                ->withInput(Arr::except($dbCredentials, 'password'))
                ->withErrors('Connection to your database cannot be established.
                Please provide correct database credentials.');
        }

        if (! $this->foreignKeysAreEnabled()) {
            return redirect()->route('install.database')
                ->withInput(Arr::except($dbCredentials, 'password'))
                ->withErrors('Database connection established but foreign keys are not enabled.
                Please enable foreign key support to proceed.');
        }

        Session::put('install.db_credentials', $dbCredentials);

        return view('install.installation');
    }

    public function install(): RedirectResponse
    {
        try {
            $db = Session::pull('install.db_credentials');
            $appKey = $this->generateRandomKey();

            $content = <<<PHP
            APP_ENV=production
            APP_DEBUG=false
            APP_KEY={$appKey}
            APP_URL=http://vanguard.test

            LOG_CHANNEL=stack

            DB_CONNECTION=mysql
            DB_HOST="{$db['host']}"
            DB_DATABASE="{$db['database']}"
            DB_USERNAME="{$db['username']}"
            DB_PASSWORD="{$db['password']}"
            DB_PREFIX="{$db['prefix']}"

            BROADCAST_DRIVER=log
            CACHE_DRIVER=file
            QUEUE_DRIVER=sync
            SESSION_DRIVER=database
            SESSION_LIFETIME=120

            REDIS_HOST=127.0.0.1
            REDIS_PASSWORD=null
            REDIS_PORT=6379

            MAIL_MAILER=mail
            MAIL_FROM_NAME=Vanguard
            MAIL_FROM_ADDRESS=vanguard@test.dev
            MAIL_HOST=smtp.mailtrap.io
            MAIL_PORT=2525
            MAIL_USERNAME=null
            MAIL_PASSWORD=null
            MAIL_ENCRYPTION=null

            PUSHER_APP_ID=
            PUSHER_APP_KEY=
            PUSHER_APP_SECRET=
            PUSHER_APP_CLUSTER=mt1

            MIX_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
            MIX_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"
            PHP;

            file_put_contents(base_path('.env'), $content);

            $this->setDatabaseCredentials($db);
            config(['app.debug' => true]);

            \Setting::set('app_name', \request('app_name'));
            \Setting::save();

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
            Artisan::call('db:seed', [
                '--force' => true,
                '--quiet' => true,
                '--class' => '\\Vanguard\\Announcements\\Database\\Seeders\\AnnouncementsDatabaseSeeder',
            ]);

            return redirect()->route('install.complete');
        } catch (\Exception $e) {
            @unlink(base_path('.env'));
            \Log::error($e->getMessage());
            \Log::error($e->getTraceAsString());

            return redirect()->route('install.error');
        }
    }

    public function complete(): View
    {
        return view('install.complete');
    }

    public function error(): View
    {
        return view('install.error');
    }

    private function getRequirements(): array
    {
        $requirements = [
            'PHP Version (>= 8.2.0)' => version_compare(phpversion(), '8.2.0', '>='),
            'BCMath Extension' => extension_loaded('bcmath'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'PDO Extension' => extension_loaded('PDO'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'XML Extension' => extension_loaded('xml'),
            'Ctype PHP Extension' => extension_loaded('ctype'),
            'JSON PHP Extension' => extension_loaded('json'),
            'GD Extension' => extension_loaded('gd'),
            'Fileinfo Extension' => extension_loaded('fileinfo'),
        ];

        if (extension_loaded('xdebug')) {
            $requirements['Xdebug Max Nesting Level (>= 500)'] = (int) ini_get('xdebug.max_nesting_level') >= 500;
        }

        return $requirements;
    }

    private function allRequirementsLoaded(): bool
    {
        foreach ($this->getRequirements() as $loaded) {
            if (! $loaded) {
                return false;
            }
        }

        return true;
    }

    private function getPermissions(): array
    {
        return [
            'public/upload/users' => is_writable(public_path('upload/users')),
            'storage/app' => is_writable(storage_path('app')),
            'storage/framework/cache' => is_writable(storage_path('framework/cache')),
            'storage/framework/sessions' => is_writable(storage_path('framework/sessions')),
            'storage/framework/views' => is_writable(storage_path('framework/views')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            'Base Directory' => is_writable(base_path('')),
        ];
    }

    private function allPermissionsGranted(): bool
    {
        foreach ($this->getPermissions() as $permission => $granted) {
            if (! $granted) {
                return false;
            }
        }

        return true;
    }

    private function dbCredentialsAreValid($credentials): bool
    {
        $this->setDatabaseCredentials($credentials);

        try {
            DB::statement('SHOW FULL TABLES');
        } catch (\Exception $e) {
            \Log::info($e->getMessage());

            return false;
        }

        return true;
    }

    private function foreignKeysAreEnabled(): bool
    {
        try {
            $result = DB::select('SELECT @@foreign_key_checks;');

            return data_get($result, '0.@@foreign_key_checks') === 1;
        } catch (\Exception $e) {
            \Log::info($e->getMessage());

            return false;
        }
    }

    private function setDatabaseCredentials($credentials): void
    {
        $default = config('database.default');

        config([
            "database.connections.{$default}.host" => $credentials['host'],
            "database.connections.{$default}.database" => $credentials['database'],
            "database.connections.{$default}.username" => $credentials['username'],
            "database.connections.{$default}.password" => $credentials['password'],
            "database.connections.{$default}.prefix" => $credentials['prefix'],
        ]);
    }

    protected function generateRandomKey(): string
    {
        return 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
    }
}
