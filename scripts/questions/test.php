<?php
{
    # include conf file
    require_once(dirname(__FILE__) . '/../../include/config.inc.php');

    ini_set('max_execution_time', 9990);

//    header('Content-type: image/png');

    $gdManager          = new CGdManager();
    $guessPlayerMapping = new CGuessPlayer();

//    $filePath = DIR_ROOT . "images/N'Golo Kanté.jpg";
//    $filePath = DIR_ROOT . "images/Mohamed Salah.jpg";
////    $filePath = DIR_ROOT . "images/Ryan Guno Babel.jpg";
////    $filePath = DIR_ROOT . "images/Rafael Márquez.jpg";
//
//    # resize and get base image data
//    $imageInfo = $gdManager->resizeImage($filePath);
//    $imageHeaders = $imageInfo;
//    $imageBasePath = $imageInfo['baseImagePath'];
//
//    # rectangle image |
//    $imPath = $gdManager->rectangleSlice($imageBasePath, $imageHeaders, 50);

    $image = imagecreatefrompng('/tmp/tmpFilesBSfWl');

    imagepng($image);
}

