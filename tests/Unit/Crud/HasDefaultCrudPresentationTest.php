<?php

use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Concerns\HasDefaultCrudPresentation;
use Modules\Crud\Contracts\HasCrudPresentation;
use Modules\Crud\Contracts\HasDefaultCrudPageSize;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudFormMode;
use Modules\Crud\CrudLayoutWidth;

test('it registers the package presentation defaults', function () {
    expect(config('crud.default_page_size'))->toBe(10)
        ->and(config('crud.default_form_mode'))->toBe('page')
        ->and(config('crud.default_page_width'))->toBe('standard')
        ->and(config('crud.default_form_width'))->toBe('standard');
});

test('it resolves presentation defaults from configuration', function () {
    config([
        'crud.default_page_size' => 25,
        'crud.default_form_mode' => 'dialog',
        'crud.default_page_width' => 'full',
        'crud.default_form_width' => 'wide',
    ]);

    $definition = new class implements CrudDefinition, HasCrudPresentation, HasDefaultCrudPageSize
    {
        use HasDefaultCrudPresentation;

        public function model(): string
        {
            return Model::class;
        }

        public function title(): string
        {
            return 'Products';
        }

        public function description(): ?string
        {
            return null;
        }

        public function emptyLabel(): ?string
        {
            return null;
        }

        public function columns(): array
        {
            return [];
        }

        public function fields(): array
        {
            return [];
        }
    };

    expect($definition->formMode())->toBe(CrudFormMode::Dialog)
        ->and($definition->pageWidth())->toBe(CrudLayoutWidth::Full)
        ->and($definition->formWidth())->toBe(CrudLayoutWidth::Wide)
        ->and($definition->defaultPageSize())->toBe(25);
});

test('it allows local presentation overrides', function () {
    config([
        'crud.default_page_size' => 25,
        'crud.default_form_mode' => 'dialog',
        'crud.default_page_width' => 'full',
        'crud.default_form_width' => 'wide',
    ]);

    $definition = new class implements CrudDefinition, HasCrudPresentation, HasDefaultCrudPageSize
    {
        use HasDefaultCrudPresentation;

        public function model(): string
        {
            return Model::class;
        }

        public function title(): string
        {
            return 'Products';
        }

        public function description(): ?string
        {
            return null;
        }

        public function emptyLabel(): ?string
        {
            return null;
        }

        public function columns(): array
        {
            return [];
        }

        public function fields(): array
        {
            return [];
        }

        public function formMode(): CrudFormMode
        {
            return CrudFormMode::Page;
        }

        public function pageWidth(): CrudLayoutWidth
        {
            return CrudLayoutWidth::Standard;
        }

        public function formWidth(): CrudLayoutWidth
        {
            return CrudLayoutWidth::Standard;
        }

        public function defaultPageSize(): int
        {
            return 10;
        }
    };

    expect($definition->formMode())->toBe(CrudFormMode::Page)
        ->and($definition->pageWidth())->toBe(CrudLayoutWidth::Standard)
        ->and($definition->formWidth())->toBe(CrudLayoutWidth::Standard)
        ->and($definition->defaultPageSize())->toBe(10);
});
