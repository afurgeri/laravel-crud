# Laravel CRUD

Declarative CRUD definitions, schema generation, filtering, sorting, validation, authorization hooks, and mutation managers for Laravel applications.

The package owns the reusable backend behavior. Resource-specific models, policies, controllers, routes, and frontend pages remain in the consuming application.

## Requirements

- PHP 8.3+
- Laravel 13+

## Installation

```bash
composer require afurgeri/laravel-crud
```

The service provider is registered automatically through Laravel package discovery.

If Packagist is temporarily unavailable, Composer can install the same tagged release directly from GitHub:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/afurgeri/laravel-crud"
        }
    ]
}
```

Then require the desired tag:

```bash
composer require afurgeri/laravel-crud:^0.2
```

Remove the temporary VCS repository once Packagist is available again. The VCS fallback is only an installation workaround; it does not change the package or its version constraints.

## Quick path

For each CRUD resource:

1. Add the `HasCrudDefinition` contract and concern to the model.
2. Create a definition class implementing `CrudDefinition`.
3. Declare columns, fields, and optional filters in the definition.
4. Inject the CRUD managers into the resource controller.
5. Render the generated schema and paginated records in the frontend.

The `Role` and `User` resources in the companion application follow this structure.

## Generator command

Install the generic frontend integration once per consuming application:

```bash
php artisan crud:install
```

This copies the reusable Vue components and TypeScript definitions to `resources/js`. Use `--force` only when you intentionally want to replace local changes:

```bash
php artisan crud:install --force
```

The package also registers a project scaffold command through Laravel package discovery:

```bash
php artisan make:crud Product --module=Catalog
```

It generates the starting files for a complete resource:

- migration;
- Eloquent model;
- `CrudDefinition`;
- controller;
- policy;
- factory;
- CRUD definition test;
- Inertia/Vue index page;
- module provider and routes when the module is new.

The command is intentionally opinionated around the module structure used by this project and Laravel + Inertia/Vue applications. It is not a generic model generator. After generation, review the placeholder `name` field, authorization rules, navigation, relationships, and frontend slots.

### Options

```bash
php artisan make:crud Product \
    --module=Catalog \
    --table=products \
    --force
```

- `--module` is required and uses a StudlyCase module name.
- `--table` overrides the default snake_case plural table name.
- `--force` allows overwriting generated files that already exist.

When the project has Wayfinder installed, the command regenerates route helpers automatically. Without Wayfinder, generation still succeeds and prints a warning. Pint is also used when available, but is not required for generation.

The command updates the application's Composer PSR-4 mapping and `bootstrap/providers.php` only when it creates a new module. Existing modules only receive the new resource route and generated files.

## 1. Connect the model

The model points to its definition through a small bridge. This keeps CRUD configuration out of the Eloquent model while allowing controllers to resolve it consistently.

```php
namespace App\Models;

use App\Crud\ProductCrudDefinition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Concerns\HasCrudDefinition;
use Modules\Crud\Contracts\HasCrudDefinition as HasCrudDefinitionContract;

#[Fillable(['name', 'sku', 'price'])]
class Product extends Model implements HasCrudDefinitionContract
{
    use HasCrudDefinition;

    public static function crudDefinition(): string
    {
        return ProductCrudDefinition::class;
    }
}
```

`makeCrudDefinition()` is provided by the concern and resolves the definition through Laravel's container:

```php
$definition = Product::makeCrudDefinition();
```

The definition class must be instantiable by the container. Constructor injection is supported when a definition needs an application service.

## 2. Define the resource

A definition implements `CrudDefinition` and returns the model plus the metadata used by the backend and frontend.

```php
namespace App\Crud;

use Illuminate\Database\Eloquent\Model;
use Modules\Crud\CrudColumn;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudField;
use Modules\Crud\Concerns\AuthorizesViaGate;
use Modules\Crud\Contracts\AuthorizesCrudIndex;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use Modules\Crud\Contracts\HasDefaultCrudSort;
use App\Models\Product;

