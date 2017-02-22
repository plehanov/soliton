<?php
/**
 * User: Takamura
 * Date: 08.10.2014 16:02
 * Copyright: (c) 2014, Valentin Plehanov
 */

class SolitonTest extends PHPUnit_Framework_TestCase
{

    protected $server_url = 'http://localhost:8080';

    public function testSoliton()
    {
        $func = function(\Soliton\Query $query, $responses) {
            // var_dump($responses);
        };
        $funcA = function(\Soliton\Response $response) {
            // $response->setErrorMessage('1');
        };

        $queries = [
            'data0' => [
                'url' => $this->server_url,
                'method_type' => 'POST',
                'method_params' => ['sleep' => 400000],
                'options' => [
                    CURLOPT_POSTFIELDS => [
                        'value' => 1
                    ],
                ],
                'before_func' => null,
            ],
            'data2' => [
                'url' => $this->server_url . '?thread1=2',
                'method_params' => ['sleep' => 480000]
            ],
            'data1' => [
                'url' => $this->server_url,
                'method_type' => 'POST',
                'method_params' => ['sleep' => 200000],
                'dependency' => ['data0', 'data2'],
                'before_func' => $func,
                'after_func' => $funcA,
            ],
            /*'data3' => [
                'url' => $this->server_url,
                'dependency' => ['data1'],
                'method_params' => ['sleep' => 50000]
            ],
            'data4' => [
                'url' => $this->server_url,
                'dependency' => ['data3'],
                'method_params' => ['sleep' => 100000]
            ],
            'data5' => [
                'url' => $this->server_url,
                'dependency' => ['data4'],
                'method_params' => ['sleep' => 200000]
            ],*/
        ];

        $handler = new \Soliton\Soliton($queries);
        $result = $handler->timeout(10000)->get([], false);

        $this->assertNotEmpty($result, '1 Soliton result is empty');
        $this->assertCount(count($queries), $result, '2 Soliton result is empty');
    }

    public function testExceptionLoop()
    {
        try {
            $queries = [
                'data4' => [
                    'url' => $this->server_url,
                    'dependency' => ['data5'],
                    'method_params' => ['sleep' => 100000]
                ],
                'data5' => [
                    'url' => $this->server_url,
                    'dependency' => ['data4'],
                    'method_params' => ['sleep' => 200000]
                ],
            ];

            $handler = new \Soliton\Soliton($queries);
            $handler->timeout(100)->get();
            $this->fail('Trouble with - Mistakes in dependencies (Loop requests etc.)');
        } catch (Exception $e) {
            $this->assertEquals(69, $e->getCode());
        }
    }

    public function testRequest()
    {
        $queries = [
            'data4' => [
                'url' => $this->server_url,
                'method_params' => ['sleep' => 1000],
                'detail_connection' => true,
            ]
        ];

        $handler = new \Soliton\Soliton($queries);
        $responses = $handler->get();

        $this->assertEquals(count($responses), 1, 'Not equal Responses.');
        $this->assertNotEmpty($responses['data4'], 'Empty Response');
        $this->assertInstanceOf('\Soliton\Response', $responses['data4'], 'Not correct class');
        /** @var \Soliton\Response $response */
        $response = $responses['data4'];
        $this->assertEquals((int)$response->getData(), (int)$queries['data4']['method_params']['sleep'], 'Not correct data');
    }

    public function testResponse()
    {
        $queries = [
            'data4' => [
                'url' => $this->server_url,
                'dependency' => [],
                'method_params' => ['sleep' => 100000],
                'detail_connection' => true,
            ],
            'data5' => [
                'url' => $this->server_url,
                'dependency' => ['data4'],
                'method_params' => ['sleep' => 200000],
            ],
            'data6' => [
                'url' => $this->server_url,
                'dependency' => ['data4'],
                'method_params' => ['sleep' => 200000],
                'detail_connection' => true,
            ],
        ];

        $response = new \Soliton\Response();

        $handler = new \Soliton\Soliton($queries);
        $data = $handler->setResponses(['data4' => $response])->get(['data5']);
        $this->assertCount(1, $data);

        $handler->clearResponses();
        $data2 = $handler->get();
        $this->assertCount(3, $data2);
    }
}