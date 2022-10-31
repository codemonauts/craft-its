<?php

namespace codemonauts\its\helpers;

class ColorHelper
{
    /**
     * Increases or decreases the brightness of a color by a percentage of the current brightness.
     *
     * @param string $hexCode The hex code of the color.
     * @param float $adjustPercent A number between -1 and 1.
     *
     * @return string
     */
    public static function adjustBrightness(string $hexCode, float $adjustPercent): string
    {
        $rgb = self::hexToRgb($hexCode);

        foreach ($rgb as & $color) {
            $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
            $adjustAmount = ceil($adjustableLimit * $adjustPercent);

            $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
        }

        return implode($rgb);
    }

    public static function hexToRgb(string $hexCode): array
    {
        $hexCode = ltrim($hexCode, '#');

        if (strlen($hexCode) == 3) {
            $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
        }

        return array_map('hexdec', str_split($hexCode, 2));
    }

    public static function hexToHsl(string $hexCode): object
    {
        $rgb = self::hexToRgb($hexCode);

        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;

        $min = min($r, $g, $b);
        $max = max($r, $g, $b);

        // Lightness
        $l = ($min + $max) / 2;

        // Check for monochrome
        if ($min == $max) {
            // Monochrome
            $h = 0;
            $s = 0;
            $l = $l * 100;
        } else {
            // Saturation
            $s = $l > 0.5 ? ($max - $min) / (2 - $max - $min) : ($max - $min) / ($max + $min);

            // Hue
            if ($r == $max) {
                $h = ($g - $b) / ($max - $min);
            }
            if ($g == $max) {
                $h = 2 + ($b - $r) / ($max - $min);
            }
            if ($b == $max) {
                $h = 4 + ($r - $g) / ($max - $min);
            }

            // Convert to degrees/percent
            $h *= 60;

            if ($h < 0) {
                $h += 360;
            }

            $s *= 100;
            $l *= 100;
        }

        return (object)['hue' => (int)round($h), 'saturation' => (int)round($s), 'lightness' => (int)round($l)];
    }

    public static function isLight(string $hexCode): bool
    {
        $hsl = self::hexToHsl($hexCode);

        return $hsl->lightness > 100;
    }
}
