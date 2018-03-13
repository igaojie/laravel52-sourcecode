<?php

namespace Dotenv;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Exception\InvalidPathException;

/**
 * This is the loaded class.
 *
 * It's responsible for loading variables by reading a file from disk and:
 * - stripping comments beginning with a `#`,#为注释
 * - parsing lines that look shell variable setters, e.g `export key = value`, `key="value"`.
 * .env文件支持的格式 ①export key = value ②key="value"
 *
 * 配置文件以$_SERVER为准。如果env里的key和$_SERVER里的key相同，则通过配置$immutable来判断是否允许覆盖
 */
class Loader
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Are we immutable?
     *
     * @var bool
     */
    protected $immutable;

    /**
     * Create a new loader instance.
     *
     * @param string $filePath 文件路径
     * @param bool   $immutable 是否不可变
     *
     * @return void
     */
    public function __construct($filePath, $immutable = false)
    {
        $this->filePath = $filePath;
        $this->immutable = $immutable;
    }

    /**
     * Load `.env` file in given directory.
     * 在给定的目录里加在.env文件并解析成环境变量
     * @return array
     */
    public function load()
    {
        //确定文件是否可读
        $this->ensureFileIsReadable();

        $filePath = $this->filePath;
        //按行读取文件到数组
        $lines = $this->readLinesFromFile($filePath);
        foreach ($lines as $line) {
            //注释排除 将环境变量加在到$_SERVER里
            if (!$this->isComment($line) && $this->looksLikeSetter($line)) {
                $this->setEnvironmentVariable($line);
            }
        }

        return $lines;
    }

    /**
     * Ensures the given filePath is readable.
     *
     * @throws \Dotenv\Exception\InvalidPathException
     * 判断文件是否可读
     *
     * @return void
     */
    protected function ensureFileIsReadable()
    {
        if (!is_readable($this->filePath) || !is_file($this->filePath)) {
            throw new InvalidPathException(sprintf('Unable to read the environment file at %s.', $this->filePath));
        }
    }

    /**
     * Normalise the given environment variable.
     * 将环境变量常态化
     *
     * Takes value as passed in by developer and:
     * - ensures we're dealing with a separate name and value, breaking apart the name string if needed,
     * - cleaning the value of quotes,
     * - cleaning the name of quotes,
     * - resolving nested variables.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function normaliseEnvironmentVariable($name, $value)
    {
        list($name, $value) = $this->splitCompoundStringIntoParts($name, $value);
        list($name, $value) = $this->sanitiseVariableName($name, $value);
        list($name, $value) = $this->sanitiseVariableValue($name, $value);

        $value = $this->resolveNestedVariables($value);

        return array($name, $value);
    }

    /**
     * Process the runtime filters.
     *
     * Called from the `VariableFactory`, passed as a callback in `$this->loadFromFile()`.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    public function processFilters($name, $value)
    {
        list($name, $value) = $this->splitCompoundStringIntoParts($name, $value);
        list($name, $value) = $this->sanitiseVariableName($name, $value);
        list($name, $value) = $this->sanitiseVariableValue($name, $value);

        return array($name, $value);
    }

    /**
     * Read lines from the file, auto detecting line endings.
     *
     * @param string $filePath
     *
     * @return array
     */
    protected function readLinesFromFile($filePath)
    {
        // Read file into an array of lines with auto-detected line endings
        //http://php.net/manual/zh/filesystem.configuration.php
        //当设为 On 时，PHP 将检查通过 fgets() 和 file() 取得的数据中的行结束符号是符合 Unix，MS-DOS，还是 Macintosh 的习惯。
        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        //FILE_IGNORE_NEW_LINES 在数组每个元素的末尾不要添加换行符
        //FILE_SKIP_EMPTY_LINES 跳过空行
        //把整个文件读入一个数组中。
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        ini_set('auto_detect_line_endings', $autodetect);

        return $lines;
    }

    /**
     * Determine if the line in the file is a comment, e.g. begins with a #.
     * 判断文件里的其中一行是否是注释 例如用#开头
     * @param string $line
     *
     * @return bool
     */
    protected function isComment($line)
    {
        return strpos(ltrim($line), '#') === 0;
    }

    /**
     * Determine if the given line looks like it's setting a variable.
     * 判断给定的一行看起来像个设置一个变量 也就是说用其中有个等号
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeSetter($line)
    {
        return strpos($line, '=') !== false;
    }

    /**
     * Split the compound string into parts.
     * 分割字符串到数组
     *
     * If the `$name` contains an `=` sign, then we split it into 2 parts, a `name` & `value`
     * disregarding the `$value` passed in.
     * 如果$name里有=号，那么默认会舍弃掉$value
     * F1=V1&F2=V2&F3=V3     [F1] => V1&F2=V2&F3=V3
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function splitCompoundStringIntoParts($name, $value)
    {
        //如果$name里有=
        if (strpos($name, '=') !== false) {
            list($name, $value) = array_map('trim', explode('=', $name, 2));
        }

        return array($name, $value);
    }

    /**
     * Strips quotes from the environment variable value.
     * 过滤环境变量的值
     * @param string $name
     * @param string $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array
     */
    protected function sanitiseVariableValue($name, $value)
    {
        $value = trim($value);
        if (!$value) {
            return array($name, $value);
        }

        if ($this->beginsWithAQuote($value)) { // value starts with a quote
            $quote = $value[0];
            $regexPattern = sprintf(
                '/^
                %1$s          # match a quote at the start of the value
                (             # capturing sub-pattern used
                 (?:          # we do not need to capture this
                  [^%1$s\\\\] # any character other than a quote or backslash
                  |\\\\\\\\   # or two backslashes together
                  |\\\\%1$s   # or an escaped quote e.g \"
                 )*           # as many characters that match the previous rules
                )             # end of the capturing sub-pattern
                %1$s          # and the closing quote
                .*$           # and discard any string after the closing quote
                /mx',
                $quote
            );
            $value = preg_replace($regexPattern, '$1', $value);
            $value = str_replace("\\$quote", $quote, $value);
            $value = str_replace('\\\\', '\\', $value);
        } else {
            $parts = explode(' #', $value, 2);
            $value = trim($parts[0]);

            // Unquoted values cannot contain whitespace
            //如果包含空格 必须用引号包起来
            if (preg_match('/\s+/', $value) > 0) {
                throw new InvalidFileException('Dotenv values containing spaces must be surrounded by quotes.');
            }
        }

        return array($name, trim($value));
    }

    /**
     * Resolve the nested variables.
     * 嵌套变量的处理
     * LARAVELBLOG_KEY_KEY=${SESSION_DRIVER} 会使用SESSION_DRIVER的值作为value
     * Look for {$varname} patterns in the variable value and replace with an
     * existing environment variable.
     *
     * @param string $value
     *
     * @return mixed
     */
    protected function resolveNestedVariables($value)
    {
        //如果$value里有$变量符号 允许值里面有变量
        if (strpos($value, '$') !== false) {
            $loader = $this;
            $value = preg_replace_callback(
                '/\${([a-zA-Z0-9_]+)}/',
                function ($matchedPatterns) use ($loader) {
                    $nestedVariable = $loader->getEnvironmentVariable($matchedPatterns[1]);
                    if ($nestedVariable === null) {
                        return $matchedPatterns[0];
                    } else {
                        return $nestedVariable;
                    }
                },
                $value
            );
        }

        return $value;
    }

    /**
     * Strips quotes and the optional leading "export " from the environment variable name.
     * 去掉引号 和 export 导出的环境变量
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function sanitiseVariableName($name, $value)
    {
        $name = trim(str_replace(array('export ', '\'', '"'), '', $name));

        return array($name, $value);
    }

    /**
     * Determine if the given string begins with a quote.
     * 判断字符串是否以引号开头
     * @param string $value
     *
     * @return bool
     */
    protected function beginsWithAQuote($value)
    {
        //strpbrk 在字符串中查找一组字符的任何一个字符
        return strpbrk($value[0], '"\'') !== false;
    }

    /**
     * Search the different places for environment variables and return first value found.
     * 查找环境变量出现的不同位置并返回发现的第一个值
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getEnvironmentVariable($name)
    {
        //查找顺序为：$_ENV $_SERVER 最后是.env文件
        switch (true) {
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];
            case array_key_exists($name, $_SERVER):
                return $_SERVER[$name];
            default:
                $value = getenv($name);
                return $value === false ? null : $value; // switch getenv default to null
        }
    }

    /**
     * Set an environment variable.
     * 设置环境变量
     *
     * This is done using:
     * - putenv,
     * - $_ENV,
     * - $_SERVER.
     *
     * The environment variable value is stripped of single and double quotes.
     * 环境变量的值是去掉了单引号和双引号的
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    public function setEnvironmentVariable($name, $value = null)
    {
        list($name, $value) = $this->normaliseEnvironmentVariable($name, $value);

        // Don't overwrite existing environment variables if we're immutable
        // 如果不能变更，请不要覆盖存在的环境变量
        // Ruby's dotenv does this with `ENV[key] ||= value`.
        //getEnvironmentVariable 来判断如果$_SERVER里存在是覆盖还是跳过 也就是以哪个文件为准的策略
        if ($this->immutable && $this->getEnvironmentVariable($name) !== null) {
            return;
        }

        // If PHP is running as an Apache module and an existing
        // Apache environment variable exists, overwrite it
        if (function_exists('apache_getenv') && function_exists('apache_setenv') && apache_getenv($name)) {
            apache_setenv($name, $value);
        }

        if (function_exists('putenv')) {
            putenv("$name=$value");
        }
        //echo "name:{$name}=value:{$value}<br>";
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;//从这里能看到 laravel将.env文件里的变量写入了$_SERVER里。
    }

    /**
     * Clear an environment variable.
     * 清除一个环境变量
     * This is not (currently) used by Dotenv but is provided as a utility
     * method for 3rd party code.
     *
     * This is done using:
     * - putenv,
     * - unset($_ENV, $_SERVER).
     *
     * @param string $name
     *
     * @see setEnvironmentVariable()
     *
     * @return void
     */
    public function clearEnvironmentVariable($name)
    {
        // Don't clear anything if we're immutable.
        if ($this->immutable) {
            return;
        }

        if (function_exists('putenv')) {
            putenv($name);
        }

        unset($_ENV[$name], $_SERVER[$name]);
    }
}
