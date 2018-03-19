<?php

namespace Dotenv;

/**
 * This is the dotenv class.
 *
 * It's responsible for loading a `.env` file in the given directory and
 * setting the environment vars.
 *
 * 这个是.env的类。负责解析.env文件设置的环境变量
 */
class Dotenv
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * The loader instance.
     *
     * @var \Dotenv\Loader|null
     */
    protected $loader;

    /**
     * Create a new dotenv instance.
     *
     * @param string $path
     * @param string $file
     *
     * @return void
     */
    public function __construct($path, $file = '.env')
    {
        $this->filePath = $this->getFilePath($path, $file);
        $this->loader = new Loader($this->filePath, true);
    }

    /**
     * Load environment file in given directory.
     *
     * @return array
     */
    public function load()
    {
        return $this->loadData();
    }

    /**
     * Load environment file in given directory.
     *
     * @return array
     */
    public function overload()
    {
        return $this->loadData(true);
    }

    /**
     * Returns the full path to the file.
     * 获取文件的绝对路径 默认文件名为.env
     * @param string $path
     * @param string $file
     *
     * @return string
     */
    protected function getFilePath($path, $file)
    {
        if (!is_string($file)) {
            $file = '.env';
        }

        $filePath = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;

        return $filePath;
    }

    /**
     * Actually load the data.
     * 加载数据
     * @param bool $overload
     *
     * @return array
     */
    protected function loadData($overload = false)
    {
        $this->loader = new Loader($this->filePath, !$overload);

        return $this->loader->load();
    }

    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variable
     *
     * @return \Dotenv\Validator
     */
    public function required($variable)
    {
        return new Validator((array) $variable, $this->loader);
    }
}
