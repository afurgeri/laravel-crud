<?php

namespace Modules\Crud\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use InvalidArgumentException;

class InvalidCrudFilterRange extends InvalidArgumentException
{
    public static function forGroup(string $group): self
    {
        return new self("The [{$group}] filter range is invalid: the lower bound is after the upper bound.");
    }

    public function render(Request $request): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => __('The selected range is invalid: the starting value cannot be after the ending value.')]);

        return redirect()->back();
    }
}
