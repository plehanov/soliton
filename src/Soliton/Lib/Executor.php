<?php
/**
 * User: Valentin Plehanov (Takamura) valentin@plehanov.su
 * Date: 22.02.17
 * Time: 14:39
 */

namespace Soliton\Lib;

use Soliton\Query;
use Soliton\Response;

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
     * @param string $alias
     * @param int $requestTime
     * @param array $queries
     * @param array $responses
     */
    public function curlOne($alias, $requestTime, array $queries, array &$responses)
    {
        /** @var Query $query */
        $query = $queries[$alias];
        $channel = $this->initRequest($query, $requestTime);
        $response = $responses[$alias] = new Response();
        $data = curl_exec($channel);
        $headerSize = curl_getinfo($channel, CURLINFO_HEADER_SIZE);
        $response->setHeaderAndData($data, $headerSize);
        if (curl_errno($channel) !== 0) {
            $response->setErrorMessage(curl_error($channel));
        }
        $response->setHttpCode(curl_getinfo($channel, CURLINFO_HTTP_CODE));
        if ($query->isDetailConnection()) {
            $response->setDetailConnection(curl_getinfo($channel));
        }
        curl_close($channel);
    }

    /**
     * @param array $aliases
     * @param int $requestTime
     * @param array $queries
     * @param array $responses
     */
    public function curlMany(array $aliases, $requestTime, array $queries, array &$responses)
    {
        $multiHandler = curl_multi_init();

        //создаем набор дескрипторов cURL
        $handlers = [];
        foreach ($aliases as $alias) {
            $handlers[$alias] = $this->initRequest($queries[$alias], $requestTime);
            curl_multi_add_handle($multiHandler, $handlers[$alias]);
        }

        // curl_multi_select($multiHandler, $requestTime / 1000); // ms to s

        //запускаем дескрипторы
        $runningRequests = null;
        do {
            $mrc = curl_multi_exec($multiHandler, $runningRequests);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($runningRequests && $mrc == CURLM_OK) {
            if (curl_multi_select($multiHandler, $requestTime / 1000) != -1) {
                usleep(100);
            }
            do {
                $mrc = curl_multi_exec($multiHandler, $runningRequests);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

//        do {
//            curl_multi_exec($multiHandler, $runningRequests);
//            curl_multi_select($multiHandler);
//        } while ($runningRequests > 0);

        $this->buildMultiResponse($handlers, $multiHandler, $queries, $responses);
        curl_multi_close($multiHandler);
    }

    /**
     * @author Valentin Plehanov (Takamura)
     * @param array $handlers
     * @param resource $multiHandler
     * @param array $queries
     * @param array $responses
     */
    private function buildMultiResponse(array $handlers, $multiHandler, array $queries, array &$responses)
    {
        foreach ($handlers as $alias => $handler) {
            $response = $responses[$alias] = new Response();
            if (curl_errno($handler) !== 0) {
                $response->setErrorMessage(curl_error($handler));
            }
            $response->setHttpCode(curl_getinfo($handler, CURLINFO_HTTP_CODE));
            $data = curl_multi_getcontent($handler);
            $headerSize = curl_getinfo($handler, CURLINFO_HEADER_SIZE);
            $response->setHeaderAndData($data, $headerSize);
            /** @var Query $query */
            $query = $queries[$alias];
            if ($query->isDetailConnection()) {
                $response->setDetailConnection(curl_getinfo($handler));
            }
            // close current handler
            curl_multi_remove_handle($multiHandler, $handlers[$alias]);
        }
    }

    /**
     * @param Query $query
     * @param int $requestTime
     * @return resource
     */
    private function initRequest(Query $query, $requestTime)
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