<?php
/**
 * User: Takamura
 * Date: 08.10.2014 16:02
 * Copyright: (c) 2014, Valentin Plehanov
 */

class SolitonTest extends PHPUnit_Framework_TestCase
{

    public function testSoliton()
    {
        $func = function(\Soliton\Query $query, $responses) {
            // dd($responses);
        };
        $funcA = function(\Soliton\Response $response) {
            // $response->setErrorMessage('1');
        };

        $url = 'http://localhost:8080';
        $queries = [
            'data0' => [
                'url' => $url . '/server.php?',
                'methodType' => 'POST',
                'methodParams' => ['sleep' => 400000],
                'options' => [
                    CURLOPT_POSTFIELDS => [
                        'value' => 1
                    ],
                ],
                'beforeFunc' => null,
            ],
            'data2' => [
                'url' => $url . '/server.php?thread1=2',
                'methodParams' => ['sleep' => 480000]
            ],
            'data1' => [
                'url' => $url . '/server.php',
                'methodType' => 'POST',
                'methodParams' => ['sleep' => 200000],
                'dependency' => ['data0', 'data2'],
                'beforeFunc' => $func,
                'afterFunc' => $funcA,
            ],
            /*'data3' => [
                'url' => $url . '/server.php',
                'dependency' => ['data1'],
                'methodParams' => ['sleep' => 50000]
            ],
            'data4' => [
                'url' => $url . '/server.php',
                'dependency' => ['data3'],
                'methodParams' => ['sleep' => 100000]
            ],
            'data5' => [
                'url' => $url . '/server.php',
                'dependency' => ['data4'],
                'methodParams' => ['sleep' => 200000]
            ],*/
        ];

        $handler = new \Soliton\Soliton($queries);
        $result = $handler->timeout(10000)->get([], false);

        $this->assertNotEmpty($result, '1 Soliton result is empty');
        $this->assertCount(count($queries), $result, '2 Soliton result is empty');
    }

    public function testExceptionTime()
    {
        try {
            $url = 'http://localhost:8080';
            $queries = [
                'data2' => [
                    'url' => $url . '/server.php?thread1=2',
                    'methodParams' => ['sleep' => 480000]
                ],
            ];

            $handler = new \Soliton\Soliton($queries, 0, 0);
            $this->$handler->get();
            $this->fail('Trouble with - "Total time" or "percent loop" mast not be zero.');
        } catch (Exception $e) {
            $this->assertEquals(4096, $e->getCode());
            // $this->assertEquals('Object of class Soliton\Soliton could not be converted to string', $e->getMessage());
        }
    }

    public function testExceptionLoop()
    {
        try {
            $url = 'http://localhost:8080';
            $queries = [
                'data4' => [
                    'url' => $url . '/server.php',
                    'dependency' => ['data5'],
                    'methodParams' => ['sleep' => 100000]
                ],
                'data5' => [
                    'url' => $url . '/server.php',
                    'dependency' => ['data4'],
                    'methodParams' => ['sleep' => 200000]
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
        $url = 'http://localhost:8080';
        $queries = [
            'data4' => [
                'url' => $url . '/server.php',
                'methodParams' => ['sleep' => 1000],
                'detailConnection' => true,
            ]
        ];

        $handler = new \Soliton\Soliton($queries);
        $responses = $handler->get();

        $this->assertEquals(count($responses), 1, 'Not equal Responses.');
        $this->assertNotEmpty($responses['data4'], 'Empty Response');
        $this->assertInstanceOf('\Soliton\Response', $responses['data4'], 'Not correct class');
        /** @var \Soliton\Response $response */
        $response = $responses['data4'];
        $this->assertEquals((int)$response->getData(), (int)$queries['data4']['methodParams']['sleep'], 'Not correct data');
    }

    public function testResponse()
    {
        $url = 'http://localhost:8080';
        $queries = [
            'data4' => [
                'url' => $url . '/server.php',
                'dependency' => [],
                'methodParams' => ['sleep' => 100000],
                'detailConnection' => true,
            ],
            'data5' => [
                'url' => $url . '/server.php',
                'dependency' => ['data4'],
                'methodParams' => ['sleep' => 200000],
            ],
            'data6' => [
                'url' => $url . '/server.php',
                'dependency' => ['data4'],
                'methodParams' => ['sleep' => 200000],
                'detailConnection' => true,
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