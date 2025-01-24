<?php

namespace Vanguard\Presenters\Traits;

use Exception;
use Vanguard\Presenters\Presenter;

trait Presentable
{
    protected ?Presenter $presenterInstance = null;

    /**
     * @throws Exception
     */
    public function present(): Presenter
    {
        if (is_object($this->presenterInstance)) {
            return $this->presenterInstance;
        }

        if (property_exists($this, 'presenter') and class_exists($this->presenter)) {
            return $this->presenterInstance = new $this->presenter($this);
        }

        throw new Exception('Property $presenter was not set correctly in '.get_class($this));
    }
}
