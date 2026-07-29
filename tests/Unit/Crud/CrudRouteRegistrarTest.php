<?php

use Illuminate\Support\Facades\Route;
use Modules\Crud\CrudRouteRegistrar;

test('crud route registration adds the options endpoint before resource routes', function () {
    app(CrudRouteRegistrar::class)->register(
        'appointments',
        'App\\Http\\Controllers\\AppointmentController',
        'App\\Models\\Appointment',
    );
    Route::getRoutes()->refreshNameLookups();

    expect(Route::getRoutes()->getByName('appointments.options'))->not->toBeNull()
        ->and(Route::getRoutes()->getByName('appointments.options')?->uri())
        ->toBe('appointments/options/{filter}');
});
