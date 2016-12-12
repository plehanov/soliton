<?php
/**
 * Created by PhpStorm.
 * User: takamura
 * Date: 14.01.15
 * Time: 13:45
 */

include_once '../common/head.php';
include_once '../src/Soliton/Helper.php';
include_once '../src/Soliton/Soliton.php';

use \Soliton\Helper as H;
/*
------------------------------------------------------------------------------------------------- Sample
$func = function(\Soliton\Query $query, $responses, $alias) {
    // dd($responses);
};

$funcA = function(\Soliton\Response $response) {
    // $response->setErrorMessage('1');
};

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
    'data2' => ['url' => 'http://test.server.dev/server.php?thread1=2', 'methodParams' => ['sleep' => 480000]],
    'data1' => [
        'url' => 'http://test.server.dev/server.php',
        'methodType' => 'POST',
        'methodParams' => ['sleep' => 200000],
        'dependency' => ['data0', 'data2'],
        'beforeFunc' => $func,
        'afterFunc' => $funcA,
    ],
    'data3' => ['url' => 'http://test.server.dev/server.php', 'dependency' => ['data1'], 'methodParams' => ['sleep' => 50000]],
    'data4' => ['url' => 'http://test.server.dev/server.php', 'dependency' => ['data3'], 'methodParams' => ['sleep' => 100000]],
    'data5' => ['url' => 'http://test.server.dev/server.php', 'dependency' => ['data4'], 'methodParams' => ['sleep' => 200000]],
];*/

/*
---------------------------------------------------------------------------- Sample
$beforeAccount = function(\Soliton\Query $query, $responses) {
    /** @var \Soliton\Response $response * /
    $response = $responses['login'];
    if ($response->isCorrect()) {
        $data = $response->getData();
        if ($data->owner->account->id) {
            $url = str_replace('{id}', $data->owner->account->id, $query->getUrl());
            $query->setUrl($url);
            $methodParams = [
                'checksum' => $data->checksum,
                'auth_token' => $data->auth_token,
            ];
            $query->addMethodParams($methodParams);
        } else {
            $query->setExecutable(false);
        }
    } else {
        $query->setExecutable(false);
    }
};

$afterFunc = function (\Soliton\Response $response) {
    if ($response->isCorrect()) {
        $data = $response->getData();
        $response->setData(json_decode($data));
    }
};

//$logResponse = new \Soliton\Response();
//$logResponse->setData(json_decode('{"checksum":"1234567","isActive":true,"expiresOn":"2016-01-16T02:21:51Z","owner":{"id":6,"href":"https://javabox.dev:9000/v1/owners/6","isHidden":false,"name":"Takamura","email":"plehanov.v@gmail.com","phone":"","account":{"id":1,"href":"https://javabox.dev:9000/v1/accounts/1","isHidden":false,"name":"Автогарант","owners":[{"href":"https://javabox.dev:9000/v1/owners/3"},{"href":"https://javabox.dev:9000/v1/owners/5"},{"href":"https://javabox.dev:9000/v1/owners/6"},{"href":"https://javabox.dev:9000/v1/owners/2"}],"companies":[{"href":"https://javabox.dev:9000/v1/companies/3"},{"href":"https://javabox.dev:9000/v1/companies/2"},{"href":"https://javabox.dev:9000/v1/companies/1"}],"clients":[]},"role":"PARTNER"},"auth_token":"a3f833a1-4c9e-4062-98fc-ee1d97c52c13"}'));

$query = [
    'login' => [
        'url' => 'https://server.angara.dev/api-signin?expand=owner,owner.account',
        'detailConnection' => false,
        'methodParams' => [
            'email' => 'plehanov.v@gmail.com',
            'password' => 123,
            'checksum' => 1234567,
            'expand' => 'owner,account',
        ],
        'options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ],
        'afterFunc' => [$afterFunc],
    ],
    'account' => [
        'url' => 'https://server.angara.dev/v1/accounts/{id}',
        'detailConnection' => true,
        'dependency' => ['login'],
        'methodParams' => [
            'expand' => 'owners,companies',
            'company.is_active' => true,
        ],
        'options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ],
        'beforeFunc' => $beforeAccount,
        'afterFunc' => [$afterFunc],
    ],

];

$handler = new \Soliton\Soliton($query);
$data = $handler
    //->setResponses(['login' => $logResponse])
    ->timeout(1000)
    ->get(['account']);

/** @var \Soliton\Response $acc * /
$acc = $data['account'];
dd($acc->getDetailConnection());
*/

