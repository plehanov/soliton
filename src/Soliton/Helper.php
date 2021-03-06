<?php
/**
 * User: Valentin Plehanov (Takamura) valentin@plehanov.su
 * Date: 19.02.15
 * Time: 17:12
 */

namespace Soliton;

/**
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
        HEADER = 'loadingHeaders',
        CONNECTION = 'detailConnection',
        METHOD = 'methodType',
        DEPENDENCY = 'dependency',
        FUNC_BEFORE = 'beforeFunc',
        FUNC_AFTER = 'afterFunc',
        GET_PARAMS = 'methodParams',
        OPTIONS = 'options',
        FILES = 'files',
        OPTIONS_POSTFIELDS = CURLOPT_POSTFIELDS;

}