<?php

class Osm {

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
