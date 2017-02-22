#Multi Curl

[![Latest Stable Version](https://poser.pugx.org/plehanov/soliton/v/stable)](https://packagist.org/packages/plehanov/soliton)
[![Total Downloads](https://poser.pugx.org/plehanov/soliton/downloads)](https://packagist.org/packages/plehanov/soliton) [![License](https://poser.pugx.org/plehanov/soliton/license)](https://packagist.org/packages/plehanov/soliton)
[![Code Climate](https://codeclimate.com/github/plehanov/soliton/badges/gpa.svg)](https://codeclimate.com/github/plehanov/soliton)
[![Test Coverage](https://codeclimate.com/github/plehanov/soliton/badges/coverage.svg)](https://codeclimate.com/github/plehanov/soliton/coverage)
[![Issue Count](https://codeclimate.com/github/plehanov/soliton/badges/issue_count.svg)](https://codeclimate.com/github/plehanov/soliton)


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
php -S localhost:8080 help/server.php 
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
            H::URL => 'http://sample_server/section',
            H::METHOD => 'POST',
            H::GET_PARAMS => ['param1' => 400000],
            H::OPTIONS => [
                H::OPTIONS_POSTFIELDS => [
                    'param2' => 1
                ],
            ],
            H::FUNC_BEFORE => null,
        ],
        'response_2' => [
            H::URL => 'http://sample_server/test.php?param1=2', 
            H::GET_PARAMS => ['param2' => 480000]
        ],
        'response_1' => [
            H::URL => 'http://sample_server/section',
            H::METHOD => 'POST',
            H::GET_PARAMS => ['param1' => 200000],
            H::DEPENDENCY => ['response_0', 'response_2'],
            H::FUNC_BEFORE => $func_before,
            H::FUNC_AFTER => $func_after,
            H::OPTIONS => [
                CURLOPT_POSTFIELDS => [
                    'param2' => 1
                ],
            ],
        ],
        'response_3' => [
            H::URL => 'http://sample_server/test.php', 
            H::DEPENDENCY => ['response_1'], 
            H::GET_PARAMS => ['param1' => 50000]
        ],
        'response_4' => [
            H::URL => 'http://test.server.dev', 
            H::DEPENDENCY => ['response_3'], 
            H::GET_PARAMS => ['param1' => 100000]
        ],
        'response_5' => [
            H::URL => 'http://test.server.dev', 
            H::DEPENDENCY => ['response_4'], 
            H::GET_PARAMS => ['param1' => 200000]
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
                $method_params = [
                    'checksum' => $data->checksum,
                    'auth_token' => $data->auth_token,
                ];
                $query->addMethodParams($method_params);
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
            H::URL => 'https://server_test/api-signin?expand=owner,owner.account',
            H::CONNECTION => false,
            H::GET_PARAMS => [
                'email' => 'sample@gmail.com',
                'password' => 123,
                'checksum' => 1234567,
                'expand' => 'owner,account',
            ],
            H::OPTIONS => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ],
            H::FUNC_AFTER => [$after_func],
        ],
        'account' => [
            H::URL => 'https://server_test/v1/accounts/{id}',
            H::CONNECTION => true,
            H::DEPENDENCY => ['login'],
            H::GET_PARAMS => [
                'expand' => 'owners,companies',
                'company.is_active' => true,
            ],
            H::OPTIONS => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ],
            H::FUNC_BEFORE => [$before_account],
            H::FUNC_AFTER => [$after_func],
        ],
    
    ];
    
    $handler = new \Soliton\Soliton($query);
    $data = $handler
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
            H::URL => 'http://server/?',
            H::METHOD => 'POST',
            H::CONNECTION => true,
            H::HEADER => true,
            H::GET_PARAMS => ['param1' => 500000, 'param2' => 'files']
        ],
        'test2' => [
            H::URL => 'http://server/?',
            H::METHOD => 'POST',
            H::CONNECTION => true,
            H::HEADER => true,
            H::GET_PARAMS => ['param1' => 500000, 'param2' => 'files']
        ],
    ];
    
    $handler = new \Soliton\Soliton($query);
    
    $logResponse = new \Soliton\Response();
    $logResponse->setData(json_decode('{"checksum":"1234567","isActive":true,"expiresOn":"2016-01-16T02:21:51Z","owner":{"id":6,"href":"https://javabox.dev:9000/v1/owners/6","isHidden":false,"name":"Takamura","email":"sample@gmail.com","phone":"","account":{"id":1,"href":"https://javabox.dev:9000/v1/accounts/1","isHidden":false,"name":"Авто","owners":[{"href":"https://javabox.dev:9000/v1/owners/3"},{"href":"https://javabox.dev:9000/v1/owners/5"},{"href":"https://javabox.dev:9000/v1/owners/6"},{"href":"https://javabox.dev:9000/v1/owners/2"}],"companies":[{"href":"https://javabox.dev:9000/v1/companies/3"},{"href":"https://javabox.dev:9000/v1/companies/2"},{"href":"https://javabox.dev:9000/v1/companies/1"}],"clients":[]},"role":"PARTNER"},"auth_token":"4c9e-4062-98fc-ee1d97c52c13"}'));
    
    $responses = ['test3' => $logResponse];
    
    $data = $handler
        ->setResponses($responses)
        ->timeout(1000)
        ->get(['account']);
    // or
    $data = $handler
        ->setResponses($responses)
        ->timeout(10000)
        ->get([], false);
    
    var_dump($data['test1'], $data['test1a']);
```

## Author

* Plehanov Valentin <plehanov.v@gmail.com>

## License

* Copyright: Copyright (c) 2014
* License: Apache License, Version 2.0