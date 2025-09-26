<?php
header('Content-Type: image/jpeg');

$width = 300;
$height = 250;
$image = imagecreatetruecolor($width, $height);

$bg_color = imagecolorallocate($image, 240, 240, 240);
imagefill($image, 0, 0, $bg_color);

$text_color = imagecolorallocate($image, 180, 180, 180);
$text = "No Image Available";
$font = 5;

$text_width = imagefontwidth($font) * strlen($text);
$text_height = imagefontheight($font);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font, $x, $y, $text, $text_color);
imagejpeg($image);
imagedestroy($image);
?>