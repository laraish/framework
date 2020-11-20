<?php

namespace Laraish\Support\Wp;

use WP_Post;
use Laraish\Helper;
use Illuminate\Support\Arr;
use Laraish\Support\Wp\Model\Post;

class ThemeOptions
{
    /**
     * Arrays passed to the add_theme_support function.
     *
     * @param array $options
     */
    public static function add_theme_support(array $options): void
    {
        add_action('after_setup_theme', function () use ($options) {
            foreach ($options as $option) {
                if (isset($option['options'])) {
                    add_theme_support($option['feature'], $option['options']);
                } else {
                    add_theme_support($option['feature']);
                }
            }
        });
    }

    /**
     * Register custom post types.
     *
     * @param array $post_types
     */
    public static function post_types(array $post_types): void
    {
        $defaultArgs = [
            'public' => true,
            'has_archive' => true,
        ];

        foreach ($post_types as $postType => $args) {
            add_action('init', function () use ($postType, $args, $defaultArgs) {
                register_post_type($postType, $args + $defaultArgs);
            });
        }
    }

    /**
     * Register custom taxonomies.
     *
     * @param array $taxonomies
     * @throws \Exception
     */
    public static function taxonomies(array $taxonomies): void
    {
        foreach ($taxonomies as $taxonomy => $args) {
            if (!key_exists('object_type', $args)) {
                throw new \Exception("The `object_type` key is required to register the taxonomy `$taxonomy`.");
            }
            add_action('init', function () use ($taxonomy, $args) {
                register_taxonomy($taxonomy, Arr::pull($args, 'object_type'), $args);
            });
        }
    }

    /**
     * Add thumbnail column to the given post type.
     *
     * @param array $postTypes
     */
    public static function add_thumbnail_to_post_columns(array $postTypes): void
    {
        foreach ($postTypes as $postType) {
            add_filter(
                "manage_{$postType}_posts_columns",
                function ($defaults) {
                    $defaults['thumbnail'] = __('Thumbnail');

                    return $defaults;
                },
                5
            );

            add_action(
                "manage_{$postType}_posts_custom_column",
                function ($columnName) {
                    if ($columnName === 'thumbnail') {
                        the_post_thumbnail('medium', [
                            'style' => 'width:100%; max-width:100px; height:auto;',
                        ]);
                    }
                },
                5,
                2
            );

            add_action('admin_head', function () {
                echo '<style>.column-thumbnail { width: 10%; }</style>';
            });
        }
    }

    /**
     *
     * @param array $options
     */
    public static function title_placeholder(array $options): void
    {
        add_filter('enter_title_here', function ($title) use ($options) {
            global $post_type;

            foreach ($options as $_post_type => $_title) {
                if ($post_type === $_post_type) {
                    $title = $_title;
                    break;
                }
            }

            return $title;
        });
    }

    /**
     *
     * @param array|string $menus
     */
    public static function remove_menu_page($menus): void
    {
        add_action('admin_menu', function () use ($menus) {
            if (is_array($menus)) {
                foreach ($menus as $menu) {
                    remove_menu_page($menu);
                }
            } else {
                remove_menu_page($menus);
            }
        });
    }

    public static function remove_version(bool $remove): void
    {
        if ($remove) {
            add_filter('the_generator', function () {
                return '';
            });
        }
    }

    /**
     *
     * @param string $filepath
     */
    public static function editor_styles(string $filepath): void
    {
        add_action('after_setup_theme', function () use ($filepath) {
            add_theme_support('editor-styles');
            add_editor_style($filepath);
        });
    }

    /**
     *
     * @param string $excerpt_more_string
     */
    public static function excerpt_more(string $excerpt_more_string): void
    {
        add_filter('excerpt_more', function ($more) use ($excerpt_more_string) {
            return $excerpt_more_string;
        });
    }

    /**
     * Set the length of excerpt
     *
     * @param integer $new_length
     */
    public static function excerpt_length($new_length): void
    {
        add_filter('excerpt_length', function ($length) use ($new_length) {
            return $new_length;
        });
    }

    public static function image_sizes($image_sizes): void
    {
        add_theme_support('post-thumbnails');
        foreach ($image_sizes as $image_size) {
            call_user_func_array('add_image_size', $image_size);
        }
    }

    /**
     * Register page templates.
     *
     * @param array $templates
     */
    public static function page_templates(array $templates): void
    {
        foreach ($templates as $template) {
            if (isset($template['post'])) {
                foreach ((array) $template['post'] as $post) {
                    $post = $post instanceof Post ? $post : new Post($post);
                    add_filter(
                        "theme_{$post->post_type}_templates",
                        function ($post_templates, $wp_theme, $post_being_edited) use ($template, $post) {
                            $post_being_edited = $post_being_edited ?? get_post();
                            if (
                                $post_being_edited instanceof WP_Post and
                                (int) $post_being_edited->ID === $post->id()
                            ) {
                                $templateName = $templatePath = $template['name'];
                                $post_templates[$templatePath] = $templateName;
                            }

                            return $post_templates;
                        },
                        10,
                        3
                    );
                }
            }

            if (isset($template['post_type'])) {
                foreach ((array) $template['post_type'] as $post_type) {
                    add_filter("theme_{$post_type}_templates", function ($post_templates) use ($template) {
                        $templateName = $templatePath = $template['name'];
                        $post_templates[$templatePath] = $templateName;

                        return $post_templates;
                    });
                }
            }
        }
    }

