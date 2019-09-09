<?php
    define("CAPTCHA_CHARS", "ABCDEFGHJKLMNPQRSTUVWXYZ23456789");
    define("CAPTCHA_LENGTH", 6);

    $CAPTCHA_FONTS = array(dirname(__FILE__) . "/../resources/acme.ttf", dirname(__FILE__) . "/../resources/ubuntu.ttf", dirname(__FILE__) . "/../resources/merriweather.ttf", dirname(__FILE__) . "/../resources/playfairdisplay.ttf");

//     Reference: https://code.tutsplus.com/tutorials/build-your-own-captcha-and-contact-form-in-php--net-5362

    function randstr($input, $strength = 10) {
        $input_length = strlen($input);
        $random_string = "";
        for($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
    
        return $random_string;
    }

    $image = imagecreatetruecolor(200, 50);

    if (function_exists("imageantialias"))
        imageantialias($image, true);

    $colors = [];

    $red = rand(125, 175);
    $green = rand(125, 175);
    $blue = rand(125, 175);

    for($i = 0; $i < 5; $i++)
        $colors[] = imagecolorallocate($image, $red - 20 * $i, $green - 20 * $i, $blue - 20 * $i);

    imagefill($image, 0, 0, $colors[0]);

    for($i = 0; $i < 10; $i++) {
        imagesetthickness($image, rand(2, 10));
        $line_color = $colors[rand(1, 4)];
        imagerectangle($image, rand(-10, 190), rand(-10, 10), rand(-10, 190), rand(40, 60), $line_color);
    }

    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    $textcolors = [$black, $white];

    $captcha_string = randstr(CAPTCHA_CHARS, CAPTCHA_LENGTH);

    $_SESSION["captcha_text"] = $captcha_string;

    for($i = 0; $i < CAPTCHA_LENGTH; $i++) {
        $letter_space = 170 / CAPTCHA_LENGTH;
        $initial = 15;
        
        imagettftext($image, 24, rand(-15, 15), $initial + $i * $letter_space, rand(25, 45), $textcolors[rand(0, 1)], $CAPTCHA_FONTS[array_rand($CAPTCHA_FONTS)], $captcha_string[$i]);
    }

    header("Content-type: image/png");
    imagepng($image);
    imagedestroy($image);
?>