<?php

declare(strict_types=1);

namespace App\Providers;

use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Event\ResponseEvent;
use Radix\Http\EventListeners\CacheControlListener;
use Radix\Http\EventListeners\ContentLengthListener;
use Radix\Http\EventListeners\CorsListener;
use Radix\ServiceProvider\ServiceProviderInterface;

class EventServiceProvider implements ServiceProviderInterface
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    private array $listen = [
        ResponseEvent::class => [
            CorsListener::class,
            ContentLengthListener::class,
            CacheControlListener::class,
        ],
    ];

    public function __construct(private readonly EventDispatcher $eventDispatcher) {}

    public function register(): void
    {
        // loop over each event in the listen array
        foreach ($this->listen as $eventName => $listeners) {
            // loop over each listener
            foreach (array_unique($listeners) as $listener) {
                $listenerInstance = new $listener();

                assert(is_callable($listenerInstance));

                // call eventDispatcher->addListener
                $this->eventDispatcher->addListener($eventName, $listenerInstance);
            }
        }
    }
}
