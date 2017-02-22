<?php namespace Soliton;

/**
 * Class Response
 * @package Soliton
 */
class Response
{
    /**
     * @var mixed
     */
    private $data;

    /**
     * @var string
     */
    private $error_message;

    /**
     * @var array
     */
    private $detail_connection = [];

    /**
     * @var $int
     */
    private $http_code = 200;

    /**
     * @var string
     */
    private $header = '';

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param string $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * @return int
     */
    public function getHttpCode()
    {
        return $this->http_code;
    }

    /**
     * @param int
     */
    public function setHttpCode($http_code)
    {
        $this->http_code = $http_code;
    }

    /**
     * @return array
     */
    public function getDetailConnection()
    {
        return $this->detail_connection;
    }

    /**
     * @param array $detail_connection
     */
    public function setDetailConnection($detail_connection)
    {
        $this->detail_connection = $detail_connection;
    }

    /**
     * @return bool
     */
    public function isCorrect()
    {
        return (int)$this->http_code >= 200 && (int)$this->http_code < 300 &&
            ($this->error_message === null || $this->error_message === '');
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->error_message;
    }

    /**
     * @param string $error_message
     */
    public function setErrorMessage($error_message)
    {
        $this->error_message = $error_message;
    }

    /**
     * @param string $data
     * @param int    $header_size
     */
    public function setHeaderAndData($data, $header_size = 0)
    {
        $header = substr($data, 0, $header_size);
        $body = substr($data, $header_size);

        $this->header = $header;
        $this->data = (string) $body;
    }
}
