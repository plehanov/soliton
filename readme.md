Multi curl composer application.


Sample using
```$php

$queries = [
    'data0' => [
        'url' => 'http://test.server.dev/server.php?',
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
        'url' => 'http://test.server.dev/server.php?thread1=2', 
        'methodParams' => ['sleep' => 480000]
    ],
    'data1' => [
        'url' => 'http://test.server.dev/server.php',
        'methodType' => 'POST',
        'methodParams' => ['sleep' => 200000],
        'dependency' => ['data0', 'data2'],
        'beforeFunc' => $func,
        'afterFunc' => $funcA,
    ],
    'data3' => [
        'url' => 'http://test.server.dev/server.php', 
        'dependency' => ['data1'], 
        'methodParams' => ['sleep' => 50000]
    ],
    'data4' => [
        'url' => 'http://test.server.dev/server.php', 
        'dependency' => ['data3'], 
        'methodParams' => ['sleep' => 100000]
    ],
    'data5' => [
        'url' => 'http://test.server.dev/server.php', 
        'dependency' => ['data4'], 
        'methodParams' => ['sleep' => 200000]
    ],
];

$handler = new \Soliton\Soliton($query);

$data = $handler
    ->setResponses($responses)
    ->timeout(10000)
    ->get([], false);
    
var_dump(
    $data['data4'],
    $data['data5']
);
```

```
$query = [
    'test1' => [
        H::P_URL => 'http://test.server.dev/server.php?',
        H::P_METHOD => 'POST',
        H::P_CONNECTION => true,
        H::P_HEADER => true,
        H::P_GET_PARAMS => ['sleep' => 500000, 'data' => 'files'] //ms
    ],
    'test1a' => [
        H::P_URL => 'http://test.server.dev/server.php?',
        H::P_METHOD => 'POST',
        H::P_CONNECTION => true,
        H::P_HEADER => true,
        H::P_GET_PARAMS => ['sleep' => 500000, 'data' => 'files']
    ]
];

$handler = new \Soliton\Soliton($query);
$data = $handler
    ->setResponses($responses)
    ->timeout(10000)
    ->get([], false);


var_dump(
    $data['test1'],
    $data['test1a']
);
```
