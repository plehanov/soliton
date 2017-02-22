<?php
/**
 * User: Valentin Plehanov (Takamura) valentin@plehanov.su
 * Date: 22.02.17
 * Time: 16:12
 */

namespace Soliton\Lib;


use Soliton\Response;

class Responses
{
    private $responses;

    public function __construct(&$responses)
    {
        $this->responses = &$responses;
    }

    /**
     * @param string|array $alias
     * @param string $message
     */
    public function createErrorResponse($alias, $message)
    {
        if (is_array($alias)) {
            foreach ($alias as $item) {
                $this->createErrorResponse($item, $message);
            }
        }
        $response = $this->responses[$alias] = new Response();
        $response->setErrorMessage($message);
    }

    /**
     * @param array $aliases
     * @param bool $isOnlyCorrect
     * @return array
     */
    public function getResponses(array $aliases, $isOnlyCorrect)
    {
        $result = [];
        foreach ($aliases as $alias) {
            // ответ есть и запрос корректен(нет ошибок)
            if (array_key_exists($alias, $this->responses)) {
                /** @var Response $response */
                $response = $this->responses[$alias];

                if (!$isOnlyCorrect) {
                    $result[$alias] = $response;
                } elseif ($response->isCorrect()) {
                    $result[$alias] = $response;
                }
            }
        }
        return $result;
    }
}