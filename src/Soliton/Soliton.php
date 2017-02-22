<?php namespace Soliton;

include_once 'Query.php';
include_once 'Response.php';

/**
 * Class Soliton
 * @package Soliton
 */
class Soliton
{

    /**
     * @var string
     */
    private $version = '2.3';

    /**
     * Сгруппированные ключи запросов
     * @var array
     */
    private $groups = [];

    /**
     * Запросы
     * @var array
     */
    private $queries = [];

    /**
     * Результат работы запроса
     * @var
     */
    private $responses = [];

    /**
     * Общее время выполнение
     * @var int
     */
    private $execution_time;

    /**
     * @var float - 1=100%
     */
    private $loop_percent = 0.75;

    /**
     * @param array $queries
     * @param int $execution_time - default 1500ms = 25s
     * @param float $loop_percent
     * @throws \Exception
     */
    public function __construct(array $queries, $execution_time = 1500, $loop_percent = 0.75)
    {
        $this->execution_time = (int)$execution_time;
        $this->loop_percent = (float)$loop_percent;

        foreach ($queries as $alias => $options) {
            $this->queries[$alias] = $options instanceof Query ? $options : new Query($alias, $options);
        }
    }

    /**
     * @param int $execution_time - millisecond
     * @param float $loop_percent - 1 = 100%
     * @return $this
     * @throws \Exception
     */
    public function timeout($execution_time = null, $loop_percent = null)
    {
        $this->execution_time = $execution_time === null ? $this->execution_time : (int)$execution_time;
        $this->loop_percent = $loop_percent === null ? $this->loop_percent : (float)$loop_percent;

        if ($this->execution_time === 0 || $this->loop_percent === 0) {
            throw new \Exception('"Total time" or "percent loop" mast not be zero.', 70);
        }
        return $this;
    }

    /**
     * @param array $aliases
     * @param bool  $only_correct_responses
     * @return mixed
     * @throws \Exception
     */
    public function get(array $aliases = [], $only_correct_responses = true)
    {
        $present_aliases = array_keys($this->responses);
        // Выясняем все кто связан с запрашиваемыми aliases
        if (count($aliases) === 0) {
            $aliases = array_keys($this->queries);
        }
        $need_aliases = $this->getChainDependencies($aliases, $present_aliases);
        // Расслоение по группам запросов к ядру
        $this->groups = $this->separator($need_aliases, $present_aliases);
        $this->run();
        // Возвращаем только затребованные
        return $this->getResponses($aliases, $only_correct_responses);
    }

    /**
     * @return $this
     * @throws \Exception
     */
    private function run()
    {
        $total_time = $this->execution_time;
        $groups_count = count($this->groups);
        foreach ($this->groups as $index => $queries) {
            $loop_time = $total_time * ($groups_count > 1 ? $this->loop_percent : 1); // Последнего не ограничиваем
            $start = microtime(true);

            $this->executeGroup($index, $loop_time);
            $stop = microtime(true);
            $total_time -= (($stop - $start) * 1000);

            $groups_count--;
        }
    }

    /**
     * @param array $need_aliases - if empty then return all
     * @param array $present_aliases - resent responses aliases
     * @return array
     * @throws \Exception
     */
    private function separator(array $need_aliases = [], array $present_aliases = [])
    {
        $groups = [];
        $req = $this->queries;
        // максимальное кол-во итераций равно кол-ву элементов всего
        $total = count($req);
        $cnt = 0;

        for ($index = 0; $index < $total && $cnt < $total; $index++) {
            $new_group = [];
            /** @var Query $query */
            foreach ($req as $alias => $query) {
                if (
                    in_array($alias, $need_aliases)
                    && ! $this->checkDependency($query->getDependency($present_aliases), $groups)
                ) {
                    // Этот элемент еще рано добавлять в круг - ничего не делаем. Есть зависимости но не все в кругах
                } else {
                    if (in_array($alias, $need_aliases)) {
                        $new_group[] = $alias;
                    }
                    $cnt++;
                    unset($req[$alias]);
                }
            }
            if (count($new_group) > 0) {
                $groups[] = $new_group;
            }
        }

        if (count($req) > 0) {
            $aliases = implode(', ', array_keys($req));
            throw new \Exception('Mistakes in dependencies or Loop requests: ' . $aliases, 69);
        }
        return $groups;
    }

    /**
     * @param array $aliases
     * @param array $present_aliases -  present responses aliases
     * @return array
     */
    private function getChainDependencies(array $aliases, array $present_aliases)
    {
        // Выбрать все из списка $needAliases,
        // и отключить зависимости с учетом того что уже какие-то responses есть
        $queries = $this->queries;
        $func = function (array $aliases) use ($queries, $present_aliases) {
            $new_dependencies = [];
            foreach ($aliases as $alias) {
                // берем запрос от которого зависим - ищем его зависимости, складируем их
                if (array_key_exists($alias, $queries)) {
                    /** @var Query $query */
                    $query = $queries[$alias];
                    $dependencies = $query->getDependency($present_aliases);
                    $new_dependencies += $dependencies; //http://php.net/manual/ru/language.operators.array.php
                }
            }
            return $new_dependencies;
        };

        $need_aliases = $aliases;
        for ($index = 0; $index < count($queries) && count($need_aliases) > 0; $index++) {
            $need_aliases = $func($need_aliases);
            $aliases = array_merge($aliases, $need_aliases);
        }
        return $aliases;
    }

