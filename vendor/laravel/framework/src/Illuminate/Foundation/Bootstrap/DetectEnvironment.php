<?php

namespace Illuminate\Foundation\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Contracts\Foundation\Application;

/**
 * Class DetectEnvironment
 * @package Illuminate\Foundation\Bootstrap
 * 探测应用环境
 */
class DetectEnvironment
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if (! $app->configurationIsCached()) {
            $this->checkForSpecificEnvironmentFile($app);

            try {
                //解析.env文件到环境变量
                (new Dotenv($app->environmentPath(), $app->environmentFile()))->load();
            } catch (InvalidPathException $e) {
                //
            }
        }
    }

    /**
     * Detect if a custom environment file matching the APP_ENV exists.
     * 判断是否有跟APP_ENV匹配的自定义环境变量文件
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected function checkForSpecificEnvironmentFile($app)
    {
        if (! env('APP_ENV')) {
            return;
        }

        $file = $app->environmentFile().'.'.env('APP_ENV');

        if (file_exists($app->environmentPath().'/'.$file)) {
            $app->loadEnvironmentFrom($file);
        }
    }
}
