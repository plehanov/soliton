<?php
/**
 * User: Takamura
 * Date: 08.10.2014 16:02
 * Copyright: (c) 2014, Valentin Plehanov
 */

class QueryTest extends PHPUnit_Framework_TestCase
{
    public function testQuery()
    {
        $options = [
            'url' => 'http://test.local?',
            'methodType' => 'GET',
            'dependency' => ['A', 'B'],
            'executable' => true,
            'beforeFunc' => function () {},
            'afterFunc' => function () {},
            'methodParams' => [],
            'options' => [
                'opt1' => 'a',
                'opt2' => 'b',
            ]
        ];
        $query = new \Soliton\Query('test', $options);

        $this->assertEquals($query->isExecutable(), $options['executable'], '1 Query params error');
        $this->assertEquals($query->getMethodType(), strtolower($options['methodType']), '2 Query params error');
        $this->assertEquals($query->getDependency(), $options['dependency'], '3 Query params error');
        $this->assertEquals($query->getBeforeFunc(), [$options['beforeFunc']], '4 Query params error');
        $this->assertEquals($query->getAfterFunc(), [$options['afterFunc']], '5 Query params error');
        $this->assertEquals($query->getFullUrl(), $options['url'], '6 Query params error');

        $query->setUrl('http://test.local');
        $this->assertEquals($query->getFullUrl(), 'http://test.local', '7 Query params error');
        $query->setMethodParams(['param' => 100]);
        $this->assertEquals($query->getFullUrl(), 'http://test.local?param=100', '8 Query params error');
        $query->setUrl('http://test.local?');
        $this->assertEquals($query->getFullUrl(), 'http://test.local?&param=100', '9a Query params error');
        $this->assertEquals($query->getUrl(), 'http://test.local?', '9b Query params error');
        $query->setExecutable(false);
        $this->assertEquals($query->isExecutable(), false, '10 Query params error');
        $query->addMethodParams(['param2' => 200]);
        $this->assertEquals($query->getMethodParams(), ['param' => 100, 'param2' => 200], '11 Query params error');

        $this->assertEquals($query->getOptions(), $options['options'], '12 Query params error');
        $query->setOptions(['opt0' => 3]);
        $this->assertEquals($query->getOptions(), ['opt0' => 3], '13 Query params error');

        $query->setDetailConnection(true);
        $this->assertEquals($query->isDetailConnection(), true, '14 Query params error');

    }
}