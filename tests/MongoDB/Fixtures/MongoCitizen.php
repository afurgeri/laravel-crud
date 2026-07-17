<?php

namespace Tests\MongoDB\Fixtures;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

class MongoCitizen extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'crud_mongo_citizens';

    protected $guarded = [];

    /**
     * @return BelongsTo<MongoCity, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(MongoCity::class, 'city_id', '_id');
    }
}
