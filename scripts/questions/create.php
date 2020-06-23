<?php
{
    /**
     * Get images and process
     * Save images in mongo
     * Upsert question
     */

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    ini_set('max_execution_time', 0);
//    set_time_limit ( int $seconds )

    # include conf file
    require_once(dirname(__FILE__) . '/../../include/config.inc.php');

//    header('Content-type: image/png');

    $gdManager          = new CGdManager();
    $guessPlayerMapping = new CGuessPlayer();


    $images = $gdManager->getDirFiles(DIR_ROOT . 'images');

    foreach ($images as $image) {
        $filePath = DIR_ROOT . "/images/" . $image;

        # resize and get base image data
        $imageInfo = $gdManager->resizeImage($filePath);
        $imageHeaders = $imageInfo;
        $imageBasePath = $imageInfo['baseImagePath'];

        $processedImagesPaths = [];

        /**
         * Negate START
         */
        $processedImagesPaths['negateImage'] = [];

        # negate image
        $negateImagePath = $gdManager->negateImage($imageBasePath, $imageHeaders);
        $processedImagesPaths['negateImage']['1'] = $negateImagePath;

        /**
         * Negate END
         */

        /**
         * pixelate START
         */
        $processedImagesPaths['pixelateImage'] = [];

        # pixelate image | level 1
        $pixelateImagePath10 = $gdManager->pixelateImage($imageBasePath, $imageHeaders,10);
        $processedImagesPaths['pixelateImage']['1'] = $pixelateImagePath10;

        # pixelate image | level 2
        $pixelateImagePath20 = $gdManager->pixelateImage($imageBasePath, $imageHeaders,15);
        $processedImagesPaths['pixelateImage']['2'] = $pixelateImagePath20;

        # pixelate image | level 3
        $pixelateImagePath30 = $gdManager->pixelateImage($imageBasePath, $imageHeaders,20);
        $processedImagesPaths['pixelateImage']['3'] = $pixelateImagePath30;

        /**
         * pixelate END
         */

        /**
         * Horizontal lines START
         */
        $processedImagesPaths['horizontalLinesImage'] = [];

        # add horizontal lines to image | level 1
        $horizontalImagePath10 = $gdManager->addHorizontalLinesToImage($imageBasePath, $imageHeaders, 50, 50);
        $processedImagesPaths['horizontalLinesImage']['1'] = $horizontalImagePath10;

        # add horizontal lines to image | level 2
        $horizontalImagePath20 = $gdManager->addHorizontalLinesToImage($imageBasePath, $imageHeaders, 75, 50);
        $processedImagesPaths['horizontalLinesImage']['2'] = $horizontalImagePath20;

        # add horizontal lines to image | level 3
        $horizontalImagePath30 = $gdManager->addHorizontalLinesToImage($imageBasePath, $imageHeaders, 100, 50);
        $processedImagesPaths['horizontalLinesImage']['3'] = $horizontalImagePath30;

        /**
         * Horizontal lines END
         */

        /**
         * Vertical lines START
         */
        $processedImagesPaths['verticalLinesImage'] = [];

        # add horizontal lines to image | level 1
        $verticalLinesImagePath10 = $gdManager->addVerticalLinesToImage($imageBasePath, $imageHeaders,50, 50);
        $processedImagesPaths['verticalLinesImage']['1'] = $verticalLinesImagePath10;

        # add horizontal lines to image | level 2
        $verticalLinesImagePath20 = $gdManager->addVerticalLinesToImage($imageBasePath, $imageHeaders, 75, 50);
        $processedImagesPaths['verticalLinesImage']['2'] = $verticalLinesImagePath20;

        # add horizontal lines to image | level 3
        $verticalLinesImagePath30 = $gdManager->addVerticalLinesToImage($imageBasePath, $imageHeaders, 100, 50);
        $processedImagesPaths['verticalLinesImage']['3'] = $verticalLinesImagePath30;

        /**
         * Vertical lines END
         */

        /**
         * Rectangle image START
         */
        $processedImagesPaths['rectangleImage'] = [];

        # rectangle image |
        $rectangleImagePath10 = $gdManager->addRectangleToImage($imageBasePath, $imageHeaders, 50, 100);
        $processedImagesPaths['rectangleImage']['1'] = $rectangleImagePath10;

        # rectangle image |
        $rectangleImagePath20 = $gdManager->addRectangleToImage($imageBasePath, $imageHeaders, 75, 100);
        $processedImagesPaths['rectangleImage']['2'] = $rectangleImagePath20;

        # rectangle image |
        $rectangleImagePath30 = $gdManager->addRectangleToImage($imageBasePath, $imageHeaders, 100, 100);
        $processedImagesPaths['rectangleImage']['3'] = $rectangleImagePath30;

        /**
         * Rectangle image END
         */

        /**
         * Circle image START
         */
        $processedImagesPaths['circleImage'] = [];

        # add circles to image
        $circletImagePath1 = $gdManager->addCirclesToImage($imageBasePath, $imageHeaders, 1);
        $processedImagesPaths['circleImage']['1'] = $circletImagePath1;

        # add circles to image
        $circletImagePath05 = $gdManager->addCirclesToImage($imageBasePath, $imageHeaders, 0.5);
        $processedImagesPaths['circleImage']['2'] = $circletImagePath05;

        # add circles to image
        $circletImagePath01 = $gdManager->addCirclesToImage($imageBasePath, $imageHeaders, 0.1);
        $processedImagesPaths['circleImage']['3'] = $circletImagePath01;

        /**
         * Circle image END
         */


        /**
         * Horizontal Slice image START
         */
        $processedImagesPaths['horizontalSliceImage'] = [];

        # horizontal slice image
        $horizontalSlicedImagePath50 = $gdManager->horizontalSlice($imageBasePath, $imageHeaders, 75);
        $processedImagesPaths['horizontalSliceImage']['1'] = $horizontalSlicedImagePath50;

        # horizontal slice image
        $horizontalSlicedImagePath25 = $gdManager->horizontalSlice($imageBasePath, $imageHeaders, 50);
        $processedImagesPaths['horizontalSliceImage']['2'] = $horizontalSlicedImagePath25;

        # horizontal slice image
        $horizontalSlicedImagePath10 = $gdManager->horizontalSlice($imageBasePath, $imageHeaders, 25);
        $processedImagesPaths['horizontalSliceImage']['3'] = $horizontalSlicedImagePath10;

        /**
         * Slice image END
         */

        /**
         * Vertical Slice image START
         */
        $processedImagesPaths['verticalSliceImage'] = [];

        # vertical slice image
        $verticalSlicedImagePath50 = $gdManager->verticalSlice($imageBasePath, $imageHeaders, 75);
        $processedImagesPaths['verticalSliceImage']['1'] = $verticalSlicedImagePath50;

        # vertical slice image
        $verticalSlicedImagePath25 = $gdManager->verticalSlice($imageBasePath, $imageHeaders, 50);
        $processedImagesPaths['verticalSliceImage']['2'] = $verticalSlicedImagePath25;

        # vertical slice image
        $verticalSlicedImagePath10 = $gdManager->verticalSlice($imageBasePath, $imageHeaders, 25);
        $processedImagesPaths['verticalSliceImage']['3'] = $verticalSlicedImagePath10;

        /**
         * Vertical Slice image END
         */

        /**
         * Rectangle Slice image START
         */
        $processedImagesPaths['rectangleSliceImage'] = [];

        # rectangle slice image
        $rectangleSliceImagePath50 = $gdManager->rectangleSlice($imageBasePath, $imageHeaders, 150);
        $processedImagesPaths['rectangleSliceImage']['1'] = $rectangleSliceImagePath50;

        # rectangle slice image
        $rectangleSliceImagePath25 = $gdManager->rectangleSlice($imageBasePath, $imageHeaders, 100);
        $processedImagesPaths['rectangleSliceImage']['2'] = $rectangleSliceImagePath25;

        # rectangle slice image
        $rectangleSliceImagePath10 = $gdManager->rectangleSlice($imageBasePath, $imageHeaders, 50);
        $processedImagesPaths['rectangleSliceImage']['3'] = $rectangleSliceImagePath10;

        /**
         * Rectangle Slice image END
         */

        saveImages($imageHeaders, $processedImagesPaths);

    }


}

