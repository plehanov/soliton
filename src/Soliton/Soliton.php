<?php
/**
 * User: Valentin Plehanov (Takamura) valentin@plehanov.su
 * Date: 19.02.15
 * Time: 17:12
 */


namespace Soliton;

use Soliton\Lib\Executor;

/**
 * Class Soliton
 * @package Soliton
 */
class Soliton
{

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
    private $executionTime;

    /**
     * @var float - 1=100%
     */
    private $loopPercent = 0.75;

    /**
     * @var bool
     */
    private $isOnlyCorrectResponses = false;

    /**
     * @param array $queries
     * @param int $executionTime - default 1500ms = 25s
     * @param float $loopPercent
     * @throws \Exception
     */
    public function __construct(array $queries, $executionTime = 1500, $loopPercent = 0.75)
    {
        $this->executionTime = (int)$executionTime;
        $this->loopPercent = (float)$loopPercent;

        foreach ($queries as $alias => $options) {
            $this->queries[$alias] = $options instanceof Query ? $options : new Query($alias, $options);
        }
    }

    /**
     * @param int $executionTime - millisecond
     * @param float $loopPercent - 1 = 100%
     * @return $this
     * @throws \Exception
     */
    public function timeout($executionTime = null, $loopPercent = null)
    {
        $this->executionTime = $executionTime === null ? $this->executionTime : (int)$executionTime;
        $this->loopPercent = $loopPercent === null ? $this->loopPercent : (float)$loopPercent;

        if ($this->executionTime === 0 || $this->loopPercent === 0) {
            throw new \Exception('"Total time" or "percent loop" mast not be zero.', 70);
        }
        return $this;
    }

    /**
     * Устанавливает триггер который фильтрует результаты работы (выдает только корректные ответы сервера)
     * @return $this
     */
    public function onlyCorrect()
    {
        $this->isOnlyCorrectResponses = true;
        return $this;
    }

