<?php

declare(strict_types=1);

namespace App\Providers;

use Psr\Container\ContainerInterface;
use Radix\ServiceProvider\ServiceProviderInterface;
use RuntimeException;

readonly class ListenersServiceProvider implements ServiceProviderInterface
{
    public function __construct(private ContainerInterface $container) {}

    public function register(): void
    {
        $dispatcher = $this->container->get(\Radix\EventDispatcher\EventDispatcher::class);
        /** @var \Radix\EventDispatcher\EventDispatcher $dispatcher */

        $listeners = require ROOT_PATH . '/config/listeners.php';

        if (!is_array($listeners)) {
            throw new RuntimeException('Config file config/listeners.php must return an array.');
        }

        /**
         * Struktur för $listeners:
         *
         * @var array<string, array<int, array{
         *     type: 'container'|'custom',
         *     listener: class-string,
         *     dependencies?: array<int, class-string>,
         *     stopPropagation?: bool
         * }>> $listeners
         */

        foreach ($listeners as $event => $handlers) {
            if (!is_string($event)) {
                throw new RuntimeException('Event name keys in config/listeners.php must be strings (usually class-string).');
            }

            if (!is_array($handlers)) {
                throw new RuntimeException("Handlers for event '$event' must be an array.");
            }

            /** @var array<int, array{
             *     type: 'container'|'custom',
             *     listener: class-string,
             *     dependencies?: array<int, class-string>,
             *     stopPropagation?: bool
             * }> $handlers
             */

            foreach ($handlers as $handler) {
                if (!is_array($handler) || !isset($handler['type'], $handler['listener'])) {
                    throw new RuntimeException("Each handler for event '$event' must be an array with at least 'type' and 'listener' keys.");
                }

                /** @var array{
                 *     type: 'container'|'custom',
                 *     listener: class-string,
                 *     dependencies?: array<int, class-string>,
                 *     stopPropagation?: bool
                 * } $handler
                 */

                $type = $handler['type'];
                $listenerId = $handler['listener'];

                if (!is_string($type) || !is_string($listenerId)) {
                    throw new RuntimeException("Handler 'type' and 'listener' for event '$event' must be strings.");
                }

                $listener = match ($type) {
                    'container' => $this->container->get($listenerId),
                    'custom' => new $listenerId(
                        ...array_map(
                            fn(string $dep) => $this->container->get($dep),
                            $handler['dependencies'] ?? []
                        )
                    ),
                    default => throw new RuntimeException("Invalid listener type: {$type}"),
                };

                assert(is_callable($listener));
                /** @var callable $listener */

                $dispatcher->addListener(
                    $event,
                    /**
                     * @param object $event
                     */
                    static function (object $event) use ($listener, $handler): void {
                        $listener($event);

                        if (!empty($handler['stopPropagation']) && $handler['stopPropagation'] === true) {
                            if (method_exists($event, 'stopPropagation')) {
                                $event->stopPropagation();
                            }
                        }
                    }
                );
            }
        }
    }
}
