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
        P_URL = 'url',
        P_HEADER = 'loading_headers',
        P_CONNECTION = 'detail_connection',
        P_METHOD = 'method_type',
        P_DEPENDENCY = 'dependency',
        P_FUNC_BEFORE = 'before_func',
        P_FUNC_AFTER = 'after_func',
        P_GET_PARAMS = 'method_params',
        P_OPTIONS = 'options',
        P_FILES = 'files',
        P_OPTIONS_POSTFIELDS = CURLOPT_POSTFIELDS;

}