## EduResultChain-api

EduResultChain-api is the backend API for the EduResultChain project, built using Laravel. This API provides endpoints for managing educational results and related data.

### Main Repository

For more information, visit the main repository: [EduResultChain](https://github.com/alaminXpro/EduResultChain)

### Prerequisites

- Docker
- Docker Compose
- PHP 8.2
- Composer

### Setup Instructions

1. Clone the repository:
    ```sh
    git clone https://github.com/alaminXpro/EduResultChain-api.git
    cd EduResultChain-api
    ```

2. Install dependencies:
    ```sh
    composer install
    ```

3. Copy the `.env.example` file to `.env` and configure your environment variables:
    ```sh
    cp .env.example .env
    ```

4. Generate the application key:
    ```sh
    php artisan key:generate
    ```

5. Run the database migrations:
    ```sh
    php artisan migrate
    ```

6. Seed the database:
    ```sh
    php artisan db:seed
    ```

### Running the Application

#### Using Laravel Sail (Docker)

1. Start the application using Laravel Sail:
    ```sh
    ./vendor/bin/sail up
    ```

2. Access the application at `http://localhost`.

#### Using PHP Artisan

1. Start the application using PHP Artisan:
    ```sh
    php artisan serve
    ```

2. Access the application at `http://localhost:8000`.

### Contact

For any inquiries or issues, please contact the author via [LinkedIn](https://www.linkedin.com/in/alaminxpro/).
