<?php

/**
 * Class CHelperManager
 */
class CHelperManager {
    /**
     * @param $url
     */
    public static function redirect($url) {
        header("Location: " . HTTP_DASHBOARD_SERVER . "$url");
    }

    /**
     * @param $url
     */
    public static function globalRedirect($url) {
        header("Location: " . $url);
    }

    /**
     * @param $data
     */
    public static function var_pump($data) {
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
    }

    /**
     * @param $ID
     */
    public static function actionAddToSession($ID) {
        //$_SESSION['hnk'] = $ID;
    }

    /**
     * @param $tamplates
     */
    public static function generateWordDoc($tamplates) {
        $br = "\n";

        $dir = $tamplates['user_ID'] . '_' . $tamplates['ID'] . '-'.time().'.doc';
        $dirForCreat = DIR_ROOT . "other/download/download-" . $dir;
        $dirForDownload = HTTP_SERVER . "other/download/download-" . $dir;

        $fh = fopen($dirForCreat, 'w+') or die('Permission error');

        $content = $tamplates['name'] . $br;
        $content .= $tamplates['description'];

        fwrite($fh, $content);
        fclose($fh);

        header("Location: $dirForDownload");
    }

    /**
     *
     */
    public static function actionAutocompleteInput() {
        $criteria = '{ "name" : { "$regex" : "(?i)'. $_POST['value'] .'" } }';
        $options = [ 'sort' => ['name' => 1], 'limit' => 25 ];

        // remove all special characters
        $collectionName = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['collection']);

        $className = new CHelperValue();
        $documents = $className->find($collectionName, json_decode($criteria), $options);

        foreach ($documents as $document) {
            $docArray[] = array('_id' => (string)$document['_id'], 'name' => $document['name']);
        }

