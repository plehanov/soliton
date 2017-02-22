<?php
/**
 * User: Takamura
 * Date: 08.10.2014 16:02
 * Copyright: (c) 2014, Valentin Plehanov
 */

use \Soliton\Helper as H;

class SolitonTest extends PHPUnit_Framework_TestCase
{

    protected $serverUrl = 'http://localhost:8080';

    public function testSoliton()
    {
//        $func = function(\Soliton\Query $query, $responses) {
//            // var_dump($responses);
//        };
//        $funcA = function(\Soliton\Response $response) {
//            // $response->setErrorMessage('1');
//        };

        $queries = [
            'data0' => [
                H::URL => $this->serverUrl,
                H::METHOD => 'POST',
                H::GET_PARAMS => ['sleep' => 400000],
                H::OPTIONS => [
                    CURLOPT_POSTFIELDS => [
                        'value' => 1
                    ],
                ],
                'before_func' => null,
            ],
            'data2' => [
                H::URL => $this->serverUrl . '?thread1=2',
                'method_params' => ['sleep' => 480000]
            ],
            'data1' => [
                H::URL => $this->serverUrl,
                H::METHOD => 'POST',
                H::GET_PARAMS => ['sleep' => 200000],
                H::DEPENDENCY => ['data0', 'data2'],
//                H::FUNC_BEFORE => $func,
//                H::FUNC_AFTER => $funcA,
            ],
            /*'data3' => [
                H::URL => $this->serverUrl,
                H::DEPENDENCY => ['data1'],
                H::GET_PARAMS => ['sleep' => 50000]
            ],
            'data4' => [
                H::URL => $this->serverUrl,
                H::DEPENDENCY => ['data3'],
                H::GET_PARAMS => ['sleep' => 100000]
            ],
            'data5' => [
                H::URL => $this->serverUrl,
                H::DEPENDENCY => ['data4'],
                H::GET_PARAMS => ['sleep' => 200000]
            ],*/
        ];

        $handler = new \Soliton\Soliton($queries);
        $result = $handler->timeout(10000)->get([]);

        $this->assertNotEmpty($result, '1 Soliton result is empty');
        $this->assertCount(count($queries), $result, '2 Soliton result is empty');
    }

    public function testExceptionLoop()
    {
        try {
            $queries = [
                'data4' => [
                    H::URL => $this->serverUrl,
                    H::DEPENDENCY => ['data5'],
                    H::GET_PARAMS => ['sleep' => 100000]
                ],
                'data5' => [
                    H::URL => $this->serverUrl,
                    H::DEPENDENCY => ['data4'],
                    H::GET_PARAMS => ['sleep' => 200000]
                ],
            ];

            $handler = new \Soliton\Soliton($queries);
            $handler->timeout(100)->onlyCorrect()->get();
            $this->fail('Trouble with - Mistakes in dependencies (Loop requests etc.)');
        } catch (Exception $e) {
            $this->assertEquals(69, $e->getCode());
        }
    }

    public function testRequest()
    {
        $queries = [
            'data4' => [
                H::URL => $this->serverUrl,
                H::GET_PARAMS => ['sleep' => 1000],
                H::CONNECTION => true,
            ]
        ];

        $handler = new \Soliton\Soliton($queries);
        $responses = $handler->onlyCorrect()->get();

        $this->assertEquals(count($responses), 1, 'Not equal Responses.');
        $this->assertNotEmpty($responses['data4'], 'Empty Response');
        $this->assertInstanceOf('\Soliton\Response', $responses['data4'], 'Not correct class');
        /** @var \Soliton\Response $response */
        $response = $responses['data4'];
        $this->assertEquals((int)$response->getData(), (int)$queries['data4'][H::GET_PARAMS]['sleep'], 'Not correct data');
    }

    public function testResponse()
    {
        $queries = [
            'data4' => [
                H::URL => $this->serverUrl,
                H::DEPENDENCY => [],
                H::GET_PARAMS => ['sleep' => 100000],
                H::CONNECTION => true,
            ],
            'data5' => [
                H::URL => $this->serverUrl,
                H::DEPENDENCY => ['data4'],
                H::GET_PARAMS => ['sleep' => 200000],
            ],
            'data6' => [
                H::URL => $this->serverUrl,
                H::DEPENDENCY => ['data4'],
                H::GET_PARAMS => ['sleep' => 200000],
                H::CONNECTION => true,
            ],
        ];

        $response = new \Soliton\Response();

        $handler = new \Soliton\Soliton($queries);
        $data = $handler->setResponses(['data4' => $response])->onlyCorrect()->get(['data5']);
        $this->assertCount(1, $data);

        $handler->clearResponses();
        $data2 = $handler->onlyCorrect()->get();
        $this->assertCount(3, $data2);
    }
}