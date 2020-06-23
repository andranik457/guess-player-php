<?php

/**
 * Class CGdManager
 */
class CGdManager {

    /**
     * @var int
     */
    private $baseWidth;

    /**
     * @var int
     */
    private $baseHeight;

    /**
     * @var
     */
    private $image;

    /**
     * CGdManager constructor.
     */
    public function __construct() {
        $this->baseWidth = 2048;
        $this->baseHeight = 1434;
    }

    /**
     * CGdManager destructor.
     */
    public function __destruct() {
        if ($this->image != NULL && get_resource_type($this->image) != 'Unknown') {
            imagedestroy($this->image);
        }
    }

    /**
     * @param $filePath
     * @return array
     */
    public function resizeImage($filePath) {
        $headers = $this->getFileHeaders($filePath);

        if ($headers['mimeType'] == 'image/jpeg') {
            $this->image = imagecreatefromjpeg($filePath);
        }
        else {
            exit("Can't support this mimeType! \n");
        }

        $tempFilePath = null;

        try {
            $newImage = imagecreatetruecolor($this->baseWidth, $this->baseHeight);
            //
            imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $this->baseWidth, $this->baseHeight, $headers['width'], $headers['height']);

            $tempFilePath = $this->getTmpDir();

            # save image as png
            imagepng ($newImage, $tempFilePath);
            //
            imagedestroy($newImage);
        }
        catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

        imagedestroy($this->image);

        # update headers some info
        $headers['width'] = $this->baseWidth;
        $headers['height'] = $this->baseHeight;

        $result = $headers + [ 'baseImagePath' => $tempFilePath ];

