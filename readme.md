#Multi Curl

Allows parallel queries. Queries can be constructed in the form of a chain, with dependencies on the results of the previous ones.
Soliton itself divides into groups and processes requests.
It is possible to set the overall timeout on the whole process. He balances the distribution of time.

For example the result of the authorization passes the token to other requests.

## Install

```php
    include_once '../src/Soliton/Helper.php';
    include_once '../src/Soliton/Soliton.php';
```
## Testing

```php
php -S localhost:8080 sample/ 
phpunit
```



## Usage

###Sample One

```php
    use \Soliton\Helper as H;

    $func_before = function(\Soliton\Query $query, $responses, $alias) {
        var_dump($responses);
    };
    
    $func_after = function(\Soliton\Response $response) {
        $response->setErrorMessage('1');
    };
    
    $queries = [
        'response_0' => [
            'url' => 'http://sample_server/section',
            'methodType' => 'POST',
            'methodParams' => ['param1' => 400000],
            'options' => [
                CURLOPT_POSTFIELDS => [
                    'param2' => 1
                ],
            ],
            'beforeFunc' => null,
        ],
        'response_2' => [
            'url' => 'http://sample_server/test.php?param1=2', 
            'methodParams' => ['param2' => 480000]
        ],
        'response_1' => [
            'url' => 'http://sample_server/section',
            'methodType' => 'POST',
            'methodParams' => ['param1' => 200000],
            'dependency' => ['response_0', 'response_2'],
            'beforeFunc' => $func_before,
            'afterFunc' => $func_after,
        ],
        'response_3' => [
            'url' => 'http://sample_server/test.php', 
            'dependency' => ['response_1'], 
            'methodParams' => ['param1' => 50000]
        ],
        'response_4' => [
            'url' => 'http://test.server.dev/server.php', 
            'dependency' => ['response_3'], 
            'methodParams' => ['param1' => 100000]
        ],
        'response_5' => [
            'url' => 'http://test.server.dev/server.php', 
            'dependency' => ['response_4'], 
            'methodParams' => ['param1' => 200000]
        ]
    ];
```

###Sample Two

```php
    use \Soliton\Helper as H;
    
    $before_account = function(\Soliton\Query $query, $responses) {
        /** @var \Soliton\Response $response */
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
    
    $after_func = function (\Soliton\Response $response) {
        if ($response->isCorrect()) {
            $data = $response->getData();
            $response->setData(json_decode($data));
        }
    };
    
    $query = [
        'login' => [
            'url' => 'https://server_test/api-signin?expand=owner,owner.account',
            'detailConnection' => false,
            'methodParams' => [
                'email' => 'sample@gmail.com',
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
            'url' => 'https://server_test/v1/accounts/{id}',
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
            'beforeFunc' => [$before_account],
            'afterFunc' => [$after_func],
        ],
    
    ];
    
    $handler = new \Soliton\Soliton($query);
    $data = $handler
        /* ->setResponses(['login' => $logResponse]) */
        ->timeout(1000)
        ->get(['account']);
    
    /** @var \Soliton\Response $acc */
    $result = $data['account'];
    var_dump($result->getDetailConnection());
```

### Sample 3

```php
    use \Soliton\Helper as H;
    
    $query = [
        'test1' => [
            H::P_URL => 'http://server/server.php?',
            H::P_METHOD => 'POST',
            H::P_CONNECTION => true,
            H::P_HEADER => true,
            H::P_GET_PARAMS => ['param1' => 500000, 'param2' => 'files']
        ],
        'test2' => [
            H::P_URL => 'http://server/server.php?',
            H::P_METHOD => 'POST',
            H::P_CONNECTION => true,
            H::P_HEADER => true,
            H::P_GET_PARAMS => ['param1' => 500000, 'param2' => 'files']
        ],
    ];
    
    $handler = new \Soliton\Soliton($query);
    
    $logResponse = new \Soliton\Response();
    $logResponse->setData(json_decode('{"checksum":"1234567","isActive":true,"expiresOn":"2016-01-16T02:21:51Z","owner":{"id":6,"href":"https://javabox.dev:9000/v1/owners/6","isHidden":false,"name":"Takamura","email":"sample@gmail.com","phone":"","account":{"id":1,"href":"https://javabox.dev:9000/v1/accounts/1","isHidden":false,"name":"Авто","owners":[{"href":"https://javabox.dev:9000/v1/owners/3"},{"href":"https://javabox.dev:9000/v1/owners/5"},{"href":"https://javabox.dev:9000/v1/owners/6"},{"href":"https://javabox.dev:9000/v1/owners/2"}],"companies":[{"href":"https://javabox.dev:9000/v1/companies/3"},{"href":"https://javabox.dev:9000/v1/companies/2"},{"href":"https://javabox.dev:9000/v1/companies/1"}],"clients":[]},"role":"PARTNER"},"auth_token":"4c9e-4062-98fc-ee1d97c52c13"}'));
    
    $responses = ['test3' => $logResponse];
    $data = $handler->setResponses($responses)->timeout(1000)->get(['account']);
    // or
    $data = $handler->setResponses($responses)->timeout(10000)->get([], false);
    
    var_dump($data['test1'], $data['test1a']);
```

## Author

* Plehanov Valentin <plehanov.v@gmail.com>

## License

* Copyright: Copyright (c) 2014
* License: Apache License, Version 2.0