/* --------------------------------------------------------------------------------- Sample */
$query = [
    'test1' => [
        H::P_URL => 'http://test.server.dev/server.php?',
        H::P_METHOD => 'POST',
        H::P_CONNECTION => true,
//        H::P_HEADER => true,
        H::P_GET_PARAMS => ['sleep' => 500000, 'data' => 'files'] //ms
    ],
    'test1a' => [
        H::P_URL => 'http://test.server.dev/server.php?',
        H::P_METHOD => 'POST',
        H::P_CONNECTION => true,
//        H::P_HEADER => true,
        H::P_GET_PARAMS => ['sleep' => 500000, 'data' => 'files']
    ],
   /* 'test2a' => [
        'url' => 'https://javabox.dev:9000/api-signin',
        'dependency' => ['test1'],
    ],
    'test2b' => [
        'url' => 'https://javabox.dev:9000/api-signin',
        'dependency' => ['test1'],
    ],
    'test3a' => [
        'url' => 'https://javabox.dev:9000/api-signin',
        'dependency' => ['test2a'],
    ],
    'test3b' => [
        'url' => 'https://javabox.dev:9000/api-signin',
        'dependency' => ['test2a'],
    ],

    'test4a' => [
        'url' => 'https://javabox.dev:9000/api-signin',
        'dependency' => ['test2b'],
    ],
    'test4b' => [
        'url' => 'https://javabox.dev:9000/api-signin',
        'dependency' => ['test2b'],
    ],*/
];


$handler = new \Soliton\Soliton($query);

//$responses = ['login' => $logResponse];
//$data = $handler->setResponses($responses)->timeout(1000)->get(['account']);

$responses = [
//    'test1' => $logResponse,
//    'test2a' => $logResponse,
//    'test2b' => $logResponse
];
$data = $handler
    ->setResponses($responses)
    ->timeout(10000)
    ->get([], false);


dd(
    $data['test1'],
    $data['test1a']
);


/*
?>
    <form action="index.php" enctype="multipart/form-data" method="post">
        <input type="file" name="cats"/>
        <input type="submit"/>
    </form>
<?php */

/*$beforeAccount = function (\Soliton\Query $query, $responses) {
    \Soliton\Helper::eventBeforeFile($query, $_FILES);
};*/

/*
$queries = [
    'data0' => [
        \Soliton\Helper::P_URL => 'http://test2.server.dev/server.php?',
        \Soliton\Helper::P_METHOD => 'POST',
        \Soliton\Helper::P_CONNECTION => true,
        \Soliton\Helper::P_HEADER => true,
        \Soliton\Helper::P_GET_PARAMS => ['sleep' => 4, 'data' => 'files'],
        \Soliton\Helper::P_OPTIONS => [
            \Soliton\Helper::P_OPTIONS_POSTFIELDS => [
                'value' => 1
            ],
        ],
        //Soliton\Helper::P_FUNC_BEFORE => $beforeAccount,
    ],
    'data1' => [
        'url' => 'http://test.server.dev/server.php?',
        'methodType' => 'POST',
//        'detailConnection' => true,
        'loadingHeaders' => true,
        'methodParams' => ['sleep' => 6, 'data' => 'files'],
        'options' => [
            CURLOPT_POSTFIELDS => [
                'value' => 1
            ],
        ],
        //'beforeFunc' => $beforeAccount,
    ],
];

$handler = new \Soliton\Soliton($queries);

$data = $handler
    ->timeout(100000)
    ->get([], false);

 dd($data['data0']->isCorrect());

*/