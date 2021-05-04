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
 * WktLexer provides lexical parsing ewkt and wkt geometry format.
 *
 * @author Michael Lucas <mlucas@nasumilu.net>
 */
class WktLexer extends AbstractLexer
{

    /** None or Unknown type */
    public const T_NONE = 1;
    /** Numeric type (float or integer)*/
    public const T_NUMERIC = 2;
    /** Closed parenthesis type */
    public const T_CLOSE_PARENTHESIS = 6;
    /** Open parenthesis type */
    public const T_OPEN_PARENTHESIS = 7;
    /** Comma type */
    public const T_COMMA = 8;
    /** Equals type */
    public const T_EQUALS = 11;
    /** Semicolon type */
    public const T_SEMICOLON = 50;
    /** "SRID" character set type */
    public const T_SRID = 501;
    /** "EMPTY" character set type */
    public const T_EMPTY = 500;
    /** "Z", "M", or "ZM" character set type */
    public const T_DIMENSION = 502;
    /** Wkt geometry type character set type (point, linestring ... geometrycollection) */
    public const T_GEOMETRY_TYPE = 600;

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
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
     * {@inheritDoc}
     */
    protected function getNonCatchablePatterns()
    {
        return ['\s+'];
    }

}
