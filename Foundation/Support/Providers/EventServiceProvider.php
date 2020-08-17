<?php

namespace Laraish\Foundation\Support\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Laraish\Support\Arr;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the WordPress actions
     * @var array
     */
    protected $action = [];

    /**
     * Register the WordPress filters
     * @var array
     */
    protected $filter = [];

    /**
     * Determine if hte given array is nested.
     *
     * @param array $array
     *
     * @return bool
     */
    public function isNested(array $array)
    {
        if (Arr::isAssociative($array)) {
            return false;
        }

        if (!is_array($array[0])) {
            return false;
        }

        return true;
    }

    public function addHooks($type, $name, $listeners)
    {
        if (!is_array($listeners)) {
            $listeners = [$listeners];
        } else {
            $listeners = $this->isNested($listeners) ? $listeners : [$listeners];
        }

        foreach ($listeners as $listener) {
            $fn = 'add_' . $type; // `add_action` or `add_filter`
            $priority = 10;
            $argumentsNumber = 10;
            $listenerClassName = $listener;

            if (is_array($listener)) {
                if (Arr::isAssociative($listener)) {
                    $listenerClassName = $listener['listener'];
                    $priority = isset($listener['priority']) ? $listener['priority'] : $priority;
                    $argumentsNumber = isset($listener['argumentsNumber'])
                        ? $listener['argumentsNumber']
                        : $argumentsNumber;
                } else {
                    $listenerClassName = isset($listener[0]) ? $listener[0] : $priority;
                    $priority = isset($listener[1]) ? $listener[1] : $priority;
                    $argumentsNumber = isset($listener[2]) ? $listener[2] : $argumentsNumber;
                }
            }

            $fn(
                $name,
                function () use ($listenerClassName) {
                    $listenerInstance = app()->make($listenerClassName);

                    return call_user_func_array([$listenerInstance, 'handle'], func_get_args());
                },
                $priority,
                $argumentsNumber
            );
        }
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->listens() as $event => $listeners) {
            $listeners = is_array($listeners) ? $listeners : [$listeners];
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        foreach ($this->subscribe as $subscriber) {
            Event::subscribe($subscriber);
        }

        foreach ($this->action as $action => $listeners) {
            $this->addHooks('action', $action, $listeners);
        }

        foreach ($this->filter as $filter => $listeners) {
            $this->addHooks('filter', $filter, $listeners);
        }
    }

    /**
     * Get the action and handlers.
     *
     * @return array
     */
    public function action()
    {
        return $this->action;
    }

    /**
     * Get the filter and handlers.
     *
     * @return array
     */
    public function filter()
    {
        return $this->filter;
    }
}
