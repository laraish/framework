<?php

namespace Laraish\Support\Wp;

use WP_Widget;

abstract class Widget extends WP_Widget
{
    public $baseId;
    public $name;
    public $description;
    public $widget_options = [];
    public $fields = [];

    /**
     * Register widget with WordPress.
     */
    function __construct()
    {
        foreach (['baseId', 'name', 'description'] as $property_name) {
            if (!isset($this->$property_name)) {
                throw new \ErrorException("The property '${property_name}' is not set.");
            }
        }

        parent::__construct(
            $this->baseId, // Base ID
            $this->name, // Name
            array_merge(['description' => $this->description], $this->widget_options) // widget_options
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance)
    {
        // echo $args['before_widget'];
        $this->displayWidget($args, $instance);
        // echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     * @return void
     */
    public function form($instance)
    {
        $this->displayForm($instance);
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = method_exists($this, '_update') ? $this->_update($new_instance, $old_instance) : [];
        foreach ($this->fields as $field) {
            $instance[$field] = !empty($new_instance[$field]) ? strip_tags($new_instance[$field]) : '';
        }

        return $instance;
    }

    protected function get_field_ids()
    {
        $field_ids = [];
        foreach ($this->fields as $field) {
            $field_ids[$field] = $this->get_field_id($field);
        }

        return (object) $field_ids;
    }

    protected function get_field_names()
    {
        $field_names = [];
        foreach ($this->fields as $field) {
            $field_names[$field] = $this->get_field_name($field);
        }

        return (object) $field_names;
    }

    abstract protected function displayWidget($args, $instance);

    abstract protected function displayForm($instance);
}
