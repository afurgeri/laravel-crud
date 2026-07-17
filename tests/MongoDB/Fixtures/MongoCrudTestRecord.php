<?php

namespace Tests\MongoDB\Fixtures;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Crud\Concerns\HasCrudDefinition;
use Modules\Crud\Contracts\HasCrudDefinition as HasCrudDefinitionContract;
use MongoDB\Laravel\Eloquent\Model;

class MongoCrudTestRecord extends Model implements HasCrudDefinitionContract
{
    use HasCrudDefinition;

    protected $connection = 'mongodb';

    protected $collection = 'crud_mongo_test_records';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function crudDefinition(): string
    {
        return MongoCrudTestRecordDefinition::class;
    }

    /**
     * @return HasMany<MongoCrudTestRecordNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(MongoCrudTestRecordNote::class, 'mongo_crud_test_record_id');
    }
}
