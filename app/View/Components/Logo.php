<?php

namespace Vanguard\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Logo extends Component
{
    private const VARIANT_DEFAULT = 'default';
    private const VARIANT_NO_TEXT = 'no-text';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $src = '',
        public string $class = '',
        public string $height = '50',
        public string $variant = self::VARIANT_DEFAULT,
    )
    {
        if ($variant === self::VARIANT_DEFAULT) {
            $this->src = url('assets/img/logo.png');
        } else {
            $this->src = url('assets/img/logo-no-text.png');
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.logo', [

        ]);
    }
}