class ProductCrudDefinition implements
    CrudDefinition,
    AuthorizesCrudIndex,
    AuthorizesCrudMutations,
    HasDefaultCrudSort
{
    use AuthorizesViaGate;

    /** @return class-string<Model> */
    public function model(): string
    {
        return Product::class;
    }

    public function title(): string
    {
        return __('Products');
    }

    public function description(): ?string
    {
        return __('Manage products.');
    }

    public function emptyLabel(): ?string
    {
        return __('No products found.');
    }

    public function columns(): array
    {
        return [
            CrudColumn::make('id')->sortable(),
            CrudColumn::make('name')->sortable()->searchable(),
            CrudColumn::make('sku')->sortable()->searchable(),
            CrudColumn::make('price')->sortable(),
        ];
    }

    public function fields(): array
    {
        return [
            CrudField::make('name', ['required', 'string', 'max:255']),
            CrudField::make('sku', ['required', 'string', 'max:50'])->unique(),
            CrudField::make('price', ['required', 'numeric', 'min:0']),
        ];
    }

    public function defaultSortColumn(): string
    {
        return 'name';
    }

    public function defaultSortDirection(): string
    {
        return 'asc';
    }
}
```

### Definition methods

| Method | Purpose |
| --- | --- |
| `model()` | Eloquent model class queried and mutated by the managers. |
| `title()` | Resource title sent in the schema. |
| `description()` | Optional explanatory text. |
| `emptyLabel()` | Optional empty-table message. |
| `columns()` | Columns exposed by the index. |
| `fields()` | Fields validated and accepted by create/update mutations. |

## Columns

```php
CrudColumn::make('name')
    ->sortable()
    ->searchable();

CrudColumn::make('internal_code')->hidden();

CrudColumn::make('permission_ids')->computed();
```

- `sortable()` allows the column in the requested `sort` parameter.
- `searchable()` includes the column in the text search.
- `hidden()` keeps the column out of the generated schema.
- `computed()` marks a value that is added by the controller and must not be selected, sorted, or searched as a database column.

## Fields and validation

Fields define both the generated form metadata and the validation rules used by `CrudMutationManager`.

```php
CrudField::make('email', ['required', 'email', 'max:255'])
    ->email()
    ->unique();

CrudField::make('password', ['required', 'string', 'min:8'])
    ->password()
    ->confirmed()
    ->createOnly();
```

- `unique()` adds a database uniqueness rule and ignores the current model during update.
- `unique('external_id')` validates a different database column.
- `email()` and `password()` select the frontend input type.
- `confirmed()` adds Laravel's `confirmed` rule and exposes the confirmation hint in the schema.
- `createOnly()` hides the field during update and excludes it from update validation.
- `rules([...])` replaces the field's validation rules.

The model must still define `$fillable` or the equivalent Laravel model attribute for every mutable field.

## Sorting

Implement `HasDefaultCrudSort` to define the initial ordering:

```php
use Modules\Crud\Contracts\HasDefaultCrudSort;

public function defaultSortColumn(): string
{
    return 'name';
}

public function defaultSortDirection(): string
{
    return 'asc';
}
```

Only columns declared as sortable can be requested by the client. Invalid sort columns are rejected by the index manager; invalid directions are normalized to `asc`.

## Filters

Implement `HasCrudFilters` and return `CrudFilter` instances:

```php
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\CrudFilter;

