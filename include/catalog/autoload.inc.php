<?php

    spl_autoload_register('autoload');

    function autoload($className) {
        $className = substr($className, 1);

		if(strpos($className, 'Model') !== false) {
            $autoActionFolder = 'Mapping/db/';
		}
		else if (strpos($className, 'Manager') !== false) {
            $autoActionFolder = 'Manager/';
            $classNameInfo = explode("Manager", $className);
            $className = ucwords($classNameInfo[0]) . '.' . 'manager';
		}
		else {
            $autoActionFolder = 'Mapping/';
        }

        require_once(DIR_LIBRARY . $autoActionFolder . $className . '.class.php');
    }