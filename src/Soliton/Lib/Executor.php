<?php
/**
 * User: Valentin Plehanov (Takamura) valentin@plehanov.su
 * Date: 22.02.17
 * Time: 14:39
 */

namespace Soliton\Lib;

use Soliton\Query;

/**
 * Class Executor
 * @package Soliton\Lib
 */
class Executor
{

    /**
     * @var string
     */
    private $version = '1.1.1';

    /**
     * @param Query $query
     * @param int $requestTime
     * @return resource
     */
    public function initRequest(Query $query, $requestTime)
    {
        // Создание нового ресурса cURL
        $channel = curl_init();
        // установка URL и других необходимых параметров
        $options = [
            CURLOPT_URL => $query->getFullUrl(),
            CURLOPT_HEADER => 1,  // get the header
            CURLINFO_HEADER_OUT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT_MS => $requestTime, //milliseconds 1s=1000ms $requestTime
            CURLOPT_USERAGENT => "Soliton/{$this->version} (PHP "
                . phpversion() . '; CURL ' . curl_version()['version'] . ')'
        ];
        // http://php.net/manual/ru/function.curl-setopt.php
        switch ($query->getMethodType()) {
            case 'post':
                $options[CURLOPT_POST] = true;
                break;
            case 'put':
                $options[CURLOPT_PUT] = true; // $defaults[CURLOPT_CUSTOMREQUEST] = 'PUT';
                break;
            case 'delete':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
            case 'get':
            default;
                $options[CURLOPT_HTTPGET] = true; // нужно указывать только в том случае если мы его изменили
        }

        $customOptions = $query->getOptions();
        $customOptions = static::preparePOSTFields($customOptions);
        $options = array_replace($options, $customOptions);

        if ($query->getMethodType() === 'post' && empty($options[CURLOPT_POSTFIELDS])) {
            $options[CURLOPT_POSTFIELDS] = [];
        }
        $files = $query->getFiles();
        (new Common)->prepareFiles($files, $options);

        curl_setopt_array($channel, $options);
        return $channel;
    }

    /**
     * @param array $options
     * @return array
     */
    private static function preparePOSTFields(array $options)
    {
        if (count($options)) {
            // пакуем опции если они переданны в виде массива
            if (isset($options[CURLOPT_POSTFIELDS]) && is_array($options[CURLOPT_POSTFIELDS])) {
                $arr = [];
                (new Common)->convertToStringArray('', $options[CURLOPT_POSTFIELDS], $arr);
                $options[CURLOPT_POSTFIELDS] = $arr;
            }
        }
        return $options;
    }
}