class ProductCrudDefinition implements CrudDefinition, HasCrudFilters
{
    public function filters(): array
    {
        return [
            CrudFilter::make('status')->select([
                'active' => 'Active',
                'archived' => 'Archived',
            ])->clearable(),

            CrudFilter::make('created_from', 'created_at')
                ->date()
                ->operator('>=')
                ->range('created_at')
                ->default(fn (): string => now()->startOfMonth()->toDateString())
                ->maxDate(fn (): string => now()->toDateString()),

            CrudFilter::make('created_to', 'created_at')
                ->date()
                ->operator('<=')
                ->range('created_at')
                ->maxDate(fn (): string => now()->toDateString()),
        ];
    }
}
```

Supported filter types are `text()`, `date()`, `number()`, and `select(...)`. Use `operator()` with `=`, `!=`, `>`, `>=`, `<`, or `<=`.

### Relationship filters

Use `relation()` when the filter applies to a related model:

```php
CrudFilter::make('role')
    ->select(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
    ->relation('roles', 'id')
    ->clearable();
```

This applies a `whereHas('roles', ...)` constraint instead of filtering a column on the main table.

### Dependent select options

An options closure may receive all effective filter values. This enables cascading filters:

```php
CrudFilter::make('city')
    ->select(function (array $filters): array {
        return City::query()
            ->when($filters['country'] ?? null, fn ($query, $country) =>
                $query->where('country_id', $country))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    })
    ->clearable();
```

Closures without parameters continue to work. The values include defaults and current request values, so option providers receive the same effective state used by the index query.

## Authorization

Authorization is opt-in per definition. Implement `AuthorizesCrudIndex` and/or `AuthorizesCrudMutations` and use the included `AuthorizesViaGate` concern:

```php
use Modules\Crud\Concerns\AuthorizesViaGate;
use Modules\Crud\Contracts\AuthorizesCrudIndex;
use Modules\Crud\Contracts\AuthorizesCrudMutations;

class ProductCrudDefinition implements
    CrudDefinition,
    AuthorizesCrudIndex,
    AuthorizesCrudMutations
{
    use AuthorizesViaGate;
}
```

The concern calls the standard Laravel policies for `viewAny`, `create`, `update`, and `delete`. Register a policy for the model using the application's normal Laravel mechanism.

If authorization is not implemented, the CRUD managers do not add authorization checks automatically.

## Eager loading

Implement `EagerLoadsCrudRelations` when the controller serializes relationship data for every row:

```php
use Modules\Crud\Contracts\EagerLoadsCrudRelations;

public function eagerLoads(): array
{
    return ['category:id,name'];
}
```

`CrudIndexManager` applies these relations before pagination. This prevents loading the same relationship once per row in the controller.

## Controller integration

The package does not provide resource controllers because the response shape and frontend are application-specific. Inject the managers through the controller method signature.

### Index

```php
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crud\CrudIndexManager;
use Modules\Crud\CrudSchemaManager;

public function index(
    Request $request,
    CrudIndexManager $index,
    CrudSchemaManager $schema,
): Response {
    $definition = Product::makeCrudDefinition();
    $sort = $request->string('sort')->toString() ?: null;
    $direction = $request->string('direction', 'asc')->toString();
    $search = $request->string('search')->toString() ?: null;
    $filters = $request->array('filters');

    /** @var LengthAwarePaginator<int, Product> $products */
    $products = $index->paginate(
        definition: $definition,
        page: $request->integer('page', 1),
        perPage: $request->integer('per_page', 15),
        sort: $sort,
        direction: $direction,
        search: $search,
        filters: $filters,
    );

    $products->through(fn (Product $product): array => [
        'id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
    ]);

    return Inertia::render('products/Index', [
        'crud' => $schema->for($definition, 'products', $sort, $direction, $search, $filters),
        'products' => $products,
    ]);
}
```

`CrudSchemaManager::for()` returns the metadata needed by a generic frontend: columns, fields, sort state, search state, filters, labels, and resolved select options.

### Create, update, and delete

Inject `CrudMutationManager` and pass the definition to the corresponding operation:

```php
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Crud\CrudMutationManager;

public function store(Request $request, CrudMutationManager $mutations): RedirectResponse
{
    $mutations->create(Product::makeCrudDefinition(), $request->all());

    return to_route('products.index');
}

public function update(
    Request $request,
    Product $product,
    CrudMutationManager $mutations,
): RedirectResponse {
    $mutations->update($product, Product::makeCrudDefinition(), $request->all());

    return to_route('products.index');
}

public function destroy(Product $product, CrudMutationManager $mutations): RedirectResponse
{
    $mutations->delete($product, Product::makeCrudDefinition());

    return to_route('products.index');
}
```

The mutation manager validates only the fields declared by the definition, applies policy authorization when configured, fills the model, and persists it. Relationship synchronization and resource-specific validation belong in the resource controller, as shown by the `Role` controller.

## Routes and frontend

Use normal Laravel resource routes in the application:

```php
Route::resource('products', ProductController::class)
    ->only(['index', 'store', 'update', 'destroy']);
```

The package has no Vue or React dependency. A consuming application can pass the `crud` schema to its own generic table/form components. The companion scaffold uses a `CrudPage` component with this contract:

```php
return Inertia::render('products/Index', [
    'crud' => $schema->for($definition, 'products', $sort, $direction, $search, $filters),
    'products' => $products,
]);
```

Resource-specific UI, such as role-permission checkboxes or user-role selectors, should be implemented as slots or dedicated components in the consuming application rather than added to the backend package.

## Local development

The scaffold application consumes this repository from `modules/Crud` through a Composer path repository. Changes made in that directory are immediately available after Composer regenerates the autoloader:

```bash
composer dump-autoload
```

The package itself is maintained in its own repository:

```text
https://github.com/afurgeri/laravel-crud
```

## Testing guidance

Definitions should be tested with fake Eloquent models and test-created tables. Test the definition schema, validation, authorization, filters, and mutation behavior independently from application-specific entities.

The package includes its own Pest and Testbench suite:

```bash
composer install
composer lint
composer test
```

GitHub Actions runs the manifest validation, formatting check, and tests against PHP 8.3 using both the lowest and stable dependency sets.

## API checklist for a new entity

- [ ] Model implements `HasCrudDefinitionContract` and uses `HasCrudDefinition`.
- [ ] Definition implements `CrudDefinition`.
- [ ] Every mutable field is declared in `fields()` and fillable on the model.
- [ ] Database columns intended for sort/search are explicitly declared in `columns()`.
- [ ] Computed or relationship-derived values are marked `computed()` and serialized by the controller.
- [ ] Policies are configured before enabling `AuthorizesViaGate`.
- [ ] Relationship data used per row is declared through `EagerLoadsCrudRelations`.
- [ ] Filters are declared through `HasCrudFilters` and use a valid operator.
- [ ] Resource-specific relationships are synchronized in the controller after the base mutation.
- [ ] The controller passes the schema and paginator to the frontend page.
