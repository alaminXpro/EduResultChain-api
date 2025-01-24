<?php

namespace Vanguard\Presenters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class Presenter
{
    public function __construct(protected Model $model)
    {
    }

    public function __isset($property): bool
    {
        return method_exists($this, Str::camel($property));
    }

    public function __get($property): mixed
    {
        $camel_property = Str::camel($property);

        if (method_exists($this, $camel_property)) {
            return $this->{$camel_property}();
        }

        return $this->model->{Str::snake($property)};
    }
}
