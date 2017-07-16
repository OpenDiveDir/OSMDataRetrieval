<?php

class Osm {

     /**
      * Segment a bounding box into sections of a maximum dimension provided.
      *
      * @param string $bbox
      *   A comma-separated pair of co-ordinates giving the south-west and
      *   north-east extremeties of the bounding box.
      *   For example: "-180,-90,180,90".
      * @param string $divisor
      *   A number indicating the maximum number of degrees longitude and
      *   latitude in each segment, for example: each segment may be a maximum
      *   of 10 degrees in each axis.
      *
      * @return string
      *   A space-separated collection of segments, each segment providing a
      *   pair of coordinates indicating the south-west and north-east
      *   extremities of the segment.
      */
     function osm_bbox_section($bbox, $divisor) {
        list($left, $bottom, $right, $top) = explode(',', $bbox);

        $bboxes = array();

        $a = $left;
        do {
            $b = $bottom;
            do {
                $subbox = array(
                    $a,
                    $b,
                    ($a + $divisor < $right) ? $a + $divisor : $right,
                    ($b + $divisor < $top)   ? $b + $divisor : $top,
                );
                $bboxes[] = implode(',', $subbox);

                $b += $divisor;
            } while ($b < $top);

            $a += $divisor;
        } while ($a < $right);

        return implode(' ', $bboxes);
    }
}
