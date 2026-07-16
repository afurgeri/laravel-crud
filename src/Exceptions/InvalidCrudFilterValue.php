<?php

namespace Modules\Crud\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use InvalidArgumentException;

class InvalidCrudFilterValue extends InvalidArgumentException
{
    public static function exceedsMaximumDate(string $filterName, string $maxDate): self
    {
        return new self("The [{$filterName}] filter value cannot be after the maximum allowed date [{$maxDate}].");
    }

    public function render(Request $request): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => __('The selected date is not allowed: it cannot be later than the maximum permitted date.')]);

        return redirect()->back();
    }
}
