<?php

namespace Tests\Feature\Crud\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrudTestRecordProfessional extends Model
{
    use SoftDeletes;

    protected $table = 'crud_test_record_professionals';

    protected $guarded = [];
}
