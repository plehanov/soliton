<?php namespace Soliton;

/**
 * Class Query
 * @package Soliton
 */
class Query
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string - lowercase always
     */
    private $method_type = 'get';

    /**
     * @var array
     */
    private $dependency = [];

    /**
     * @var bool
     */
    private $executable = true;

    /**
     * @var array
     */
    private $before_func = [];

    /**
     * @var array
     */
    private $after_func = [];

    /**
     * @var array
     */
    private $method_params = [];

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var bool
     */
    private $detail_connection = false;

    /**
     * @var bool
     */
    private $loading_headers = false;

    /**
     * @var array
     */
    private $files = [];

    /**
     * @param string $alias
     * @param mixed $options
     */
    public function __construct($alias, $options)
    {
        foreach ((array)$options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }
        $this->alias = $alias;
        $this->method_type = strtolower($this->method_type);
        // Если один обработчик то обрачиваем его в массив
        if (is_callable($this->before_func)) {
            $this->before_func = [$this->before_func];
        } elseif (count((array)$this->before_func) === 0) {
            $this->before_func = [];
        }
        // Если один обработчик то обрачиваем его в массив
        if (is_callable($this->after_func)) {
            $this->after_func = [$this->after_func];
        } elseif (count((array)$this->after_func) === 0) {
            $this->after_func = [];
        }
    }

    /**
     * @return boolean
     */
    public function isLoadingHeaders()
    {
        return $this->loading_headers;
    }

    /**
     * @param boolean $loading_headers
     */
    public function setLoadingHeaders($loading_headers)
    {
        $this->loading_headers = $loading_headers;
    }

    /**
     * @return boolean
     */
    public function isDetailConnection()
    {
        return $this->detail_connection;
    }

    /**
     * @param boolean $detail_connection
     */
    public function setDetailConnection($detail_connection)
    {
        $this->detail_connection = $detail_connection;
    }

    /**
     * @return string
     */
    public function getMethodType()
    {
        return $this->method_type;
    }

    /**
     * @param array $remove_aliases
     * @return array
     */
    public function getDependency(array $remove_aliases = [])
    {
        return array_diff($this->dependency, $remove_aliases);
    }

    /**
     * @return int
     */
    public function isExecutable()
    {
        return $this->executable;
    }

    /**
     * @param boolean $executable
     */
    public function setExecutable($executable)
    {
        $this->executable = $executable;
    }

    /**
     * @return array
     */
    public function getBeforeFunc()
    {
        return $this->before_func;
    }

    /**
     * @param array $responses
     * @param string $alias
     */
    public function runBeforeFunc(array $responses, $alias)
    {
        foreach ($this->before_func as $before) {
            // Выполнить запрос.
            if (is_callable($before)) {
                $before($this, $responses, $alias);
            }
        }
    }

    /**
     * @return array
     */
    public function getAfterFunc()
    {
        return $this->after_func;
    }

    /**
     * @param Response $response
     */
    public function runAfterFunc(Response $response)
    {
        foreach ($this->after_func as $after) {
            // Выполнить запрос.
            if (is_callable($after)) {
                $after($response);
            }
        }
    }

    /**
     * @return string
     */
    public function getFullUrl()
    {
        // если урле есть ? то отпилить в конце & и добавить все methodParams
        // в ином случае в конце добавить ? и methodParams
        if (count($this->method_params) === 0) {
            $url = $this->url;
        } else {
            $index = strpos($this->url, '?');
            $paramsString = http_build_query($this->method_params, '', '&');

            if ($index === false) {
                $url = "{$this->url}?{$paramsString}";
            } else {
                $url = rtrim($this->url, '&');
                $url .= "&{$paramsString}";
            }
        }
        return $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getMethodParams()
    {
        return $this->method_params;
    }

    /**
     * @param array $method_params
     */
    public function setMethodParams($method_params)
    {
        $this->method_params = (array)$method_params;
    }

    /**
     * @param array $methodParams
     */
    public function addMethodParams($methodParams)
    {
        $this->method_params = array_merge($this->method_params, (array)$methodParams);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return (array)$this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return (array)$this->files;
    }

    /**
     * @param array $files
     */
    public function setFiles($files)
    {
        $this->files = $files;
    }
}
