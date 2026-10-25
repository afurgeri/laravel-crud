<?php

namespace Tests\Feature\Crud\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Crud\Concerns\HasCrudDefinition;
use Modules\Crud\Contracts\HasCrudDefinition as HasCrudDefinitionContract;

class CrudTestRecord extends Model implements HasCrudDefinitionContract
{
    use HasCrudDefinition;

    protected $table = 'crud_test_records';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
        'starts_at' => 'datetime',
    ];

    public static function crudDefinition(): string
    {
        return CrudTestRecordDefinition::class;
    }

    /**
     * @return HasMany<CrudTestRecordNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CrudTestRecordNote::class, 'crud_test_record_id');
    }

    /**
     * @return BelongsTo<CrudTestRecordProfessional, $this>
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(CrudTestRecordProfessional::class);
    }

    /**
     * @return HasOne<CrudTestRecordNote, $this>
     */
    public function visibleNote(): HasOne
    {
        return $this->hasOne(CrudTestRecordNote::class, 'crud_test_record_id')->where('body', '!=', 'Excluded');
    }

    /**
     * @return BelongsTo<CrudTestRecord, $this>
     */
    public function matchingRecord(): BelongsTo
    {
        return $this->belongsTo(self::class, 'email', 'email');
    }
}
