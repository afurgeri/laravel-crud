<?php

namespace Tests\MongoDB\Fixtures;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

class MongoCity extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'crud_mongo_cities';

    protected $guarded = [];

    /**
     * @return HasMany<MongoCitizen, $this>
     */
    public function citizens(): HasMany
    {
        return $this->hasMany(MongoCitizen::class, 'city_id', '_id');
    }
}