        return $result;
    }

    /**
     * @param $imagePath
     * @param $imageHeaders
     * @return bool|string
     */
    public function negateImage($imagePath, $imageHeaders) {
        $this->image = imagecreatefrompng($imagePath);

        imagefilter($this->image, IMG_FILTER_NEGATE);

        # create tmpFile to send image via cUrl
        $negateTmpFile = $this->getTmpDir();

        imagepng($this->image, $negateTmpFile);
        imagedestroy($this->image);

        return $negateTmpFile;
    }

    /**
     * @param $imagePath
     * @param $imageHeaders
     * @param int $pixelsSize
     * @return bool|string
     */
    public function pixelateImage($imagePath, $imageHeaders, $pixelsSize = 10) {
        $this->image = imagecreatefrompng($imagePath);

        imagefilter($this->image, IMG_FILTER_PIXELATE, $pixelsSize, true);

        # create tmpFile to send image via cUrl
        $pixelateImagePate = $this->getTmpDir();

        imagepng($this->image, $pixelateImagePate);
        imagedestroy($this->image);

        return $pixelateImagePate;
    }

    /**
     * @param $imagePath
     * @param $imageHeaders
     * @param int $lineWith
     * @param int $elementsMargin
     * @return bool|string
     */
    public function addHorizontalLinesToImage($imagePath, $imageHeaders, $lineWith = 10, $elementsMargin = 20) {
        $this->image = imagecreatefrompng($imagePath);

        # set background color for image
        $white = imagecolorallocate($this->image, 229, 229, 197);

        # set the line thickness
        imagesetthickness($this->image, $lineWith);

        $yStep = $lineWith + $elementsMargin;

        $coordinateX1 = 0;
        $coordinateX2 = $imageHeaders['width'];
        //
        $coordinateY1 = $yStep;
        $coordinateY2 = $yStep;

        $verticalElementsMaxCount = round($imageHeaders['height'] / $yStep);

        for ($i = 0; $i < $verticalElementsMaxCount; $i++) {
            imageline($this->image, $coordinateX1, $coordinateY1, $coordinateX2, $coordinateY2, $white);

            $coordinateY1 += $yStep;
            $coordinateY2 += $yStep;
        }

        # create tmpFile
        $horizontalLinesImagePate = $this->getTmpDir();

        imagepng($this->image, $horizontalLinesImagePate);
        imagedestroy($this->image);

        return $horizontalLinesImagePate;
    }

    /**
     * @param $imagePath
     * @param $imageHeaders
     * @param int $lineWith
     * @param int $elementsMargin
     * @return bool|string
     */
    public function addVerticalLinesToImage($imagePath, $imageHeaders, $lineWith = 10, $elementsMargin = 20) {
        $this->image = imagecreatefrompng($imagePath);

        # set background color for image
        $white = imagecolorallocate($this->image, 229, 229, 197);

        # set the line thickness
        imagesetthickness($this->image, $lineWith);

        $xStep = $lineWith + $elementsMargin;

        $coordinateY1 = 0;
        $coordinateY2 = $imageHeaders['height'];
        //
        $coordinateX1 = $xStep;
        $coordinateX2 = $xStep;

        $horizontalElementsMaxCount = round($imageHeaders['width'] / $xStep);

        for ($i = 0; $i < $horizontalElementsMaxCount; $i++) {
            imageline($this->image, $coordinateX1, $coordinateY1, $coordinateX2, $coordinateY2, $white);

            $coordinateX1 += $xStep;
            $coordinateX2 += $xStep;
        }

        # create tmpFile
        $verticalLinesImagePate = $this->getTmpDir();

        imagepng($this->image, $verticalLinesImagePate);
        imagedestroy($this->image);

        return $verticalLinesImagePate;
    }

    /**
     * @param $imagePath
     * @param $imageHeaders
     * @param int $lineWith
     * @param int $elementsMargin
     * @return bool|string
     */
    public function addRectangleToImage($imagePath, $imageHeaders, $lineWith = 10, $elementsMargin = 20) {
        $horizontalLineImagePath = $this->addHorizontalLinesToImage($imagePath, $imageHeaders, $lineWith, $elementsMargin);
        $verticalLineImagePath = $this->addVerticalLinesToImage($horizontalLineImagePath, $imageHeaders, $lineWith, $elementsMargin);

        unlink($horizontalLineImagePath);

        $rectangleImage = $verticalLineImagePath;

        return $rectangleImage;

    }

    /**
     * @param $imagePath
     * @param $imageHeaders
     * @param $alphaIncrement
     * @return bool|string
     */
    public function addCirclesToImage($imagePath, $imageHeaders, $alphaIncrement) {
        $this->image = imagecreatefrompng($imagePath);

        # set background color for image
        $white = imagecolorallocate($this->image, 229, 229, 197);

        # set the line thickness
        $lineWith = 1;
        imagesetthickness($this->image, $lineWith);

        # margin between two elements
        $elementsMargin = 20;

        # set starting coordinates
        $coordinateX = 100 + $elementsMargin;
        $coordinateY = 100 + $elementsMargin;

        # Set the radius of the circle.
        $radius = 100;

        $horizontalElementsMaxCount = round($imageHeaders['width'] / ( 2 * $radius + $elementsMargin ) );
        $verticalElementsMaxCount = round($imageHeaders['height'] / ( 2 * $radius + $elementsMargin ) );
        $elementsMaxCount = $horizontalElementsMaxCount * $verticalElementsMaxCount;

        for ($i = 0; $i < $elementsMaxCount; $i++ ) {

            if ($i != 0 && $i % $horizontalElementsMaxCount == 0) {
                $coordinateX = 100 + $elementsMargin;
                $coordinateY += 2 * $radius + $elementsMargin;
            }

            # the angle what will be incremented for each loop.
            $alpha = 0;
            while ($alpha <= 360) {
                $x = $coordinateX + $radius * cos(deg2rad($alpha));
                $y = $coordinateY + $radius * sin(deg2rad($alpha));

                imageline($this->image, $coordinateX, $coordinateY, $x, $y, $white);

                $alpha += $alphaIncrement;
            }

            $coordinateX += 2 * $radius + $elementsMargin;;
        }

        # create tmpFile to send image via cUrl
        $tmpFile = $this->getTmpDir();

        imagepng($this->image, $tmpFile);
        imagedestroy($this->image);

        return $tmpFile;
    }

    /**
     * @param $imagePath
     * @param $imageHeaders
     * @param int $lineWidth
     * @return bool|string
     */
    public function horizontalSlice($imagePath, $imageHeaders, $lineWidth = 30) {
        $this->image = imagecreatefrompng($imagePath);

        $destinationImage = imagecreatetruecolor($imageHeaders['width'], $imageHeaders['height']);

        $currentXStep = 0;
        $maxStepsCount = round($imageHeaders['width'] / $lineWidth) + 1;

        $tmpFileForLines = $this->getTmpDir();
        $destinationTmpFile = $this->getTmpDir();

        for ($i = 1; $i <= $maxStepsCount; $i++) {
            $currentLine = imagecrop($this->image, ['x' => $currentXStep, 'y' => 0, 'width' => $lineWidth, 'height' => $imageHeaders['height']]);
            imagepng($currentLine, $tmpFileForLines);
            imagedestroy($currentLine);

            # get current line image
            $currentLineImage = imagecreatefrompng($tmpFileForLines);

            imagecopymerge($destinationImage, $currentLineImage, ($imageHeaders['width'] - $i * $lineWidth), 0, 0, 0, $lineWidth, $imageHeaders['height'], 70);
            imagepng($destinationImage, $destinationTmpFile);

            $destinationImage = imagecreatefrompng($destinationTmpFile);

            $currentXStep += $lineWidth;
        }

        return $destinationTmpFile;
    }

    /**
     * @param $imagePath
     * @param $imageHeaders
     * @param int $lineHeight
     * @return bool|string
     */
    public function verticalSlice($imagePath, $imageHeaders, $lineHeight = 30) {
        $this->image = imagecreatefrompng($imagePath);

        $destinationImage = imagecreatetruecolor($imageHeaders['width'], $imageHeaders['height']);

        $currentYStep = 0;
        $maxStepsCount = round($imageHeaders['height'] / $lineHeight);

        $tmpFileForLines = $this->getTmpDir();
        $destinationTmpFile = $this->getTmpDir();

        for ($i = 1; $i <= $maxStepsCount; $i++) {
            $currentLine = imagecrop($this->image, ['x' => 0, 'y' => $currentYStep, 'width' => $imageHeaders['width'], 'height' => $lineHeight]);
            imagepng($currentLine, $tmpFileForLines);
            imagedestroy($currentLine);

            # get current line image
            $currentLineImage = imagecreatefrompng($tmpFileForLines);

            imagecopymerge($destinationImage, $currentLineImage, 0, ($imageHeaders['height'] - $i * $lineHeight), 0, 0, $imageHeaders['width'], $lineHeight, 70);
            imagepng($destinationImage, $destinationTmpFile);

            $destinationImage = imagecreatefrompng($destinationTmpFile);

            $currentYStep += $lineHeight;
        }

        return $destinationTmpFile;
    }

    /**
     * @param $imageBasePath
     * @param $imageHeaders
     * @param $lineSize
     * @return bool|string
     */
    public function rectangleSlice($imageBasePath, $imageHeaders, $lineSize) {
        $horizontalSliceImagePath = $this->horizontalSlice($imageBasePath, $imageHeaders, $lineSize);
        $verticalSliceImagePath = $this->verticalSlice($horizontalSliceImagePath, $imageHeaders, $lineSize);

        unlink($horizontalSliceImagePath);

        $rectangleSlice = $verticalSliceImagePath;

        return $rectangleSlice;
    }

    /**
     * @param $tmpFile
     * @param $imageHeaders
     * @return string
     */
    public function storeImageViaCurl($tmpFile, $imageHeaders) {
        $url = 'http://local-node-image.com/files/setFile';

        $cfile = curl_file_create($tmpFile,'image/jpeg', $imageHeaders['fileName']);

        $data = [ 'test_file' => $cfile ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOiIxMTM2ODg0NDMiLCJkZXZpY2VJZCI6IjI1OTQ5MDc4IiwidHJhbnNhY3Rpb25JZCI6MCwiaWF0IjoxNTgzOTMxODA2fQ.UyCB0LN0QwrfrI0yx-cDDl_pSgYuCYcxFOhoTWsAluk"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $imageId = $result['result']['fileId'];

        unlink($tmpFile);

        return 'http://local-node-image.com/files/getFile/'. $imageId;
    }

    /**
     * @param $filePath
     * @return array
     */
    public function getFileHeaders($filePath) {
        $fileData = [];

        $fileHeaders = exif_read_data($filePath, 0, true);
        //
        $fileData['fileName']       = $fileHeaders['FILE']['FileName']          ?? '';
        $fileData['mimeType']       = $fileHeaders['FILE']['MimeType']          ?? '';
        $fileData['width']          = $fileHeaders['COMPUTED']['Width']         ?? '';
        $fileData['height']         = $fileHeaders['COMPUTED']['Height']        ?? '';
        $fileData['description']    = $fileHeaders['IFD0']['ImageDescription']  ?? '';

        return $fileData;
    }

    /**
     * @param $dir
     * @return array
     */
    public function getDirFiles($dir) {
        $files = scandir($dir);

        unset($files[0]);
        unset($files[1]);

        return $files;
    }

    /**
     * @return bool|string
     */
    public function getTmpDir() {
        $tmpDir = tempnam("/tmp", "tmpFile");

        return $tmpDir;
    }

}

