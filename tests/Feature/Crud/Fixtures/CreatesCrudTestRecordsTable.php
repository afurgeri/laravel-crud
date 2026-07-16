<?php

namespace Tests\Feature\Crud\Fixtures;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesCrudTestRecordsTable
{
    protected function createCrudTestRecordsTable(): void
    {
        Schema::dropIfExists('crud_test_records');

        Schema::create('crud_test_records', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('crud_test_record_notes');

        Schema::create('crud_test_record_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crud_test_record_id');
            $table->string('body');
            $table->timestamps();
        });
    }
}
