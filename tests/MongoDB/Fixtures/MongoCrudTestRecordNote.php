<?php

namespace Tests\MongoDB\Fixtures;

use MongoDB\Laravel\Eloquent\Model;

class MongoCrudTestRecordNote extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'crud_mongo_test_record_notes';

    protected $guarded = [];
}
