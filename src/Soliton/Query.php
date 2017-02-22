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
    private $methodType = 'get';

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
    private $beforeFunc = [];

    /**
     * @var array
     */
    private $afterFunc = [];

    /**
     * @var array
     */
    private $methodParams = [];

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var bool
     */
    private $detailConnection = false;

    /**
     * @var bool
     */
    private $loadingHeaders = false;

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
        $this->methodType = strtolower($this->methodType);
        // Если один обработчик то обрачиваем его в массив
        if (is_callable($this->beforeFunc)) {
            $this->beforeFunc = [$this->beforeFunc];
        } elseif (count((array)$this->beforeFunc) === 0) {
            $this->beforeFunc = [];
        }
        // Если один обработчик то обрачиваем его в массив
        if (is_callable($this->afterFunc)) {
            $this->afterFunc = [$this->afterFunc];
        } elseif (count((array)$this->afterFunc) === 0) {
            $this->afterFunc = [];
        }
    }

    /**
     * @return boolean
     */
    public function isLoadingHeaders()
    {
        return $this->loadingHeaders;
    }

    /**
     * @param boolean $loadingHeaders
     */
    public function setLoadingHeaders($loadingHeaders)
    {
        $this->loadingHeaders = $loadingHeaders;
    }

    /**
     * @return boolean
     */
    public function isDetailConnection()
    {
        return $this->detailConnection;
    }

    /**
     * @param boolean $detailConnection
     */
    public function setDetailConnection($detailConnection)
    {
        $this->detailConnection = $detailConnection;
    }

    /**
     * @return string
     */
    public function getMethodType()
    {
        return $this->methodType;
    }

    /**
     * @param array $removeAliases
     * @return array
     */
    public function getDependency(array $removeAliases = [])
    {
        return array_diff($this->dependency, $removeAliases);
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
        return $this->beforeFunc;
    }

    /**
     * @param array $responses
     * @param string $alias
     */
    public function runBeforeFunc(array $responses, $alias)
    {
        foreach ($this->beforeFunc as $before) {
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
        return $this->afterFunc;
    }

    /**
     * @param Response $response
     */
    public function runAfterFunc(Response $response)
    {
        foreach ($this->afterFunc as $after) {
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
        if (count($this->methodParams) === 0) {
            $url = $this->url;
        } else {
            $index = strpos($this->url, '?');
            $paramsString = http_build_query($this->methodParams, '', '&');

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
        return $this->methodParams;
    }

    /**
     * @param array $methodParams
     */
    public function setMethodParams($methodParams)
    {
        $this->methodParams = (array)$methodParams;
    }

    /**
     * @param array $methodParams
     */
    public function addMethodParams($methodParams)
    {
        $this->methodParams = array_merge($this->methodParams, (array)$methodParams);
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
