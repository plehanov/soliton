<?php
/**
 * User: Valentin Plehanov (Takamura) valentin@plehanov.su
 * Date: 19.02.15
 * Time: 17:12
 */

namespace Soliton;

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
    private $errorMessage;

    /**
     * @var array
     */
    private $detailConnection = [];

    /**
     * @var $int
     */
    private $httpCode = 200;

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
        return $this->httpCode;
    }

    /**
     * @param int
     */
    public function setHttpCode($httpCode)
    {
        $this->httpCode = $httpCode;
    }

    /**
     * @return array
     */
    public function getDetailConnection()
    {
        return $this->detailConnection;
    }

    /**
     * @param array $detailConnection
     */
    public function setDetailConnection($detailConnection)
    {
        $this->detailConnection = $detailConnection;
    }

    /**
     * @return bool
     */
    public function isCorrect()
    {
        return (int)$this->httpCode >= 200 && (int)$this->httpCode < 300 &&
            ($this->errorMessage === null || $this->errorMessage === '');
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
        return $this->errorMessage;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @param string $data
     * @param int    $headerSize
     */
    public function setHeaderAndData($data, $headerSize = 0)
    {
        $header = substr($data, 0, $headerSize);
        $body = substr($data, $headerSize);

        $this->header = $header;
        $this->data = (string) $body;
    }
}