        echo json_encode($docArray);
        exit();
    }

    public function actionSetCookie() {
        setcookie('info_show_type', $_POST['type'], time() + (86400 * 30), "/"); // 86400 = 1 day
    }

    public static function sortMultiDimensionalArray(&$multiDimensionalArray, $sortKey) {
        $sorter = [];
        $result = [];

        # foreach for ge keys for sort
        foreach ($multiDimensionalArray as $key => $value) {
            $sorter[$key] = $value[$sortKey];
        }
        ksort($sorter);

        # create sorted array
        foreach ($sorter as $sortedKey => $sortedValue) {
            $result[$sortedKey] = $multiDimensionalArray[$sortedKey];
        }

        return $result;
    }

    public static function setCookieHelper($key, $value, $days) {
        setcookie($key, json_encode($value), time() + (DAY * $days), "/");
    }

    /**
     * Get Fields Names
     * Ajax
     */
    public static function actionGetFields() {
        $event = $_POST['event'];

        $criteria = '{"status" : 1}';
        $limit = [];

        $helperFields = new CHelperFields();

        # for all events
//        if ($event == 'all') {
//            $listCollections = $helperFields->listCollections();
//        }
//        # for unique event
//        else {
        $listCollections[] = $event . '_fields';
//        }

        # create array for all fields
        $fieldsInfo = [];
        foreach ($listCollections as $listCollection) {
            $fields = $helperFields->find($listCollection, json_decode($criteria), $limit);

            foreach ($fields as $field) {
                $fieldsInfo[$field['name']] = [
                    'name' => $field['name'],
                    'type' => $field['type']
                ];
            }
        }

        # sort fields array By ascending
        sort($fieldsInfo);

        echo json_encode( $fieldsInfo );
        exit();
    }

    /**
     * Convert Search Serialize To Mongo Query
     * @param $serialize
     * @return mixed|string
     */
    public static function serializeToQuery($serialize) {
        switch (@$serialize['and_or']) {
            case 'AND' : $andOr = '"$and"'; break;
            case 'OR' : $andOr = '"$or"'; break;
            default : $andOr = '"$and"'; break;
        }
        $criteria = '{'. $andOr .' : [';

        # generate criteria main part
        for ($i = 1; $i <= $serialize['count']; $i++) {
            if (isset($serialize['prop_type']) && isset($serialize['prop_key_' . $i]) && @!isset($serialize['prop_value_' . $i])) {
                switch ($serialize['prop_type']) {
                    case 'by' : $groupType = '$sum'; $groupKey1 = '"$' . $serialize['prop_key_' . $i] . '"'; $groupKey2 = 1; break;
                    case 'sum' : $groupType = '$sum'; $groupKey1 = '"$' . $serialize['prop_key_' . $i] . '"'; $groupKey2 = 1; break;
                    case 'avg' : $groupType = '$avg'; $groupKey1 = 1; $groupKey2 = '"$' . $serialize['prop_key_' . $i] . '"'; break;
                    case 'max' : $groupType = '$max'; $groupKey1 = 1; $groupKey2 = '"$' . $serialize['prop_key_' . $i] . '"';  break;
                    case 'min' : $groupType = '$min'; $groupKey1 = 1; $groupKey2 = '"$' . $serialize['prop_key_' . $i] . '"';  break;
                }

                $groupValue = $serialize['prop_value_' . $i];
            }

            if (isset($serialize['prop_criteria_' . $i])) {
                # check key name
                $propKey = '"'. $serialize['prop_key_' . $i] .'"';

                # check criteria type
                switch ($serialize['prop_criteria_' . $i]) {
                    case 'eq' : $propCriteria = '"$eq"'; break;
                    case 'neq' : $propCriteria = '"$ne"'; break;
                    case 'gt' : $propCriteria = '"$gt"'; break;
                    case 'lt' : $propCriteria = '"$lt"'; break;
                    case 'range' : $propCriteria = ''; break;
                }

                # check value name
                # check value is numeric or string
                if ((isset($serialize['prop_value_' . $i]) && !is_numeric($serialize['prop_value_' . $i])) || ($serialize['prop_key_' . $i] == 'Track ID') || ($serialize['prop_key_' . $i] == 'UserID')) {
                    $propValue = '"'. $serialize['prop_value_' . $i] .'"';
                }
                else {
                    # some check for range
                    if ($propCriteria == '') {
                        $propValueStart = $serialize['prop_value_start_' . $i];
                        $propValueEnd = $serialize['prop_value_end_' . $i];
                    }
                    else {
                        $propValue = $serialize['prop_value_' . $i];
                    }
                }

                # check last loop
                if ($i == $serialize['count'] && !isset($serialize['prop_type'])) {
                    if ($propCriteria == '') {
                        $criteria .= '{'. $propKey .' : {"$gte" : '. $propValueStart .'}},{'. $propKey .' : {"$lte" : '. $propValueEnd .'}}';
                    }
                    else {
                        $criteria .= '{'. $propKey .' : {'. $propCriteria .' : '. $propValue .'}}';
                    }
                }
                elseif (isset($serialize['prop_type']) && ($i == $serialize['count'] - 1)) {
                    if ($propCriteria == '') {
                        $criteria .= '{'. $propKey .' : {"$gte" : '. $propValueStart .'}},{'. $propKey .' : {"$lte" : '. $propValueEnd .'}}';
                    }
                    else {
                        $criteria .= '{'. $propKey .' : {'. $propCriteria .' : '. $propValue .'}}';
                    }
                }
                else {
                    if ($propCriteria == '') {
                        $criteria .= '{'. $propKey .' : {"$gte" : '. $propValueStart .'}},{'. $propKey .' : {"$lte" : '. $propValueEnd .'}},';
                    }
                    else {
                        $criteria .= '{'. $propKey .' : {'. $propCriteria .' : '. $propValue .'}},';
                    }
                }
            }
        }
        # add time part
        $criteria .= ']}';

        if ($criteria == '{'. $andOr .' : []}') {
            $criteria = '{}';
        }
        else {
            $criteria = $criteria;
        }

        # check is exist group operator
        if (isset($serialize['prop_type'])) {
            if ($criteria == '{}') {
                $match = '';
            }
            else {
                $match = '{"$match" : '. $criteria .'},';
            }

            //
            $finalCriteria = '['. $match . '
            {
                "$group" : {
                    "_id" : '. $groupKey1 .',
                    "value" : {
                        "'. $groupType .'" : '. $groupKey2 .'
                    }
                }
            },
            {
                "$sort" : {
                    "value" : -1
                }
            }, 
            { 
                "$limit" : 10 
            }]';
        }
        else {
            $finalCriteria = $criteria;
        }

        # check if json have ",]" element replace with "]"
        $finalCriteria = str_replace(',]',']',$finalCriteria);

        return $finalCriteria;
    }

    /**
     * @param $range
     * @return array
     */
    public static function getSessionDurattionCount($range, $checkDate) {
        $finalArray = [];

        foreach ($range as $value) {
            foreach (range($value[0], $value[1]) as $number) {
                $finalArray[$number] = $number;
            }
        }
        ksort($finalArray);

        $checker = 1;
        $sessionDuration = 0;
        $sessionCount = 0;
        //
        $sessionStartInfo = [];
        $sessionEndInfo = [];
        $sessionInfo = [];

        foreach ($finalArray as $key => $value) {
            if (($value - $checker) > 60) {
                $sessionStartInfo[] = $value;
                $sessionEndInfo[] = $checker;

                $sessionCount++;
            }

            # get last timestamp
            if (($sessionDuration + 1) == count($finalArray)) {
                $sessionEndInfo[] = $value;
            }

            $sessionDuration++;

            $checker = $value;
        }

        foreach ($sessionStartInfo as $key1 => $value1) {
            if ($value1 < $checkDate) {
                $sessionInfo[strtotime(date('Y-m-d', $value1))][] = [
                    'start'    => $sessionStartInfo[$key1],
                    'end'       => $sessionEndInfo[$key1 + 1],
                    'durattion' => ($sessionEndInfo[$key1 + 1] - $sessionStartInfo[$key1] + 1)
                ];
            }
            else {
                $sessionInfo[$checkDate][] = [
                    'start'    => $sessionStartInfo[$key1],
                    'end'       => $sessionEndInfo[$key1 + 1],
                    'durattion' => ($sessionEndInfo[$key1 + 1] - $sessionStartInfo[$key1] + 1)
                ];
            }


        }

//        return [
//            'session_count'     => $sessionCount,
//            'session_durattion' => $sessionDuration,
//            'session_avg'       => round($sessionDuration / $sessionCount),
//            'session_info'      => $sessionInfo
//        ];


        return [
            'session_info'      => $sessionInfo
        ];
    }

    /**
     * @param $range
     * @param $checkDate
     * @return array
     */
    public static function getSessionDurattionTotalInfo($range, $checkDate) {
        $finalArray = [];
        foreach ($range as $value) {
            if (abs($value['end'] - $value['start']) < 10000) {
                foreach (range($value['start'], $value['end']) as $number) {
                    $finalArray[$number] = $number;
                }
            }
        }
        ksort($finalArray);
        $seesionArray = array_values($finalArray);

        $checker = 1;
        $sessionDuration = 0;
        $sessionCount = 0;
        //
        $sessionStartInfo = [];
        $sessionEndInfo = [];
        $sessionInfo = [];

        foreach ($seesionArray as $value) {
            if (($value - $checker) > 60) {
                $sessionStartInfo[] = $value;
                $sessionEndInfo[] = $checker;

                $sessionCount++;
            }

            # get last timestamp
            if (($sessionDuration + 1) == count($finalArray)) {
                $sessionEndInfo[] = $value;
            }

            $sessionDuration++;
            $checker = $value;
        }

        foreach ($sessionStartInfo as $key1 => $value1) {
            if ($value1 < $checkDate) {
                $sessionInfo[strtotime(date('Y-m-d', $value1))][] = [
                    'start'    => $sessionStartInfo[$key1],
                    'end'       => $sessionEndInfo[$key1 + 1],
                    'durattion' => ($sessionEndInfo[$key1 + 1] - $sessionStartInfo[$key1] + 1)
                ];
            }
            else {
                $sessionInfo[$checkDate][] = [
                    'start'    => $sessionStartInfo[$key1],
                    'end'       => $sessionEndInfo[$key1 + 1],
                    'durattion' => ($sessionEndInfo[$key1 + 1] - $sessionStartInfo[$key1] + 1)
                ];
            }


        }

        return $sessionInfo;
    }

    /**
     * @param $filePath
     * @param $row
     * @param string $firstLine
     */
    public static function writeToFile($filePath, $row, $firstLine = '') {
        if (file_exists($filePath)) {
            $file = fopen($filePath,"a");
            fwrite($file, $row);
            fclose($file);
        } else {
            $file = fopen($filePath,"w");
            fwrite($file, $firstLine);
            fwrite($file, $row);
            fclose($file);
        }
    }

    /**
     * @param $fileDir
     * @param $fileName
     * @param $row
     * @param string $firstLine
     */
    public static function createFile($fileDir, $fileName, $row, $firstLine = '') {
        # check is exist file path
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0755, true);
        }

        $filePath = $fileDir . $fileName;

        if (file_exists($filePath)) {
            $file = fopen($filePath,"a");
            fwrite($file, $row);
            fclose($file);
        } else {
            $file = fopen($filePath,"w");
            fwrite($file, $firstLine);
            fwrite($file, $row);
            fclose($file);
        }
    }

    /**
     * @param $fileDir
     * @param $fileName
     * @param $row
     * @param string $firstLine
     */
    public static function createZipFile($fileDir, $fileName, $row, $firstLine = '') {
        # check is exist file path
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0755, true);
        }

        $filePath = $fileDir . $fileName;

        if (file_exists($filePath)) {
            $file = fopen($filePath,"a");
            fwrite($file, $row);
            fclose($file);
        } else {
            $file = fopen($filePath,"w");
            fwrite($file, $firstLine);
            fwrite($file, $row);
            fclose($file);
        }

        shell_exec('zip -j '. $fileDir . $fileName . '.zip ' . $fileDir . $fileName );
        shell_exec('rm ' . $fileDir . '/' . $fileName);
    }

    public static function multisortByKey($array, $key) {
        $fieldsNames = [];
        foreach ($array as $availableFieldInfo) {
            $fieldsNames[] = key($availableFieldInfo['name']);
        }

        $lowercaseFields = array_map('strtolower', $fieldsNames);

        array_multisort(
            $lowercaseFields,
            SORT_ASC,
            SORT_STRING,
            $array);

        return $array;
    }

    public static function getMonthsStartEndDatesForSelectedRange($startDate, $endDate) {
        $mothsFirstDates = [];

        $currentDate = $startDate + 3 * DAY;
        $endDate = $endDate + 16 * DAY;
        while ($currentDate <= $endDate) {
            $mothsFirstDates[] = strtotime(date('Y-M-01', $currentDate));

            $currentDate += 31 * DAY;
        }

        $monthsInfo = [];
        for ($j = 0; $j < count($mothsFirstDates); $j++) {
            if (isset($mothsFirstDates[$j+1])) {
                $monthsInfo[] = [
                    'start' => $mothsFirstDates[$j],
                    'end' => $mothsFirstDates[$j+1],
                ];
            }
        }

        return $monthsInfo;
    }

    /**
     * @param $date
     * @return false|string
     */
    public static function getDateFromTime($date) {
        return date('Y M d H:i:s', $date);
    }

    /**
     * @param $date
     * @return false|string
     */
    public static function getMonthFromTime($date) {
        return date('Y M', $date);
    }

    /**
     * @param $date
     * @return false|string
     */
    public static function getOnlyDateFromTime($date) {
        return date('Y M d', $date);
    }

    /**
     * @param $date
     * @return false|string
     */
    public static function getLineDateFromTime($date) {
        return date('Y-M-d', $date);
    }

    /**
     * @param $date
     * @return false|string
     */
    public static function getLineNumericDateFromTime($date) {
        return date('Y-m-d', $date);
    }

    /**
     * @param $timestamp
     * @return string
     */
    public static function getMongoIdFromTimestamp($timestamp) {
        $baseConvert = base_convert($timestamp, 10, 16);

        return $baseConvert . '0000000000000000';
    }

    /**
     * @param $date
     * @return false|int
     */
    public static function getTimestampFromDate($date) {
        $timestamp = strtotime($date);

        return $timestamp;
    }

    /**
     * @param $time
     * @return false|string
     */
    public static function getDateForPush($time) {
        $date = date('m/d/Y g:i A', $time);

        return $date;
    }

    /**
     * @param $time
     * @return false|string
     */
    public static function getTimeForPush($time) {
        $date = date('g:i A', $time);

        return $date;
    }

    /**
     * @param $result
     */
    public static function printBulkWriteResult($result) {
        echo "\n", CIRCLE, 'BulkWrite Results', "\n";
        echo COLOR_BLUE, CHECK_MARK, 'MatchedCount: ', $result->getMatchedCount(), COLOR_END, "\n";
        echo COLOR_PURPLE, CHECK_MARK, 'ModifiedCount: ', $result->getModifiedCount(), COLOR_END, "\n";
        echo COLOR_YELLOW, CHECK_MARK, 'InsertedCount: ', $result->getInsertedCount(), COLOR_END, "\n";
        echo COLOR_LIGHT_GRAY, CHECK_MARK, 'UpsertedCount: ', $result->getUpsertedCount(), COLOR_END, "\n";
        echo "\n";
    }

    /**
     * @param $result
     */
    public static function printUpdateManyResult($result) {
        echo "\n", CIRCLE, 'UpdateMany Results', "\n";
        echo COLOR_BLUE, CHECK_MARK, 'MatchedCount: ', $result->getMatchedCount(), COLOR_END, "\n";
        echo COLOR_PURPLE, CHECK_MARK, 'ModifiedCount: ', $result->getModifiedCount(), COLOR_END, "\n";
        echo COLOR_LIGHT_GRAY, CHECK_MARK, 'UpsertedCount: ', $result->getUpsertedCount(), COLOR_END, "\n";
        echo "\n";
    }

    /**
     * @param $result
     */
    public static function printInsertManyResult($result) {
        echo "\n", CIRCLE, 'InsertMany Results', "\n";
        echo COLOR_BLUE, CHECK_MARK, 'MatchedCount: ', $result->getInsertedCount(), COLOR_END, "\n";
        echo "\n";
    }

    /**
     * @param $time
     * @param string $prefix
     * @return string
     */
    public static function getCollectionNameFromTimePrefix($time, $prefix = '') {
        return $prefix . date('Ym', $time);
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function margeAndRemoveDuplicates($array1 = [], $array2 = []) {
        $resArray1 = [];
        foreach ($array1 as $value1) {
            $checkKey1 = http_build_query($value1);

            $resArray1[$checkKey1] = $value1;
        }

        foreach ($array2 as $value2) {
            $checkKey2 = http_build_query($value2);

            if (isset($resArray1[$checkKey2])) {
                unset($resArray1[$checkKey2]);
            }
        }

        $result = array_merge(array_values($resArray1), $array2);

        return $result;
    }

    public static function margeAndRemoveDuplicatesIndexedArrays($array1 = [], $array2 = []) {
        $array1 = array_values($array1);
        $array2 = array_values($array2);

        return array_values(array_unique(array_merge($array1, $array2)));
    }

    /**
     * @param $start
     * @param $end
     * @return int
     */
    public static function returnDatesDiffInSeconds($start, $end) {
        $start  = (int)$start;
        $end    = (int)$end;

        return $end - $start;
    }

    /**
     * @param $date
     */
    public static function showScriptRunDate($date) {
        echo COLOR_BLUE, CIRCLE, 'Script Run Date: ', self::getDateFromTime($date), ' - ', $date, COLOR_END, "\n";
    }

    /**
     * @param $array
     * @param $key
     * @return array
     */
    public static function groupByKey($array, $key) {
        $data = [];

        foreach ($array as $value) {
            if (!isset($value[$key])) {
                continue;
            }

            if (!isset($data[$value[$key]])) {
                $data[$value[$key]] = [];
            }

            $data[$value[$key]][] = $value;
        }

        return $data;
    }

    /**
     * @param $data
     */
    public static function echoJsonData($data) {
        header('Content-Type: application/json');

        echo json_encode($data);
    }

    /**
     * @param $listSize
     * @param $minValue
     * @param $maxValue
     * @return array
     */
    public static function getRandomNumericList($listSize, $minValue, $maxValue) {
        $randomList = [];

        while (count($randomList) < $listSize) {
            $randomNumber = rand($minValue, $maxValue);

            if (!isset($randomList[$randomNumber])) {
                $randomList[$randomNumber] = $randomNumber;
            }
        }

        return array_unique($randomList);
    }

    /**
     * @param $date
     * @return false|int
     */
    public static function getNextMonthFirstTimestamp($date) {
        return strtotime(date("Y-m-t", $date)) + 86400;
    }

    /**
     * @param $date
     * @return false|string
     */
    public static function getNumericMonthFromTime($date) {
        return date("Ym", $date);
    }

    /**
     * @param $currentTime
     * @return false|int
     */
    public static function getDayStartTimestamp($currentTime) {
        return strtotime(self::getLineDateFromTime($currentTime));
    }
}