    /**
     * Enqueuing both scripts and styles to admin page.
     *
     * @param array $options
     */
    public static function admin_page_assets(array $options): void
    {
        add_action('admin_enqueue_scripts', function ($hook) use ($options) {
            $defaults = [
                'hook' => null,
                'src' => '',
                'dependencies' => [],
                'version' => false,
                'media' => 'all',
                'in_footer' => false,
            ];

            $scriptDependencies = ['jquery', 'underscore', 'backbone'];

            foreach ($options as $optionName => $option) {
                // Skip if the option key is 'hook'.
                if ($optionName === 'hook') {
                    continue;
                }

                foreach ($option as $_args) {
                    $args = array_merge($defaults, $_args);

                    // Use global `hook` parameter if possible.
                    if (is_null($args['hook']) and !empty($options['hook'])) {
                        $args['hook'] = $options['hook'];
                    }

                    if (is_callable($args['hook'])) {
                        if (!call_user_func($args['hook'], $hook, get_current_screen())) {
                            continue;
                        }
                    } elseif ($args['hook'] !== $hook) {
                        continue;
                    }

                    // Generate default name if it was not supplied.
                    if (empty($args['name'])) {
                        $args['name'] = uniqid('laraish_', false);
                    }

                    if ($optionName === 'scripts') {
                        if (!isset($_args['dependencies'])) {
                            $args['dependencies'] = $scriptDependencies;
                        }
                        wp_enqueue_script(
                            $args['name'],
                            $args['src'],
                            $args['dependencies'],
                            $args['version'],
                            $args['in_footer']
                        );
                    }

                    if ($optionName === 'styles') {
                        wp_enqueue_style(
                            $args['name'],
                            $args['src'],
                            $args['dependencies'],
                            $args['version'],
                            $args['media']
                        );
                    }
                }
            }
        });
    }

    /**
     * Register navigation menus for a theme
     *
     * @param array $menus
     */
    public static function menus(array $menus): void
    {
        add_action('init', function () use ($menus) {
            register_nav_menus($menus);
        });
    }

    /**
     * Format value got from ACF.
     * Value can be a class name or a closure which returns the class name.
     * @param array $options
     */
    public static function format_acf_value(array $options): void
    {
        $postClass = $options['post'] ?? null;
        $termClass = $options['term'] ?? null;
        $userClass = $options['user'] ?? null;
        $assocArrayToObject = $options['assoc_array_to_object'] ?? false;

        add_filter(
            'acf/format_value',
            function ($value, $post_id, $field) use ($termClass, $userClass, $assocArrayToObject, $postClass) {
                if ($postClass && $value instanceof \WP_Post) {
                    $modelClassName = $postClass instanceof \Closure ? $postClass($value) : $postClass;
                    return new $modelClassName($value);
                }

                if ($termClass && $value instanceof \WP_Term) {
                    $modelClassName = $termClass instanceof \Closure ? $termClass($value) : $termClass;
                    return new $modelClassName($value);
                }

                if ($userClass && $value instanceof \WP_User) {
                    $modelClassName = $userClass instanceof \Closure ? $userClass($value) : $userClass;
                    return new $modelClassName($value);
                }

                if ($postClass && Helper::isArrayOfType($value, \WP_Post::class)) {
                    return array_map(function (\WP_Post $post) use ($postClass) {
                        $modelClassName = $postClass instanceof \Closure ? $postClass($post) : $postClass;
                        return new $modelClassName($post);
                    }, $value);
                }

                if ($termClass && Helper::isArrayOfType($value, \WP_Term::class)) {
                    return array_map(function (\WP_Term $term) use ($termClass) {
                        $modelClassName = $termClass instanceof \Closure ? $termClass($term) : $termClass;
                        return new $modelClassName($term);
                    }, $value);
                }

                if ($userClass && Helper::isArrayOfType($value, \WP_User::class)) {
                    return array_map(function (\WP_User $user) use ($userClass) {
                        $modelClassName = $userClass instanceof \Closure ? $userClass($user) : $userClass;
                        return new $modelClassName($user);
                    }, $value);
                }

                if ($assocArrayToObject && is_array($value)) {
                    return Helper::arrayToObject($value, true);
                }

                return $value;
            },
            20,
            3
        );
    }

    /**
     * Remove the 'wp-block-library' and 'wp-block-library-theme' style from both admin and frontend pages.
     * Set this to `true` to disable the default styles for the Gutenberg blocks that comes with WordPress.
     *
     * @param bool $value
     */
    public static function remove_block_library_styles(bool $value): void
    {
        if (!$value) {
            return;
        }

        add_filter('print_admin_styles', function () {
            $styles = explode(',', wp_styles()->concat);
            $stylesToBeRemoved = ['wp-block-library', 'wp-block-library-theme'];

            wp_styles()->concat = implode(
                ',',
                array_filter($styles, function ($style) use ($stylesToBeRemoved) {
                    return !in_array($style, $stylesToBeRemoved);
                })
            );

            return true;
        });

        add_action(
            'wp_enqueue_scripts',
            function () {
                wp_dequeue_style('wp-block-library');
                wp_dequeue_style('wp-block-library-theme');
            },
            100
        );
    }
}
