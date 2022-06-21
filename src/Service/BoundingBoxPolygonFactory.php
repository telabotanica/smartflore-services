<?php

namespace App\Service;

use League\Geotools\Polygon\Polygon;

class BoundingBoxPolygonFactory
{
    public function createBoundingBoxPolygon(array $coords): Polygon
    {
        if (4 !== count($coords)) {
            throw new \Exception('missing coords: '.implode(',', $coords));
        }

        $polygon = new Polygon([
            [$coords[0], $coords[1]], // yx
            [$coords[2], $coords[3]], // y'x'
            [$coords[2], $coords[1]], // y'x
            [$coords[0], $coords[3]], // yx'
        ]);
        $polygon->setPrecision(2);

        return $polygon;
    }
}
