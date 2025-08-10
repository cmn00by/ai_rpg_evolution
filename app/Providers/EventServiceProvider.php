<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\AttributeCreated;
use App\Events\AttributeUpdated;
use App\Events\AttributeDeleted;
use App\Listeners\SyncAttributeToClasses;
use App\Listeners\SyncAttributeToCharacters;
use App\Listeners\RebuildComputedCaches;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            \App\Listeners\AssignPlayerRole::class,
        ],
        AttributeCreated::class => [
            SyncAttributeToClasses::class,
            SyncAttributeToCharacters::class,
            RebuildComputedCaches::class,
        ],
        AttributeUpdated::class => [
            SyncAttributeToClasses::class,
            SyncAttributeToCharacters::class,
            RebuildComputedCaches::class,
        ],
        AttributeDeleted::class => [
            SyncAttributeToClasses::class,
            SyncAttributeToCharacters::class,
            RebuildComputedCaches::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