    /**
     * @param array $dependencies проверяемые зависимости
     * @param array $groups круги в которых проверяем наличие запросов от которых зависим
     * @return bool
     */
    private function checkDependency(array $dependencies, array $groups)
    {
        $present_counter = 0;
        if (count($dependencies) > 0) {
            foreach ($groups as $group) {
                foreach ($dependencies as $request) {
                    if (in_array($request, $group)) {
                        $present_counter++;
                    }
                }
            }
        }
        return count($dependencies) === $present_counter;
    }

//  Execute ------------------------------------------------------------------------------------------------------------

    /**
     * Выбираем активные
     * @param array $groups
     * @return array
     */
    private function getExecutableRequests(array $groups)
    {
        $executable_queries = [];
        foreach ($groups as $alias) {
            /** @var Query $req */
            $req = $this->queries[$alias];
            if ($req->isExecutable()) {
                $executable_queries[] = $alias;
            }
        }
        return $executable_queries;
    }

    /**
     * @param int $index
     * @param int $group_time
     */
    private function executeGroup($index, $group_time)
    {
        $group = $this->getExecutableRequests($this->groups[$index]);
        // Отрабатываем before callback
        $this->executeQueriesBefore($group);

        $group = $this->getExecutableRequests($this->groups[$index]);
        $cnt = count($group);

        if ($cnt !== 0) {
            if ((int)$group_time >= 0) { // Блокирую выполнение если нет времени на эту операцию
                if ($cnt === 1) {
                    $this->curlOne($group[0], $group_time);
                } else {
                    $this->curlMany($group, $group_time);
                }
            } else { //Если залочил то необходимо создать пустышки. Что-бы after callback отработал
                foreach ($group as $alias) {
                    $this->createErrorResponse($alias, 'Previous loop timed out');
                }
            }
            // Отрабатываем after callback
            $this->executeQueriesAfter($group);
        }
    }

    /**
     * @param array $aliases
     */
    private function executeQueriesBefore(array $aliases)
    {
        // переданные сюда названия, 100% существующие запросы
        foreach ($aliases as $alias) {
            /** @var Query $query */
            $query = $this->queries[$alias];

            // Результаты всех зависимостей должны быть корректны.
            $responses_array = $this->getResponses($query->getDependency());
            if (count($responses_array) === count($query->getDependency())) {
                $query->runBeforeFunc($responses_array, $alias);
            } else {
                // Если запрос не будет выполнен то необходимо добавить Response с ошибкой.
                $query->setExecutable(false);

                $need_responses = array_diff($aliases, array_keys($responses_array));
                $error_msg = 'Not all depending on compliance. Incorrect queries: ' . implode(', ', $need_responses);
                $this->createErrorResponse($alias, $error_msg);
            }
        }
    }

    /**
     * @param array $aliases
     */
    private function executeQueriesAfter(array $aliases)
    {
        // переданные сюда названия, 100% существующие запросы
        foreach ($aliases as $alias) {
            /** @var Query $query */
            $query = $this->queries[$alias];
            $response = $this->responses[$alias];
            $query->runAfterFunc($response);
        }
    }

//  Response -----------------------------------------------------------------------------------------------------------