    /**
     * @param array $aliases
     * @return mixed
     * @throws \Exception
     */
    public function get(array $aliases = [])
    {
        $presentAliases = array_keys($this->responses);
        // Выясняем все кто связан с запрашиваемыми aliases
        if (count($aliases) === 0) {
            $aliases = array_keys($this->queries);
        }
        $needAliases = $this->getChainDependencies($aliases, $presentAliases);
        // Расслоение по группам запросов к ядру
        $this->groups = $this->separator($needAliases, $presentAliases);
        $this->run();
        // Возвращаем только затребованные
        return $this->getResponses($aliases);
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function run()
    {
        $totalTime = $this->executionTime;
        $groupsCount = count($this->groups);

        foreach (array_keys($this->groups) as $index) {
            $loopTime = $totalTime * ($groupsCount > 1 ? $this->loopPercent : 1); // Последнего не ограничиваем
            $start = microtime(true);

            $this->executeGroup($index, $loopTime);
            $stop = microtime(true);
            $totalTime -= (($stop - $start) * 1000);

            $groupsCount--;
        }
    }

    /**
     * @param array $needAliases - необходимые завпросы
     * @param array $presentAliases - имеющиеся ответы на запросы, готовые
     * @return array
     * @throws \Exception
     */
    private function separator(array $needAliases = [], array $presentAliases = [])
    {
        $groups = [];
        $queries = $this->queries;
        // максимальное кол-во итераций равно кол-ву элементов всего
        $total = count($this->queries);
        $cnt = 0;

        for ($index = 0; $index < $total && $cnt < $total; $index++) {
            $newGroup = [];
            /** @var Query $query */
            foreach ($this->queries as $alias => $query) {
                if (
                    in_array($alias, $needAliases)
                    && ! $this->presentNeededDependency($query->getDependency($presentAliases), $groups)
                ) {
                    // Этот элемент еще рано добавлять в круг - ничего не делаем. Есть зависимости но не все в кругах
                } else {
                    if (in_array($alias, $needAliases)) {
                        $newGroup[] = $alias;
                    }
                    $cnt++;
                    unset($queries[$alias]);
                }
            }
            if (count($newGroup) > 0) {
                $groups[] = $newGroup;
            }
        }

        if (count($queries) > 0) {
            $aliases = implode(', ', array_keys($queries));
            throw new \Exception('Mistakes in dependencies or Loop requests: ' . $aliases, 69);
        }
        return $groups;
    }

    /**
     * @param array $aliases требуемые к исполнению запросы
     * @param array $presentAliases - имеющиеся ответы, запросы выполнять не надо
     * @return array
     */
    private function getChainDependencies(array $aliases, array $presentAliases)
    {
        // Выбрать все из списка $needAliases,
        // и отключить зависимости с учетом того что уже какие-то responses есть
        $queries = $this->queries;
        $func = function (array $aliases) use ($queries, $presentAliases) {
            $dependencies = [];
            foreach ($aliases as $alias) {
                // берем запрос от которого зависим - ищем его зависимости, складируем их
                if (array_key_exists($alias, $queries)) {
                    /** @var Query $query */
                    $query = $queries[$alias];
                    $dependencies += $query->getDependency($presentAliases); //http://php.net/manual/ru/language.operators.array.php
                }
            }
            return $dependencies;
        };

        $needAliases = $aliases;
        for ($index = 0; $index < count($queries) && count($needAliases) > 0; $index++) {
            $needAliases = $func($needAliases);
            $aliases = array_merge($aliases, $needAliases);
        }
        return $aliases;
    }

    /**
     * Удостоверяет что все требуемые зависимости присутсвуют в группах
     * @param array $dependencies проверяемые зависимости
     * @param array $groups круги в которых проверяем наличие запросов от которых зависим
     * @return bool
     */
    private function presentNeededDependency(array $dependencies, array $groups)
    {
        $presentCounter = 0;
        if (count($dependencies) > 0) {
            foreach ($groups as $group) {
                foreach ($dependencies as $request) {
                    if (in_array($request, $group)) {
                        $presentCounter++;
                    }
                }
            }
        }
        return count($dependencies) === $presentCounter;
    }

//  Execute ------------------------------------------------------------------------------------------------------------

    /**
     * Выбираем активные
     * @param array $groups
     * @return array
     */
    private function getExecutableRequests(array $groups)
    {
        $executableQueries = [];
        foreach ($groups as $alias) {
            /** @var Query $req */
            $req = $this->queries[$alias];
            if ($req->isExecutable()) {
                $executableQueries[] = $alias;
            }
        }
        return $executableQueries;
    }

    /**
     * @param int $index
     * @param int $groupTime
     */
    private function executeGroup($index, $groupTime)
    {
        $group = $this->getExecutableRequests($this->groups[$index]);
        // Отрабатываем before callback
        $this->executeQueriesBefore($group);

        $group = $this->getExecutableRequests($this->groups[$index]);
        $cnt = count($group);

        if ($cnt !== 0) {
            // Блокирую выполнение если нет времени на эту операцию
            if ((int)$groupTime <= 0) {
                // Если залочил то необходимо создать пустышки. Что-бы after callback отработал
                $this->createErrorResponse($group, 'Previous loop timed out');
            } elseif ($cnt === 1) {
                $this->curlOne($group[0], $groupTime);
            } elseif ($cnt > 1) {
                $this->curlMany($group, $groupTime);
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
            $responsesArray = $this->getResponses($query->getDependency());

            if (count($responsesArray) === count($query->getDependency())) {
                $query->runBeforeFunc($responsesArray, $alias);
            } else {
                // Если запрос не будет выполнен то необходимо добавить Response с ошибкой.
                $query->setExecutable(false);
                $needResponses = array_diff($aliases, array_keys($responsesArray));
                $this->createErrorResponse($alias, 'Not all depending on compliance. Incorrect queries: ' . implode(', ', $needResponses));
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
     * @return array
     */
    private function getResponses(array $aliases)
    {
        $result = [];
        foreach ($aliases as $alias) {
            // ответ есть и запрос корректен(нет ошибок)
            if (array_key_exists($alias, $this->responses)) {
                /** @var Response $response */
                $response = $this->responses[$alias];

                if (!$this->isOnlyCorrectResponses) {
                    $result[$alias] = $response;
                } elseif ($response->isCorrect()) {
                    $result[$alias] = $response;
                }
            }
        }
        return $result;
    }

    /**
     * @param string|array $alias
     * @param string $message
     */
    private function createErrorResponse($alias, $message)
    {
        if (is_array($alias)) {
            foreach ($alias as $item) {
                $this->createErrorResponse($item, $message);
            }
        }
        $response = $this->responses[$alias] = new Response();
        $response->setErrorMessage($message);
    }

//  CURL ---------------------------------------------------------------------------------------------------------------

    /**
     * @param string $alias
     * @param int $requestTime
     */
    private function curlOne($alias, $requestTime)
    {
        /** @var Query $query */
        $query = $this->queries[$alias];
        $channel = (new Executor)->initRequest($query, $requestTime);
        $response = $this->responses[$alias] = new Response();
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
     */
    private function curlMany(array $aliases, $requestTime)
    {
        $multiHandler = curl_multi_init();

        //создаем набор дескрипторов cURL
        $handlers = [];
        $executor = new Executor();
        foreach ($aliases as $alias) {
            $handlers[$alias] = $executor->initRequest($this->queries[$alias], $requestTime);
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

        $this->buildMultiResponse($handlers, $multiHandler);
        curl_multi_close($multiHandler);
    }

    /**
     * @author Valentin Plehanov (Takamura)
     * @param array $handlers
     * @param resource $multiHandler
     */
    private function buildMultiResponse(array $handlers, $multiHandler)
    {
        foreach ($handlers as $alias => $handler) {
            $response = $this->responses[$alias] = new Response();
            if (curl_errno($handler) !== 0) {
                $response->setErrorMessage(curl_error($handler));
            }
            $response->setHttpCode(curl_getinfo($handler, CURLINFO_HTTP_CODE));
            $data = curl_multi_getcontent($handler);
            $headerSize = curl_getinfo($handler, CURLINFO_HEADER_SIZE);
            $response->setHeaderAndData($data, $headerSize);
            /** @var Query $query */
            $query = $this->queries[$alias];
            if ($query->isDetailConnection()) {
                $response->setDetailConnection(curl_getinfo($handler));
            }
            // close current handler
            curl_multi_remove_handle($multiHandler, $handlers[$alias]);
        }
    }
}

