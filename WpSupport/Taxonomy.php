<?php

namespace Laraish\WpSupport;

class Taxonomy
{
    private static function getJaLabels($label)
    {
        return [
            'name'          => $label,
            'singular_name' => $label,
            'search_items'  => "{$label}を検索",
            'popular_items' => "よく使われている{$label}",
            'all_items'     => "すべての{$label}",
            'parent_item'   => "親{$label}",
            'update_item'   => '更新',
            'add_new_item'  => "新規{$label}を追加",
        ];
    }

    public static function register($name, $slug, $object_type = null, $args = null)
    {
        $default_args = [
            'label'   => $name,
            'rewrite' => [
                'hierarchical' => true
            ],
        ];

        if (method_exists(Taxonomy::class, $method = 'get' . ucfirst(str_replace('-', '', get_bloginfo("language"))) . 'Labels')) {
            $default_args['labels'] = Taxonomy::$method($name);
        }

        $args = is_array($args) ? array_merge($default_args, $args) : $default_args;

        add_action('init', function () use ($slug, $args, $object_type) {
            register_taxonomy($slug, $object_type, $args);
        });
    }

}