function saveImages($imageHeaders, $processedImagesPaths) {
    $ghManager          = new CGdManager();
    $guessPlayerMapping = new CGuessPlayer();

    # save base image
    $baseImageUrl = $ghManager->storeImageViaCurl($imageHeaders['baseImagePath'], $imageHeaders);
    $playerName = substr($imageHeaders['fileName'], 0, -4);

    $shapes = [];
    foreach ($processedImagesPaths as $shape => $processedImagePaths) {
        foreach ($processedImagePaths as $level => $path) {
            $imageUrl = $ghManager->storeImageViaCurl($path, $imageHeaders);
            $shapes[] = [
                'type'      => $shape,
                'level'     => $level,
                'imageUrl'  => $imageUrl
            ];
        }
    }

    $bulkData[] = [
        'updateOne' => [
            [
                'name' => $playerName
            ],
            [
                '$set' => [
                    "level"         => "1",
                    "name"          => $playerName,
                    "imageUrl"      => $baseImageUrl,
                    "description"   => $imageHeaders['description'],
                    'shapes'        => $shapes
                ]
            ],
            [
                'upsert' => true
            ]
        ]
    ];

    $result = $guessPlayerMapping->bulkWrite('questions', $bulkData);
    CHelperManager::printBulkWriteResult($result);

}