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

namespace Nasumilu\Spatial\Serializer\Tests;

use function \file_get_contents;
use Symfony\Component\Serializer\Serializer;
use PHPUnit\Framework\TestCase;
use Nasumilu\Spatial\Serializer\{
    Normalizer\GeometryNormalizer,
    Encoder\WktEncoder
};
use Nasumilu\Spatial\Geometry\{
    Geometry,
    AbstractGeometryFactory,
    Point,
    LineString,
    Polygon,
    MultiPoint
};

/**
 * GeometrySerializerTest
 */
class GeometrySerializerTest extends TestCase
{

    private static Serializer $serializer;

    public static function setUpBeforeClass(): void
    {
        self::$serializer = new Serializer([new GeometryNormalizer()], [new WktEncoder()]);
    }

    /**
     * @dataProvider wktDataProvider
     */
    public function testSerializeGeometryToWkt(array $args, string $wkt, string $format)
    {
        $factory = $this->getMockForAbstractClass(AbstractGeometryFactory::class, [[
        'srid' => 4326,
        '3d' => true,
        'measured' => true
        ]]);
        $geometry = $factory->create($args);
        $serializer = self::$serializer->serialize($geometry, $format);
        $this->assertEquals($serializer, $wkt);
        $deserialize = self::$serializer->deserialize($serializer, Geometry::class, $format, ['factory' => $factory]);
        $this->assertEquals($geometry, $deserialize);
    }

    public function wktDataProvider()
    {
        $resource = __DIR__ . '/../vendor/nasumilu/geometry/tests/Resources/php/';
        $wktResource = __DIR__ . '/Resources/';
        $data = [];
        foreach ([Point::WKT_TYPE, LineString::WKT_TYPE, Polygon::WKT_TYPE, MultiPoint::WKT_TYPE] as $type) {
            foreach (WktEncoder::FORMATS as $format) {
                $data["$type-$format"] = [
                    require $resource . "$type.php",
                    file_get_contents($wktResource . "$format/$type.$format"),
                    $format];
            }
        }
        return $data;
    }

}
