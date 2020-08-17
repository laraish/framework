<?php

namespace Laraish\Support\Wp\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeDirectivesProvider extends ServiceProvider
{
    public $statements = ['loop', 'endloop'];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        array_walk($this->statements, function ($statement) {
            Blade::directive($statement, function ($expression) use ($statement) {
                $method = 'compile' . ucfirst($statement);

                return $this->$method($expression);
            });
        });
    }

    /**
     * Compile the loop statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    private function compileLoop($expression)
    {
        $phpCode = 'foreach( $GLOBALS[\'wp_query\']->posts as $post ): setup_the_post($post);';
        if ($expression) {
            preg_match('/(.*) as (.*)/', $expression, $matches);
            $afterAs = explode('=>', preg_replace('/\s+/', '', $matches[2]));
            $post = count($afterAs) === 2 ? $afterAs[1] : $afterAs[0];
            $post .= '->wpPost';
            $phpCode = "foreach({$expression}): setup_the_post( $post );";
        }

        return "<?php $phpCode ?>";
    }

    /**
     * Compile the endloop statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    private function compileEndloop($expression)
    {
        return '<?php endforeach; wp_reset_postdata(); ?>';
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
