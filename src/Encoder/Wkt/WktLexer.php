<?php

declare(strict_types=1);

/*
 * Copyright 2021 Michael Lucas.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Nasumilu\Spatial\Serializer\Encoder\Wkt;

use Doctrine\Common\Lexer\AbstractLexer;

use function \is_numeric;
use function \strtolower;

/**
 * Description of WktLexer
 *
 * @author Michael Lucas <mlucas@nasumilu.net>
 */
class WktLexer extends AbstractLexer
{

    public const T_NONE = 1;
    public const T_NUMERIC = 2;
    public const T_CLOSE_PARENTHESIS = 6;
    public const T_OPEN_PARENTHESIS = 7;
    public const T_COMMA = 8;
    public const T_EQUALS = 11;
    public const T_SEMICOLON = 50;
    public const T_SRID = 501;
    public const T_EMPTY = 500;
    public const T_DIMENSION = 502;
    public const T_GEOMETRY_TYPE = 600;

    protected function getType(&$value)
    {
        if (is_numeric($value)) {
            return self::T_NUMERIC;
        }
        switch (strtolower($value)) {
            case ',':
                return self::T_COMMA;
            case '(':
                return self::T_OPEN_PARENTHESIS;
            case ')':
                return self::T_CLOSE_PARENTHESIS;
            case 'point':
            case 'linestring':
            case 'polygon':
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                return self::T_GEOMETRY_TYPE;
            case 'srid':
                return self::T_SRID;
            case 'z':
            case 'm':
            case 'zm':
                return self::T_DIMENSION;
            case 'empty':
                return self::T_EMPTY;
            case '=':
                return self::T_EQUALS;
            case ';':
                return self::T_SEMICOLON;
            default:
                return self::T_NONE;
        }
    }

    protected function getCatchablePatterns()
    {
        return [
            'point|linestring|polygon|multipoint|multilinestring|multipolygon|geometrycollection',
            'empty',
            'zm|[a-z]+[a-ln-y]',
            'SRID|=|(|)|;',
            '[+-]?[0-9]+(?:[\.][0-9]+)?(?:e[+-]?[0-9]+)?'
        ];
    }

    /**
     * @return string[]
     */
    protected function getNonCatchablePatterns()
    {
        return ['\s+'];
    }

}
