<?php

namespace Laraish\Foundation\Support\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

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
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        $add_hook = function ($type, $name, $listeners) {
            $listeners = is_array($listeners) ? $listeners : [$listeners];

            array_walk($listeners, function ($listener) use ($type, $name) {
                $fn = 'add_' . $type; // `add_action` or `add_filter`
                $fn($name, function () use ($listener) {
                    $listener_instance = app()->make($listener);

                    return call_user_func_array([$listener_instance, 'handle'], func_get_args());
                }, 10, 10);
            });
        };


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
            $add_hook('action', $action, $listeners);
        }

        foreach ($this->filter as $filter => $listeners) {
            $add_hook('filter', $filter, $listeners);
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
