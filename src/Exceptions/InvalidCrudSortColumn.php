<?php

namespace Modules\Crud\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use InvalidArgumentException;

class InvalidCrudSortColumn extends InvalidArgumentException
{
    public static function forColumn(string $column): self
    {
        return new self("The [{$column}] column is not sortable for this CRUD definition.");
    }

    public function render(Request $request): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => __('That column cannot be used to sort this list.')]);

        return redirect()->back();
    }
}
