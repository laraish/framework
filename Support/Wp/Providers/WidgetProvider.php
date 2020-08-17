<?php

namespace Laraish\Support\Wp\Providers;

use Illuminate\Support\ServiceProvider;

class WidgetProvider extends ServiceProvider
{
    /**
     * Array of Class names that will be passed to `register_widget()`
     * @type array
     */
    protected $widgets = [];

    /**
     * Array of arguments(array) passed to `register_sidebar()`
     * Usually you should give something like [ 'name' => 'Nice Sidebar', 'id' => 'nice_sidebar']
     * @type array
     */
    protected $widgetAreas = [];

    /**
     * Very often you'd like to remove the default widgets supplied by WordPress
     * Here you can list up the widgets those you want to remove
     * @type array
     */
    protected $unregisterWidgets = [
        'WP_Widget_Pages',
        'WP_Widget_Calendar',
        'WP_Widget_Archives',
        'WP_Widget_Links',
        'WP_Widget_Meta',
        'WP_Widget_Search',
        'WP_Widget_Text',
        'WP_Widget_Categories',
        'WP_Widget_Recent_Posts',
        'WP_Widget_Recent_Comments',
        'WP_Widget_RSS',
        'WP_Widget_Tag_Cloud',
        'WP_Nav_Menu_Widget',
    ];

    public function boot()
    {
        add_action('widgets_init', function () {
            foreach ($this->widgets as $widget) {
                register_widget($widget);
            }
            foreach ($this->widgetAreas as $widgetArea) {
                register_sidebar($widgetArea);
            }
            foreach ($this->unregisterWidgets as $widget) {
                unregister_widget($widget);
            }
        });
    }

    public function register()
    {
    }
}
