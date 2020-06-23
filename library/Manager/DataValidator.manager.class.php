<?php

class CDataValidatorManager {

    public function checkPushFilter($data) {
        $adas = false;

        foreach ($data as $value) {
            foreach ($value as $propName => $propValue) {
                if ($propValue == '') {
                    break 2;
                }
            }

            $adas = true;
        }

        if ($adas) {
            return true;
        }
        else {
            return false;
        }
    }

}