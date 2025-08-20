<?php

namespace Laraish\Foundation;

use Illuminate\Foundation\Application as BaseApplication;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;

class Application extends BaseApplication
{
    public function __construct($basePath = null)
    {
        $isConsoleEnvironment = PHP_SAPI === 'cli';

        // WordPressを最初にロード（Artisanコマンド用）
        if ($isConsoleEnvironment) {
            if (!defined('WP_USE_THEMES')) {
                define('WP_USE_THEMES', false);
            }

            // プラグインのロードを防ぐ（エラーを避けるため）
            if (!defined('WP_PLUGIN_DIR')) {
                define('WP_PLUGIN_DIR', '/NULL');
            }

            $wp_load = getenv('WP_LOAD_PATH') ?: realpath($basePath . '/../../../wp-load.php');
            if (file_exists($wp_load)) {
                require_once $wp_load;
            }
        }

        parent::__construct($basePath);
    }

    /**
     * Register all of the base service providers.
     * Laraishの必須プロバイダーを自動登録
     */
    protected function registerBaseServiceProviders()
    {
        parent::registerBaseServiceProviders();

        // Laraishの必須プロバイダー
        $this->register(new \Laraish\Foundation\Support\Providers\RouteServiceProvider($this));
        $this->register(new \Laraish\Support\Wp\Providers\BladeDirectivesProvider($this));

        // WordPress固有のプロバイダーを登録
        $this->registerWordPressProviders();
        // Register WordPress hooks
        $this->registerWordPressHooks();
    }

    /**
     * Register WordPress-specific service providers from bootstrap/wpProviders.php
     *
     * @return void
     */
    protected function registerWordPressProviders(): void
    {
        $bootstrapWpProvidersPath = $this->bootstrapPath('wpProviders.php');

        if ($bootstrapWpProvidersPath && file_exists($bootstrapWpProvidersPath)) {
            $packageProviders = require $bootstrapWpProvidersPath;

            foreach ($packageProviders as $index => $provider) {
                if (!class_exists($provider)) {
                    unset($packageProviders[$index]);
                }
            }
        }

        // register WordPress service providers
        foreach ($packageProviders ?? [] as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Register WordPress hooks from WpListeners directory
     *
     * @return void
     */
    protected function registerWordPressHooks(): void
    {
        /** @var \Laraish\Foundation\WpHook\WpHookRegistry $registry */
        $registry = $this->make(\Laraish\Foundation\WpHook\WpHookRegistry::class);

        $hooks = $registry->discoverWpHooks();

        foreach ($hooks as $hookConfig) {
            $registry->registerHook($hookConfig->hookType, $hookConfig->hookName, $hookConfig);
        }
    }

    /**
     * WordPress環境用のリクエストハンドリング
     * LaraishのHttp Kernelと同じ動作を再現
     *
     * @param Request $request
     * @return void
     */
    public function handleRequest(Request $request)
    {
        // wpRouterを使用するKernelを作成
        // registerBaseServiceProvidersでwpRouterは確実に存在
        $kernel = $this->make(HttpKernelContract::class, [
            'app' => $this,
            'router' => $this['router'],
        ]);

        // WordPress管理画面の場合は何もしない
        if (is_admin()) {
            return;
        }

        // template_includeフィルターで実際の処理を行う（LaraishのKernelと同じ）
        add_filter(
            'template_include',
            function ($template) use ($kernel, $request) {
                // index.phpテンプレート以外の場合はそのまま返す
                if ($template !== get_template_directory() . '/index.php') {
                    return $template;
                }

                // Kernel->handle()が全てを処理：
                // - bootstrap() （Service Providers起動）
                // - ミドルウェアパイプライン
                // - ルーティング（wpRouterを使用）
                // - RequestHandledイベント発火
                $kernel->handle($request);

                return $template;
            },
            PHP_INT_MAX,
        );
    }
}
