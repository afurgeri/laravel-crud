<?php

namespace Modules\Crud;

enum CrudOperation: string
{
    case Show = 'show';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}
