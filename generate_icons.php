<?php
function makeIcon($size) {
    $img = imagecreatetruecolor($size, $size);

    $bg    = imagecolorallocate($img, 10, 14, 20);       // #0A0E14
    $blue  = imagecolorallocate($img, 61, 124, 255);     // #3D7CFF
    $light = imagecolorallocate($img, 111, 161, 255);    // #6FA1FF
    $green = imagecolorallocate($img, 52, 211, 153);     // #34D399
    $white = imagecolorallocate($img, 234, 238, 246);    // #EAEEF6

    // Fond sombre
    imagefilledrectangle($img, 0, 0, $size, $size, $bg);

    // Cercle bleu en fond
    $margin = (int)($size * 0.05);
    imagefilledellipse($img, $size/2, $size/2, $size - $margin*2, $size - $margin*2, $blue);

    // Bouclier (polygone)
    $cx = $size / 2;
    $cy = $size / 2;
    $w  = $size * 0.44;
    $h  = $size * 0.50;
    $top = $cy - $h * 0.55;

    $shield = [
        (int)$cx,          (int)$top,
        (int)($cx + $w),   (int)($top + $h * 0.3),
        (int)($cx + $w),   (int)($top + $h * 0.6),
        (int)$cx,          (int)($top + $h),
        (int)($cx - $w),   (int)($top + $h * 0.6),
        (int)($cx - $w),   (int)($top + $h * 0.3),
    ];
    imagefilledpolygon($img, $shield, $white);

    // Coche verte (check)
    $lw = max(2, (int)($size * 0.025));
    $x1 = (int)($cx - $w * 0.35);
    $y1 = (int)($cy + $h * 0.02);
    $x2 = (int)($cx - $w * 0.05);
    $y2 = (int)($cy + $h * 0.28);
    $x3 = (int)($cx + $w * 0.42);
    $y3 = (int)($cy - $h * 0.18);

    for ($i = -$lw; $i <= $lw; $i++) {
        imageline($img, $x1, $y1 + $i, $x2, $y2 + $i, $green);
        imageline($img, $x2, $y2 + $i, $x3, $y3 + $i, $green);
    }

    $dir = __DIR__ . '/assets/icons/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    imagepng($img, $dir . 'icon-' . $size . '.png');
    imagedestroy($img);
    echo "✓ icon-{$size}.png créé\n";
}

makeIcon(192);
makeIcon(512);
echo "\nOK — supprime ce fichier.";
