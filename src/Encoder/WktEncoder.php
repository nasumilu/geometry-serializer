<?php

declare(strict_types=1);

/*
 * Copyright 2021 mlucas.
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

namespace Nasumilu\Spatial\Serializer\Encoder;

use function \implode;
use function \rtrim;
use function \call_user_func;
use function \in_array;
use function \stripos;

use Nasumilu\Spatial\Serializer\Encoder\Wkt\WktLexer;
use Symfony\Component\Serializer\Encoder\{
    EncoderInterface,
    DecoderInterface
};

/**
 * Description of WktEncoder
 */
class WktEncoder implements EncoderInterface, DecoderInterface
{

    private WktLexer $lexer;

    /** The Well-Known Text format extension */
    public const WKT_FORMAT = 'wkt';
    public const EWKT_FORMAT = 'ewkt';
    public const FORMATS = [
        self::WKT_FORMAT,
        self::EWKT_FORMAT
    ];

    public function __construct()
    {
        $this->lexer = new WktLexer();
    }

    /**
     * {@inheritDoc}
     */
    public function decode(string $data, string $format, array $context = []): array
    {
        return $this->decodeGeometry($data);
    }

    /**
     * {@inheritDoc}
     */
    public function encode($data, string $format, array $context = array()): string
    {
        $wkt = sprintf('%s (%s)', $this->encodeType($data), $this->encodeGeometry($data));
        if ($format === self::EWKT_FORMAT && $data['crs']['srid'] !== -1) {
            $wkt = "SRID={$data['crs']['srid']};$wkt";
        }
        return $wkt;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDecoding(string $format): bool
    {
        return in_array($format, self::FORMATS, true);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsEncoding(string $format): bool
    {
        return $this->supportsDecoding($format);
    }

    public function decodeGeometry(string $value): array
    {
        $this->lexer->setInput($value);
        $this->lexer->moveNext();
        $srid = $this->decodeSrid();
        $type = $this->decodeGeometryType();
        $dimension = $this->decodeDimension();
        $data = array_merge([
            'type' => $type,
            'srid' => $srid,
            'dimension' => $dimension],
                $this->decodeCoordinates($type));
        $this->lexer->reset();
        return $data;
    }

    private function decodeSrid(): int
    {
        $srid = -1;
        if ($this->lexer->isNextToken(WktLexer::T_SRID)) {
            $this->match(WktLexer::T_SRID);
            $this->match(WktLexer::T_EQUALS);
            $this->match(WktLexer::T_NUMERIC);
            $srid = (int) $this->lexer->token['value'];
            $this->match(WktLexer::T_SEMICOLON);
        }
        return $srid;
    }

    private function decodeGeometryType(): string
    {
        $this->match(WktLexer::T_GEOMETRY_TYPE);
        return $this->lexer->token['value'];
    }

    private function decodeDimension(): array
    {
        $dimension = [
            'is3D' => false,
            'isMeasured' => false
        ];
        if ($this->lexer->isNextToken(WktLexer::T_DIMENSION)) {
            $this->match(WktLexer::T_DIMENSION);
            $d = $this->lexer->token['value'];
            $dimension['3d'] = false !== stripos($d, 'z');
            $dimension['measured'] = false !== stripos($d, 'm');
        }

        return $dimension;
    }

    private function decodeCoordinateSeq(): array
    {
        $values = [$this->decodeCoordinate()];
        while ($this->lexer->isNextToken(WktLexer::T_COMMA)) {
            $this->match(WktLexer::T_COMMA);
            $values[] = $this->decodeCoordinate();
        }
        return $values;
    }

    private function decodeCoordinate(): array
    {
        $values = [];
        while ($this->lexer->isNextToken(WktLexer::T_NUMERIC)) {
            $this->match($this->lexer->lookahead['type']);
            $value = (float) $this->lexer->token['value'];
            $values[] = $value;
        }
        return $values;
    }

    private function decodeCoordinates(string $type): array
    {
        $data = [];
        if (!$this->lexer->isNextToken(WktLexer::T_EMPTY)) {
            $data['coordinates'] = $this->{'decode' . $type}();
        } else {
            $this->match(WktLexer::T_EMPTY);
            $data['coordinates'] = [];
        }
        return $data;
    }

    /**
     * Encodes a normalized geometry object's coordinates as a WKT string
     * @param array $value
     * @return
     */
    public function encodeGeometry(array $value): string
    {
        return call_user_func([$this, 'encode' . $value['type']], $value['coordinates']);
    }

    /**
     * Encodes a normalized geometry object's type as a WKT string
     * @param array $value
     * @return string
     */
    public function encodeType(array $value): string
    {
        $wkt = strtoupper($value['type']) . ' ';
        if ($value['crs']['3d'] ?? false) {
            $wkt .= 'Z';
        }
        if ($value['crs']['measured'] ?? false) {
            $wkt .= 'M';
        }
        return rtrim($wkt);
    }

    private function decodePoint(): array
    {
        $this->match(WktLexer::T_OPEN_PARENTHESIS);
        $coordinates = $this->decodeCoordinate();
        $this->match(WktLexer::T_CLOSE_PARENTHESIS);
        return $coordinates;
    }

    /**
     * Encodes a normalized point object's coordinates as a WKT string.
     * @param array $coodinates
     * @return string
     */
    public function encodePoint(array $coodinates): string
    {
        return implode(' ', $coodinates);
    }

    /**
     * Encodes a normalized linestring object's coordinates as a WKT string.
     * @param array $coordinates
     * @return string
     */
    public function encodeLineString(array $coordinates): string
    {
        $wkt = '';
        foreach ($coordinates as $coordinate) {
            $wkt .= $this->encodePoint($coordinate) . ',';
        }
        return rtrim($wkt, ',');
    }

    private function decodeLineString(): array
    {
        $this->match(WktLexer::T_OPEN_PARENTHESIS);
        $coordinates = $this->decodeCoordinateSeq();
        $this->match(WktLexer::T_CLOSE_PARENTHESIS);
        return $coordinates;
    }

    private function match($token)
    {
        $lookaheadType = $this->lexer->lookahead['type'];
        if ($lookaheadType !== $token) {
            throw new \Exception('Syntax error near ' . $this->lexer->getLiteral($token) . print_r($this->lexer->peek(), true));
        }
        $this->lexer->moveNext();
    }

}
