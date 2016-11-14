<?php

namespace Laraish\WpSupport;

class PostType
{
    private static function getJaLabels($label)
    {
        return [
            'singular_name'      => "{$label}",
            'menu_name'          => "{$label}",
            'add_new_item'       => "{$label}を追加",
            'new_item'           => "新規{$label}",
            'edit_item'          => "{$label}を編集",
            'view_item'          => "{$label}を表示",
            'not_found'          => "{$label}は見つかりませんでした",
            'not_found_in_trash' => "ゴミ箱に{$label}はありません。",
            'search_items'       => "{$label}を検索",
        ];
    }

    public static function register($name, $slug, $args = null)
    {
        $default_args = [
            'label'       => $name,
            'public'      => true,
            'has_archive' => true,
        ];

        if (method_exists(PostType::class, $method = 'get' . ucfirst(str_replace('-', '', get_bloginfo("language"))) . 'Labels')) {
            $default_args['labels'] = PostType::$method($name);
        }

        $args = is_array($args) ? array_merge($default_args, $args) : $default_args;

        add_action('init', function () use ($slug, $args) {
            register_post_type($slug, $args);
        });
    }

}