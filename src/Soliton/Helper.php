<?php namespace Soliton;

/**
 * Created by PhpStorm.
 * User: takamura
 * Date: 19.02.15
 * Time: 17:12
 *
 * Class Helper
 * @package Soliton
 */
class Helper
{

    /**
     * Константы полей которые можно использовать в запросах к солитону
     */
    const
        URL = 'url',
        HEADER = 'loading_headers',
        CONNECTION = 'detail_connection',
        METHOD = 'method_type',
        DEPENDENCY = 'dependency',
        FUNC_BEFORE = 'before_func',
        FUNC_AFTER = 'after_func',
        GET_PARAMS = 'method_params',
        OPTIONS = 'options',
        FILES = 'files',
        OPTIONS_POSTFIELDS = CURLOPT_POSTFIELDS;

}