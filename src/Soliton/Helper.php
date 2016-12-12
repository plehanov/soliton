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
        P_HEADER = 'loadingHeaders',
        P_CONNECTION = 'detailConnection',
        P_METHOD = 'methodType',
        P_DEPENDENCY = 'dependency',
        P_FUNC_BEFORE = 'beforeFunc',
        P_FUNC_AFTER = 'afterFunc',
        P_GET_PARAMS = 'methodParams',
        P_OPTIONS = 'options',
        P_FILES = 'files',
        P_OPTIONS_POSTFIELDS = CURLOPT_POSTFIELDS;

}