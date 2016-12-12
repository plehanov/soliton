<?php
/**
 * User: Takamura
 * Date: 08.10.2014 16:02
 * Copyright: (c) 2014, Valentin Plehanov
 */

class ResponseTest extends PHPUnit_Framework_TestCase
{

    public function testResponse()
    {
        $response = new \Soliton\Response();

        $response->setData('sample');
        $this->assertEquals($response->getData(), 'sample', '1 Response params error');

        $response->setErrorMessage('error');
        $this->assertEquals($response->getErrorMessage(), 'error', '2 Response params error');

        $response->setDetailConnection('detail');
        $this->assertEquals($response->getDetailConnection(), 'detail', '3 Response params error');

        $this->assertEquals($response->isCorrect(), false, '4 Response params error');
    }
}