    /**
     * @param array $responses
     * @return $this
     */
    public function setResponses(array $responses)
    {
        /** @var Response $response */
        foreach ($responses as $alias => $response) {
            if ($response instanceof Response && $response->isCorrect()) {
                $this->responses[$alias] = $response;
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function clearResponses()
    {
        $this->responses = [];
        return $this;
    }

    /**
     * @param array $aliases
     * @param bool  $only_correct_responses
     * @return array
     */
    private function getResponses(array $aliases, $only_correct_responses = true)
    {
        $result = [];
        foreach ($aliases as $alias) {
            // ответ есть и запрос корректен(нет ошибок)
            if (array_key_exists($alias, $this->responses)) {
                /** @var Response $response */
                $response = $this->responses[$alias];
                if ($only_correct_responses) {
                    if ($response->isCorrect()) {
                        $result[$alias] = $response;
                    }
                } else {
                    $result[$alias] = $response;
                }
            }
        }
        return $result;
    }

    /**
     * @param string $alias
     * @param string $message
     */
    private function createErrorResponse($alias, $message)
    {
        $response = $this->responses[$alias] = new Response();
        $response->setErrorMessage($message);
    }

//  CURL ---------------------------------------------------------------------------------------------------------------

    /**
     * @param Query $query
     * @param int $request_time
     * @return resource
     */
    private function initRequest(Query $query, $request_time)
    {
        // Создание нового ресурса cURL
        $channel = curl_init();
        // установка URL и других необходимых параметров
        $options = [
            CURLOPT_URL => $query->getFullUrl(),
            CURLOPT_HEADER => 1,  // get the header
            CURLINFO_HEADER_OUT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT_MS => $request_time, //milliseconds 1s=1000ms $requestTime
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

        $custom_options = $query->getOptions();
        $custom_options = static::preparePOSTFields($custom_options);
        $options = array_replace($options, $custom_options);

        if ($query->getMethodType() === 'post' && empty($options[CURLOPT_POSTFIELDS])) {
            $options[CURLOPT_POSTFIELDS] = [];
        }
        $files = $query->getFiles();
        static::prepareFiles($files, $options);

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
                static::convertToStringArray('', $options[CURLOPT_POSTFIELDS], $arr);
                $options[CURLOPT_POSTFIELDS] = $arr;
            }
        }
        return $options;
    }

    /**
     * @param array $files
     * @param array $options
     */
    private static function prepareFiles(array $files, array &$options)
    {
        if (count($files)) {
            foreach ($files as $key => $data_of_file) {
                if (is_array($data_of_file['name'])) {
                    $options = static::setRequestFiles($options, $key, $data_of_file);
                } else {
                    $options = static::setRequestFile($options, $key, $data_of_file);
                }
            }
        }
    }

    /**
     * Добавляет в массив упакованную строку с данными о файле для курла.
     * На вход получает массив со сведениями о файле формата $_FILES.
     *
     * @param array $options
     * @param string $key
     * @param array $file
     * @return array
     */
    private static function setRequestFile(array $options, $key, array $file)
    {
        if ($file['error'] === 0) {
            $options[CURLOPT_POSTFIELDS][$key] = new \CURLFile($file['tmp_name'], $file['type'], $file['name']);
        }
        return $options;
    }

    /**
     * Добавляет в массив упакованную строку с данными о файлах для курла.
     * На вход получает массив со сведениями о файле формата $_FILES.
     *
     * @param array $options
     * @param string $key
     * @param array $files
     * @return array
     */
    private static function setRequestFiles(array $options, $key, array $files)
    {
        foreach ($files['name'] as $index => $tmp) {
            if ($files['error'][$index] === 0) {
                $options[CURLOPT_POSTFIELDS]["{$key}[{$index}]"]
                    = new \CURLFile($files['tmp_name'][$index], $files['type'][$index], $files['name'][$index]);
            }
        }
        return $options;
    }

    /**
     * @param string $inputKey
     * @param array $input_array
     * @param array $result_array
     */
    private static function convertToStringArray($inputKey, $input_array, &$result_array)
    {
        foreach ($input_array as $key => $value) {
            $tmpKey = (bool)$inputKey ? $inputKey."[$key]" : $key;
            if (is_array($value)) {
                static::convertToStringArray($tmpKey, $value, $result_array);
            } else {
                $result_array[$tmpKey] = $value;
            }
        }
    }

    /**
     * @param string $alias
     * @param int $request_time
     */
    private function curlOne($alias, $request_time)
    {
        /** @var Query $query */
        $query = $this->queries[$alias];
        $channel = $this->initRequest($query, $request_time);
        $response = $this->responses[$alias] = new Response();
        $data = curl_exec($channel);
        $header_size = curl_getinfo($channel, CURLINFO_HEADER_SIZE);
        $response->setHeaderAndData($data, $header_size);
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
     * @param int $request_time
     */
    private function curlMany(array $aliases, $request_time)
    {
        $mh = curl_multi_init();

        //создаем набор дескрипторов cURL
        $handlers = [];
        foreach ($aliases as $alias) {
            $handlers[$alias] = $this->initRequest($this->queries[$alias], $request_time);
            curl_multi_add_handle($mh, $handlers[$alias]);
        }

        // curl_multi_select($mh, $requestTime / 1000); // ms to s

        //запускаем дескрипторы
        $running_requests = null;
        do {
            $mrc = curl_multi_exec($mh, $running_requests);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($running_requests && $mrc == CURLM_OK) {
            if (curl_multi_select($mh, $request_time / 1000) != -1) {
                usleep(100);
            }
            do {
                $mrc = curl_multi_exec($mh, $running_requests);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

//        do {
//            curl_multi_exec($mh, $running_requests);
//            curl_multi_select($mh);
//        } while ($running_requests > 0);

        foreach ($handlers as $alias => $handler) {
            $response = $this->responses[$alias] = new Response();
            if (curl_errno($handler) !== 0) {
                $response->setErrorMessage(curl_error($handler));
            }
            $response->setHttpCode(curl_getinfo($handler, CURLINFO_HTTP_CODE));
            $data = curl_multi_getcontent($handler);
            $header_size = curl_getinfo($handler, CURLINFO_HEADER_SIZE);
            $response->setHeaderAndData($data, $header_size);
            /** @var Query $query */
            $query = $this->queries[$alias];
            if ($query->isDetailConnection()) {
                $response->setDetailConnection(curl_getinfo($handler));
            }
            // close current handler
            curl_multi_remove_handle($mh, $handlers[$alias]);
        }
        curl_multi_close($mh);
    }
}

