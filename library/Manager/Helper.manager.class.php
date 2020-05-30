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
     * @param $key
     * @param $result
     * @param $collection
     * @return \MongoDB\InsertOneResult
     */
    public static function insertCache($key, $result, $collection) {
        $filter = '{"name" : "' . $key . '", "value" : ' . $result . '}';

        $cache = new CCache();
        //
        $cursor = $cache->saveOne($collection, json_decode($filter, true));

        return $cursor;
    }

    /**
     * @param $key
     * @return string
     */
    public static function checkCache($key, $collection) {
        $filter = '{"name" : "' . $key . '"}';

        $cache = new CCache();
        //
        $document = $cache->findOne($collection, json_decode($filter, true));

        return $document;
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

    public static function criteriaMaker($usersID) {
        switch (@$_POST['and_or']) {
            case 'AND' : $andOr = '"$and"'; break;
            case 'OR' : $andOr = '"$or"'; break;
            default : $andOr = '"$and"'; break;
        }
        $criteria = '{'. $andOr .' : [';

        // generate criteria main part
        for ($i = 1; $i <= $_POST['count']; $i++) {
            if (isset($_POST['prop_type']) && isset($_POST['prop_key_' . $i]) && @!isset($_POST['prop_value_' . $i])) {
                switch ($_POST['prop_type']) {
                    case 'by' : $groupType = '$sum'; $groupKey1 = '"$' . $_POST['prop_key_' . $i] . '"'; $groupKey2 = 1; break;
                    case 'sum' : $groupType = '$sum'; $groupKey1 = '"$' . $_POST['prop_key_' . $i] . '"'; $groupKey2 = 1; break;
                    case 'avg' : $groupType = '$avg'; $groupKey1 = 1; $groupKey2 = '"$' . $_POST['prop_key_' . $i] . '"'; break;
                    case 'max' : $groupType = '$max'; $groupKey1 = 1; $groupKey2 = '"$' . $_POST['prop_key_' . $i] . '"';  break;
                    case 'min' : $groupType = '$min'; $groupKey1 = 1; $groupKey2 = '"$' . $_POST['prop_key_' . $i] . '"';  break;
                }

                $groupValue = $_POST['prop_value_' . $i];
            }

            if (isset($_POST['prop_criteria_' . $i])) {
                // check key name
                $propKey = '"'. $_POST['prop_key_' . $i] .'"';

                // check criteria type
                switch ($_POST['prop_criteria_' . $i]) {
                    case 'eq' : $propCriteria = '"$eq"'; break;
                    case 'neq' : $propCriteria = '"$ne"'; break;
                    case 'gt' : $propCriteria = '"$gt"'; break;
                    case 'lt' : $propCriteria = '"$lt"'; break;
                    case 'range' : $propCriteria = ''; break;
                }

                // check value name
                // check value is numeric or string
                if (isset($_POST['prop_value_' . $i]) && !is_numeric($_POST['prop_value_' . $i])) {
                    $propValue = '"'. $_POST['prop_value_' . $i] .'"';
                }
                else {
                    // some check for range
                    if ($propCriteria == '') {
                        $propValueStart = $_POST['prop_value_start_' . $i];
                        $propValueEnd = $_POST['prop_value_end_' . $i];
                    }
                    else {
                        $propValue = $_POST['prop_value_' . $i];
                    }
                }

                // check last loop
                if ($i == $_POST['count'] && !isset($_POST['prop_type'])) {
                    if ($propCriteria == '') {
                        $criteria .= '{'. $propKey .' : {"$gte" : '. $propValueStart .'}},{'. $propKey .' : {"$lte" : '. $propValueEnd .'}}';
                    }
                    else {
                        $criteria .= '{'. $propKey .' : {'. $propCriteria .' : '. $propValue .'}}';
                    }
                }
                elseif (isset($_POST['prop_type']) && ($i == $_POST['count'] - 1)) {
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
        // add time part
        $criteria .= ']}';



        if ($criteria == '{'. $andOr .' : []}') {
            $criteria = '{}';
        }
        else {
            $criteria = $criteria;
        }

        // check is exist any user ID
        if ($usersID != null && $usersID != '') {
            if ($criteria == '{}') {
                $criteria = '{"UserID" : {"$in" : '. $usersID .'}}';
            }
            else {
                $criteriaUsers = '{"$and" : [{"UserID" : {"$in" : '. $usersID .'}},';
                $criteria = $criteriaUsers . $criteria . ']}';
            }
        }

        // check is exist group operator
        if (isset($_POST['prop_type'])) {
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

        // check if json have ",]" element replace with "]"
        $finalCriteria = str_replace(',]',']',$finalCriteria);


        return $finalCriteria;
    }

    public static function criteriaMakerForRetention() {
        switch (@$_POST['and_or']) {
            case 'AND' : $andOr = '"$and"'; break;
            case 'OR' : $andOr = '"$or"'; break;
            default : $andOr = '"$and"'; break;
        }
        $criteria = '{'. $andOr .' : [';

        // generate criteria main part
        for ($i = 1; $i <= $_POST['count']; $i++) {
            if (isset($_POST['prop_criteria_' . $i])) {
                // check key name
                $propKey = '"'. $_POST['prop_key_' . $i] .'"';

                // check criteria type
                switch ($_POST['prop_criteria_' . $i]) {
                    case 'eq' : $propCriteria = '"$eq"'; break;
                    case 'neq' : $propCriteria = '"$ne"'; break;
                    case 'gt' : $propCriteria = '"$gt"'; break;
                    case 'lt' : $propCriteria = '"$lt"'; break;
                    case 'range' : $propCriteria = ''; break;
                }

                // check value is numeric or string
                if (isset($_POST['prop_value_' . $i]) && !is_numeric($_POST['prop_value_' . $i])) {
                    $propValue = '"'. $_POST['prop_value_' . $i] .'"';
                }
                else {
                    // some check for range
                    if ($propCriteria == '') {
                        $propValueStart = $_POST['prop_value_start_' . $i];
                        $propValueEnd = $_POST['prop_value_end_' . $i];
                    }
                    else {
                        $propValue = $_POST['prop_value_' . $i];
                    }
                }

                // check last loop
                if ($i == $_POST['count'] && !isset($_POST['prop_type'])) {
                    if ($propCriteria == '') {
                        $criteria .= '{'. $propKey .' : {"$gte" : '. $propValueStart .'}},{'. $propKey .' : {"$lte" : '. $propValueEnd .'}}';
                    }
                    else {
                        $criteria .= '{'. $propKey .' : {'. $propCriteria .' : '. $propValue .'}}';
                    }
                }
                elseif (isset($_POST['prop_type']) && ($i == $_POST['count'] - 1)) {
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
        // add time part
        $criteria .= ']}';



        if ($criteria == '{'. $andOr .' : []}') {
            $criteria = '{}';
        }
        else {
            $criteria = $criteria;
        }


        // check if json have ",]" element replace with "]"
        $finalCriteria = str_replace(',]',']',$criteria);


        return $finalCriteria;
    }

    public static function actionSearchDates() {
        $dateType = $_POST['dateType'];

        $helper = new CHelper();
        $criteria = '{"type" : "'. $dateType .'"}';
        $documents = $helper->find(COL_DATE_TYPES, json_decode($criteria));

        foreach ($documents as $document) {
            $dates = (array)$document['values'];
        }

        echo json_encode([
            'status' => 1,
            'result' => $dates
        ]);
        exit();
    }

    public static function quarterToTimestamp( $date ) {
        $dateArray = explode(" ", $date);
        switch($dateArray[0]) {
            case 'First' : $month = '01'; break;
            case 'Second' : $month = '04'; break;
            case 'Third' : $month = '07'; break;
            case 'Fourth' : $month = '10'; break;
        }

        return strtotime($dateArray[2] . '-' . $month . '-01 ');
    }

    public function timestampToQuarter( $timestamp ) {
        $month = date("m", $timestamp);
        $year = date("Y", $timestamp);

        if ($month >= 1 && $month < 4) {
            $date = 'First Quarter ' . $year;
        }
        elseif ($month >= 4 && $month < 7) {
            $date = 'Second Quarter ' . $year;
        }
        elseif ($month >= 7 && $month < 10) {
            $date = 'Third Quarter ' . $year;
        }
        elseif ($month >= 10 && $month < 13) {
            $date = 'Fourth Quarter ' . $year;
        }
        else {
            die('Please check date');
        }

        return $date;
    }

    public static function biannualToTimestamp( $date ) {
        $dateArray = explode(" ", $date);
        switch($dateArray[0]) {
            case 'First' : $month = '01'; break;
            case 'Second' : $month = '07'; break;
        }

        return strtotime($dateArray[2] . '-' . $month . '-01 ');
    }

    public function timestampToBiannual( $timestamp ) {
        $month = date("m", $timestamp);
        $year = date("Y", $timestamp);

        if ($month >= 1 && $month < 7) {
            $date = 'First Half ' . $year;
        }
        elseif ($month >= 7 && $month < 13) {
            $date = 'Second Half ' . $year;
        }
        else {
            die('Please check date');
        }

        return $date;
    }

    public function actionGetUsersCountByRegDate() {
        $regDate = $_POST['reg_date'];

        # Get users reg START | END
        $criteria = '{"reg_date" : "'. $regDate .'"}';

        $helper = new CHelper();
        $userRegInfo = $helper->findOne(COL_RETENTION_REG_DATE, json_decode($criteria));

        # Get users installs count

        # Check user os
        switch ($_POST['os']) {
            case 'iOS' : $os = '{"os" : "iOS"},'; break;
            case 'Android' : $os = '{"os" : "Android"},'; break;
            default : $os = '';
        }

        $criteria = '{"$and" : [
            {"reg_date" : {"$gte" : '. $userRegInfo['activity_start'] .'}},
            {"reg_date" : {"$lt" : '. $userRegInfo['activity_end'] .'}},
            '. $os .'
            {"country" : "US"}
        ]}';

        $analytics = new CUsersInfo();
        $usersInstallsCount = $analytics->count(COLL_USERS_INFO, json_decode($criteria));

        echo json_encode([
            'status' => 1,
            'result' => $usersInstallsCount
        ]);
        exit();
    }

    public function actionSetCookie() {
        setcookie('info_show_type', $_POST['type'], time() + (86400 * 30), "/"); // 86400 = 1 day
    }

    /**
     * @param $collectionName
     * @return array
     */
    public static function getKeys($collectionName) {
        // check event collection
        switch ($collectionName) {
            case 'TRACK'            : $collectionName = COLL_FIELDS_TRACK;          break;
            case 'DURATION'         : $collectionName = COLL_FIELDS_DURATION;       break;
            case 'REVENUE'          : $collectionName = COLL_FIELDS_REVENUE;        break;
            case 'SEARCH'           : $collectionName = COLL_FIELDS_SEARCH;         break;
            case 'REWARDED'         : $collectionName = COLL_FIELDS_REWARDED;       break;
            case 'INVITE'           : $collectionName = COLL_FIELDS_INVITE;         break;
            case 'CHECK-IN'         : $collectionName = COLL_FIELDS_CHECK_IN;       break;
            case 'COIN'             : $collectionName = COLL_FIELDS_COIN;           break;
            case 'COIN_EARNINGS'    : $collectionName = COLL_FIELDS_COIN_EARNING;   break;
            case 'ADDS'             : $collectionName = COLL_FIELDS_ADS;            break;
            case 'custom_ad_impression'             : $collectionName = COLL_CUSTOM_AD_IMPRESSION_FIELDS;            break;
            case 'custom_ad_clicked'             : $collectionName = COLL_CUSTOM_AD_CLICKED_FIELDS;            break;
            default                 : $collectionName = COLL_FIELDS_TRACK;          break;
        }

        $filter = [];

        $helperFields = new CHelperFields();
        //
        $documents = $helperFields->find($collectionName, $filter);

        // create fields select area
        $documentsArray = [];
        foreach ($documents as $document) {
            $documentsArray[] = [
                'name' => $document['name'],
                'type' => $document['type']
            ];
        }
        sort($documentsArray);

        return $documentsArray;
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
     *
     */
    public static function actionActiveDatabases() {
        $helper = new CHelper();
        $activeDatabases = $helper->find(COLL_ACTIVE_DB, []);

        $dbs = [];
        foreach ($activeDatabases as $value) {
            $dbs = (array)$value['db'];
        }

        echo json_encode($dbs);
        exit();
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

    public static function getUsersFromDocuments($documents, $data) {
        foreach ($documents as $document) {
            $data[$document['UserID']] = $document['UserID'];
        }

        return $data;
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
     * @param $birthday
     * @param $agemin
     * @param $agemax
     * @return string
     */
    public static function checkAgeGroup($birthday, $agemin, $agemax) {
        if (isset($birthday) && $birthday) {
            $agemin = self::checkAgeRange($birthday)['min'];
            $agemax = self::checkAgeRange($birthday)['max'];
        }

        if ($agemax < 18 && $agemax != 0) return "<17";
        if ($agemin > 20) return "21+";
        if ($agemin >=18 && $agemin <= 20 && $agemax >=18 && $agemax <= 20) return "18-20";

        return "";
    }

    /**
     * @param $birthYear
     * @param $agemin
     * @param $agemax
     * @return string
     */
    public static function checkAgeGroupByBirthYear($birthYear, $agemin, $agemax) {
        if (isset($birthYear) && $birthYear != 0) {
            $agemin = self::checkAgeRangeByBirthYear($birthYear)['min'];
            $agemax = self::checkAgeRangeByBirthYear($birthYear)['max'];
        }

        if ($agemax < 18 && $agemax != 0) return "<17";
        if ($agemin > 20) return "21+";
        if ($agemin >=18 && $agemin <= 20 && $agemax >=18 && $agemax <= 20) return "18-20";

        return "";
    }

    /**
     * @param $birthday
     * @return array
     */
    public static function checkAgeRange($birthday) {
        $agemin = 0;
        $agemax = 0;

        if (isset($birthday) && $birthday != "0000-00-00" ) {
            $date       = new DateTime($birthday);
            $now        = new DateTime();
            $interval   = $now->diff($date);
            $age        = floor($interval->y);

            if ($age < 18) {
                $agemin = 13;
                $agemax = 17;
            }
            else if ($age < 21) {
                $agemin = 18;
                $agemax = 20;
            }
            else {
                $agemin = 21;
                $agemax = 0;
            }
        }

        return [
            'min' => $agemin,
            'max' => $agemax
        ];
    }

    /**
     * @param $birthYear
     * @return array
     */
    public static function checkAgeRangeByBirthYear($birthYear) {
        $agemin = 0;
        $agemax = 0;

        $age = (date('Y', time()) - $birthYear);

        if ($age < 18) {
            $agemin = 13;
            $agemax = 17;
        }
        else if ($age < 21) {
            $agemin = 18;
            $agemax = 20;
        }
        else {
            $agemin = 21;
            $agemax = 0;
        }

        return [
            'min' => $agemin,
            'max' => $agemax
        ];
    }

    /**
     * @return array
     */
    public static function getFakeFields() {
        $helperField = new CHelperFields();

        # get fake fields
        $filter = [];
        $fakeFieldsInfo = $helperField->find(COLL_FAKE_FIELDS, $filter);
        //
        $fakeFields = [];
        foreach ($fakeFieldsInfo as $fakeFieldInfo) {
            $fakeFields[$fakeFieldInfo['realKey']] = $fakeFieldInfo['showKey'];
        }

        return $fakeFields;
    }

    /**
     * @param $eventName
     * @param array $filter
     * @return array
     */
    public static function getFields($eventName, $filter = []) {
        $fakeFields = self::getFakeFields();

        # check event collection
        switch ($eventName) {
            case 'TRACK'                    : $helperFieldsCollection = COLL_FIELDS_TRACK;          break;
            case 'DURATION'                 : $helperFieldsCollection = COLL_FIELDS_DURATION;       break;
            case 'REVENUE'                  : $helperFieldsCollection = COLL_FIELDS_REVENUE;        break;
            case 'SEARCH'                   : $helperFieldsCollection = COLL_FIELDS_SEARCH;         break;
            case 'REWARDED'                 : $helperFieldsCollection = COLL_FIELDS_REWARDED;       break;
            case 'INVITE'                   : $helperFieldsCollection = COLL_FIELDS_INVITE;         break;
            case 'CHECK-IN'                 : $helperFieldsCollection = COLL_FIELDS_CHECK_IN;       break;
            case 'COIN'                     : $helperFieldsCollection = COLL_FIELDS_COIN;           break;
            case 'COIN_EARNINGS'            : $helperFieldsCollection = COLL_FIELDS_COIN_EARNING;   break;
            case 'ADS'                      : $helperFieldsCollection = COLL_FIELDS_ADS;            break;
            case COLL_USERS_FIELDS          : $helperFieldsCollection = COLL_USERS_FIELDS;          break;
            case COLL_PLAYED_TRACKS_FIELDS  : $helperFieldsCollection = COLL_PLAYED_TRACKS_FIELDS;  break;
            default                         : $helperFieldsCollection = $eventName;                 break;
        }

        $helperFields = new CHelperFields();
        //
        $documents = $helperFields->find($helperFieldsCollection, $filter);

        # create fields select area
        $fields = [];
        $documentsArray = [];
        foreach ($documents as $document) {
            if (isset($fakeFields[$document['name']])) {
                $fields[$document['name']] = $fakeFields[$document['name']];
            }
            else {
                $fields[$document['name']] = $document['name'];
            }

            $documentsArray[] = [
                'name' => $fields,
                'type' => $document['type']
            ];

            $fields = [];
        }

        return $documentsArray;
    }

    /**
     * @param $postInfo
     * @return string
     */
    public static function createCriteria($postInfo) {
        # check and or part
        switch (@$postInfo['and_or']) {
            case 'AND' : $andOr = '"$and"'; break;
            case 'OR' : $andOr = '"$or"'; break;
            default : $andOr = '"$and"'; break;
        }
        $criteria = '{'. $andOr .' : [';

        # generate criteria main part
        for ($i = 1; $i <= $postInfo['count']; $i++) {
            if (isset($postInfo['prop_type']) && isset($postInfo['prop_key_' . $i]) && @!isset($postInfo['prop_value_' . $i])) {
                switch ($postInfo['prop_type']) {
                    case 'by' : $groupType = '$sum'; $groupKey1 = '"$' . $postInfo['prop_key_' . $i] . '"'; $groupKey2 = 1; break;
                    case 'sum' : $groupType = '$sum'; $groupKey1 = '"$' . $postInfo['prop_key_' . $i] . '"'; $groupKey2 = 1; break;
                    case 'avg' : $groupType = '$avg'; $groupKey1 = 1; $groupKey2 = '"$' . $postInfo['prop_key_' . $i] . '"'; break;
                    case 'max' : $groupType = '$max'; $groupKey1 = 1; $groupKey2 = '"$' . $postInfo['prop_key_' . $i] . '"';  break;
                    case 'min' : $groupType = '$min'; $groupKey1 = 1; $groupKey2 = '"$' . $postInfo['prop_key_' . $i] . '"';  break;
                }

                $groupValue = $postInfo['prop_value_' . $i];
            }

            if (isset($postInfo['prop_criteria_' . $i])) {
                // check key name
                $propKey = '"'. $postInfo['prop_key_' . $i] .'"';

                // check criteria type
                switch ($postInfo['prop_criteria_' . $i]) {
                    case 'eq' : $propCriteria = '"$eq"'; break;
                    case 'neq' : $propCriteria = '"$ne"'; break;
                    case 'gt' : $propCriteria = '"$gt"'; break;
                    case 'lt' : $propCriteria = '"$lt"'; break;
                    case 'range' : $propCriteria = ''; break;
                }

                // check value is numeric or string
                if ((isset($postInfo['prop_value_' . $i]) && !is_numeric($postInfo['prop_value_' . $i])) || ($postInfo['prop_key_' . $i] == 'Track ID') || ($postInfo['prop_key_' . $i] == 'UserID')) {
                    $propValue = '"'. $postInfo['prop_value_' . $i] .'"';
                }
                else {
                    // some check for range
                    if ($propCriteria == '') {
                        $propValueStart = $postInfo['prop_value_start_' . $i];
                        $propValueEnd = $postInfo['prop_value_end_' . $i];
                    }
                    else {
                        $propValue = $postInfo['prop_value_' . $i];
                    }
                }

                // check last loop
                if ($i == $postInfo['count'] && !isset($postInfo['prop_type'])) {
                    if ($propCriteria == '') {
                        $criteria .= '{'. $propKey .' : {"$gte" : '. $propValueStart .'}},{'. $propKey .' : {"$lte" : '. $propValueEnd .'}}';
                    }
                    else {
                        $criteria .= '{'. $propKey .' : {'. $propCriteria .' : '. $propValue .'}}';
                    }
                }
                elseif (isset($postInfo['prop_type']) && ($i == $postInfo['count'] - 1)) {
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

        return $criteria;
    }

    /**
     * @param $postInfo
     * @return array
     */
    public static function createFilterForEachGroup($postInfo) {
        $fields = self::getFieldsForEachGroup($postInfo);

        # check and | or
        switch (@$postInfo['and_or']) {
            case 'AND' : $andOr = '$and'; break;
            case 'OR' : $andOr = '$or'; break;
            default : $andOr = '$and'; break;
        }

        $usersFilter = [];
        $playedTracksFilterArray = [];
        $downloadedTracksFilter = [];
        $eventsFilter = [];

        # generate criteria main part
        for ($i = 1; $i <= $postInfo['count']; $i++) {
            if (isset($fields['user'][$postInfo['prop_key_' . $i]])) {
                $filterInfo = self::createMongoFilterPart($postInfo, $i);

                $usersFilter[] = $filterInfo['value'];
            }
            else if (isset($fields['downloadedTracks'][$postInfo['prop_key_' . $i]])) {
                $filterInfo = self::createMongoFilterPart($postInfo, $i);

                $downloadedTracksFilter[] =  $filterInfo['value'];
            }
            else if (in_array($postInfo['prop_key_' . $i], $fields['playedTrack'])) {
                $filterInfo = self::createMongoFilterPart($postInfo, $i);

                $playedTracksFilterArray[] = $filterInfo['value'];
            }
            else {
                $filterInfo = self::createMongoFilterPart($postInfo, $i);

                $eventsFilter[] = $filterInfo['value'];
            }
        }

        $usersFilter            = self::createFilterForUsers($usersFilter, $andOr);
        $downloadedTracksFilter = self::createFilterForDownloadedTracks($downloadedTracksFilter, $andOr);
        $playedTracksFilter     = self::createFilterForPlayedTracks($playedTracksFilterArray, $andOr);
        $eventsFilter           = self::createFilterForEvents($eventsFilter, $andOr);

        return [
            'user'              => $usersFilter,
            'downloadedTrack'   => $downloadedTracksFilter,
            'playedTrack'       => $playedTracksFilter,
            'event'             => $eventsFilter,
            'andOr'             => $andOr
        ];
    }

    /**
     * @param $postInfo
     * @return array
     */
    public static function getFieldsForEachGroup($postInfo) {
        # get users Fields
        $filter = '{"status" : 1}';
        $usersFields = CHelperManager::getFields(COLL_USERS_FIELDS, json_decode($filter, true));
        $usersFieldsInfo = [];
        foreach($usersFields as $value) {
            $usersFieldsInfo[key($value['name'])] = $value['name'][key($value['name'])];
        }

        # get playedTracks Fields
        $filter = '{"status" : 1}';
        $playedTracksFields = CHelperManager::getFields(COLL_PLAYED_TRACKS_FIELDS, json_decode($filter, true));
        $playedTracksFieldsInfo = [];
        foreach($playedTracksFields as $value) {
            $playedTracksFieldsInfo[key($value['name'])] = $value['name'][key($value['name'])];
        }

        # get downloadedTracks Fields
        $filter = '{"status" : 1}';
        $downloadedTracksFields = CHelperManager::getFields(COLL_DOWNLOADED_TRACKS_FIELDS, json_decode($filter, true));
        $downloadedTracksFieldsInfo = [];
        foreach($downloadedTracksFields as $value) {
            $downloadedTracksFieldsInfo[key($value['name'])] = $value['name'][key($value['name'])];
        }

        return [
            'user'              => $usersFieldsInfo,
            'playedTrack'       => $playedTracksFieldsInfo,
            'downloadedTracks'  => $downloadedTracksFieldsInfo
        ];
    }

    /**
     * @param $postInfo
     * @param $step
     * @return array
     */
    public static function createMongoFilterPart($postInfo, $step) {
        $propKey = null;
        $filterPart = [];

        if (isset($postInfo['prop_criteria_' . $step])) {
            ##### check key name
            $propKey = $postInfo['prop_key_' . $step];

            ##### check criteria type
            $propCriteria = '$eq';
            switch ($postInfo['prop_criteria_' . $step]) {
                case 'eq'       : $propCriteria = '$eq';  break;
                case 'neq'      : $propCriteria = '$ne';  break;
                case 'gt'       : $propCriteria = '$gt';  break;
                case 'lt'       : $propCriteria = '$lt';  break;
                case 'gte'      : $propCriteria = '$gte'; break;
                case 'lte'      : $propCriteria = '$lte'; break;
            }

            ##### check value is date
            if ((strlen($postInfo['prop_value_' . $step]) == 10) && (strpos($postInfo['prop_value_' . $step], '/') == 2)) {
                $propValue = strtotime($postInfo['prop_value_' . $step]);
            }
            ##### check value is some ID or string
            else if ((isset($postInfo['prop_value_' . $step]) && !is_numeric($postInfo['prop_value_' . $step]))
                || ($postInfo['prop_key_' . $step] == 'Track ID')
                || ($postInfo['prop_key_' . $step] == 'UserID')) {
                $propValue = $postInfo['prop_value_' . $step];
            }
            ##### for int values
            else {
                $propValue = (int)$postInfo['prop_value_' . $step];
            }

            ##### add to filter
            $filterPart = [
                $propKey => [
                    $propCriteria => $propValue
                ]
            ];
        }

        return [
            'key'   => $propKey,
            'value' => $filterPart
        ];
    }

    /**
     * @param $playedTracksFilterArray
     * @param $andOrCriteria
     * @return array
     */
    public static function createFilterForPlayedTracks($playedTracksFilterArray, $andOrCriteria) {
        $trackPlayedOrdering = [
            'trackArtist'       => 1,
            'trackGenre'        => 2,
            'playedDuration'    => 3,
            'playedAt'          => 4
        ];

        $playedTracksFilter = [$andOrCriteria => []];

        foreach ($trackPlayedOrdering as $key => $value) {
            foreach ($playedTracksFilterArray as $filterInfo) {
                if (key($filterInfo) == $key) {
                    $playedTracksFilter[$andOrCriteria][] = $filterInfo;
                }
            }
        }

        # check is empty filter
        if ($playedTracksFilter == [$andOrCriteria => []]) {
            $playedTracksFilter = null;
        }

        return $playedTracksFilter;

    }

    public static function createFilterForDownloadedTracks($downloadedTracksFilterArray, $andOrCriteria) {
        $downloadedTracksOrdering = [
            'downloadsInfo.artistName'      => 1,
            'downloadsInfo.downloadCount'   => 2,
            'downloadsInfo.genres'          => 3
        ];

        $downloadedTracksFilter = [$andOrCriteria => []];

        foreach ($downloadedTracksOrdering as $key => $value) {
            foreach ($downloadedTracksFilterArray as $filterInfo) {
                if (key($filterInfo) == $key) {
                    $downloadedTracksFilter[$andOrCriteria][] = $filterInfo;
                }
            }
        }

        # check is empty filter
        if ($downloadedTracksFilter == [$andOrCriteria => []]) {
            $downloadedTracksFilter = null;
        }

        return $downloadedTracksFilter;

    }

    /**
     * @param $usersFilterArray
     * @param $andOrCriteria
     * @return array|null
     */
    public static function createFilterForUsers($usersFilterArray, $andOrCriteria) {
        $usersFilter = [$andOrCriteria => []];

        foreach ($usersFilterArray as $value) {
            $usersFilter[$andOrCriteria][] = $value;
        }

        # check is empty filter
        if ($usersFilter == [$andOrCriteria => []]) {
            $usersFilter = null;
        }

        return $usersFilter;

    }

    /**
     * @param $eventsFilterArray
     * @param $andOrCriteria
     * @return array|null
     */
    public static function createFilterForEvents($eventsFilterArray, $andOrCriteria) {
        $eventsFilter = [$andOrCriteria => []];

        foreach ($eventsFilterArray as $value) {
            $eventsFilter[$andOrCriteria][] = $value;
        }

        # check is empty filter
        if ($eventsFilter == [$andOrCriteria => []]) {
            $eventsFilter = null;
        }

        return $eventsFilter;

    }

    /**
     *
     */
    public static function actionCheckCriteria() {
        $collectionName = $_POST['collection'];

        $helperField = new CHelperFields();

        # check fields in fake collections list
        $fakeFields = $helperField->find(COLL_FAKE_FIELDS, []);
        foreach ($fakeFields as $fakeField) {
            if ($fakeField['showKey'] == $collectionName) {
                $collectionName = $fakeField['realKey'];
                continue;
            }
        }

        # check collectionName in fake collections list
        $fakeCollections = $helperField->find(COLL_FAKE_COLLECTIONS, []);
        foreach ($fakeCollections as $fakeCollection) {
            if ($fakeCollection['showKey'] == $collectionName) {
                $collectionName = $fakeCollection['realKey'];
                continue;
            }
        }

        # replace all incorrect symbols
        $collectionName = preg_replace('/[^A-Za-z0-9\-]/', '', $collectionName);

        $filter = '{ 
            "name" : { "$regex" : "(?i)'. $_POST['value'] .'" } 
        }';
        $options = [
            'limit' => 13
        ];
        //
        $helperValue = new CHelperValues();
        $documents = $helperValue->find($collectionName, json_decode($filter, true), $options);

        $docArray = [];
        foreach ($documents as $document) {
            $docArray[] = [
                '_id' => (string)$document['_id'],
                'name' => $document['name']
            ];
        }

        echo json_encode($docArray);
        exit();
    }

    /**
     * @param $date
     * @param $fileName
     */
    public static function exportArrayToXls($date, $fileName) {
        header("Content-Disposition: attachment; filename=". $fileName .".xls");
        header("Content-Type: application/vnd.ms-excel;");
        header("Pragma: no-cache");
        header("Expires: 0");

        $exportFile = fopen("php://output", 'w');
        foreach ($date as $value) {
            fputcsv($exportFile, $value, "\t");
        }
        fclose($exportFile);
    }

    /**
     * @param $parameters
     */
    public static function registerEmail($parameters) {
        $resProcesses = new CResProcesses();

        $resProcesses->saveOne(COLL_EMAILS, [
            'email' => is_array($parameters['email']) ? $parameters['email'] : explode(',',$parameters['email']),
            'name' => isset($parameters['name']) ? $parameters['name'] : null,
            'subject' => isset($parameters['subject']) ? $parameters['subject'] : null,
            'cc' => isset($parameters['cc']) ? (is_array($parameters['cc']) ? $parameters['cc'] : explode(',',$parameters['cc'])) : null,
            'bcc' => isset($parameters['bcc']) ? (is_array($parameters['bcc']) ? $parameters['bcc'] : explode(',',$parameters['bcc'])) : null,
            'content' => base64_encode( $parameters['content'] ? $parameters['content'] : '' ),
        ]);
    }

    /**
     * @param $params
     */
    public static function actionExportData($params) {
        $collectionName =  $_POST['collection_name'];
        $filter = base64_encode($_POST['filter']);
        $slackName =  $_POST['slack_name'];
        $slackDescription =  $_POST['slack_description'];

        $output = shell_exec("/var/www/analytics-dashboard/other/cron/bash/export.sh '{$collectionName}' '{$filter}' '{$slackName}' '{$slackDescription}'");

//        echo $output;
    }

    /**
     *
     */
    public static function actionGetSlackUsers() {
        $url = 'https://slack.com/api/users.list?token='. SLACK_TOKEN;

        $ch = curl_init();
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authToken = curl_exec($ch);

        echo $authToken;
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


    /**
     * @param $regDateStart
     * @param $regDateEnd
     * @return array
     * not a good version | user `getUsersMnmByRegDate`
     */
    public static function getUsersMnm($regDateStart, $regDateEnd) {
        $filter = '{
            "$and": [
                {"createdAt": {"$gte": ' . $regDateStart . '}},
                {"createdAt": {"$lt": ' . $regDateEnd . '}}
            ]
        }';

        $option = '{
            "projection": {
                "_id": 0,
                "userId": 1,
                "profile.country.value": 1,
                "devices.os": 1
            }
        }';

        $mnm = new CMnm();
        //
        $documents = $mnm->find(COLL_USERS, json_decode($filter, true), json_decode($option, true));

        $usersIds = [];
        $usersMainInfo = [];
        foreach ($documents as $document) {
            $usersIds[] = (string)$document['userId'];

            $usersMainInfo[$document['userId']] = [
                'country' => isset($document['profile']['country']['value']) ? $document['profile']['country']['value'] : 'Other',
                'os' => isset($document['devices'][0]['os']) ? self::checkDeviceOs($document['devices'][0]['os']) : 'Other'
            ];
        }

        return [
            'usersIds' => $usersIds,
            'usersMainInfo' => $usersMainInfo
        ];
    }

    /**
     * @param $users
     * @return array
     */
    public static function getUsersMnmByUsersIds($users) {
        $filter = ['userId' => ['$in' => $users]];

        $option = '{
            "projection": {
                "_id": 0,
                "useMode": 1,
                "userId": 1,
                "profile.country.value": 1,
                "devices.os": 1,
                "devices.appVersion": 1,
                "createdAt": 1,
                "profile.image.value": 1,
                "profile.image.share": 1,
                "profile.birthYear.value": 1,
                "profile.ageMin.value": 1,
                "profile.ageMax.value": 1
            }
        }';

        $mnm = new CMnm();
        //
        $documents = $mnm->find(COLL_USERS, $filter, json_decode($option, true));

        $users = [];
        foreach ($documents as $document) {
            $users[$document['userId']] = [
                'country'       => isset($document['profile']['country']['value']) ? $document['profile']['country']['value'] : 'Other',
                'os'            => isset($document['devices'][0]['os']) ? self::checkDeviceOs($document['devices'][0]['os']) : 'Other',
                'appVersion'    => isset($document['devices'][0]['appVersion']) ? $document['devices'][0]['appVersion'] : 'Other',
                'birthYear'     => $document['profile']['birthYear']['value'],
                'ageMin'        => $document['profile']['ageMin']['value'],
                'imageUrl'      => $document['profile']['image']['value'],
                'imageShare'    => $document['profile']['image']['share'],
                'ageMax'        => $document['profile']['ageMax']['value'],
                'useMode'       => $document['useMode'],
                'createdAt'     => $document['createdAt']
            ];
        }

        return $users;
    }

    /**
     * @param $regDateStart
     * @param $regDateEnd
     * @return array
     */
    public static function getUsersMnmByRegDate($regDateStart, $regDateEnd) {
        $filter = '{
            "$and": [
                {"createdAt": {"$gte": ' . $regDateStart . '}},
                {"createdAt": {"$lt": ' . $regDateEnd . '}}
            ]
        }';

        $option = '{
            "projection": {
                "_id": 0,
                "useMode": 1,
                "userId": 1,
                "profile.country.value": 1,
                "profile.language.value": 1,
                "profile.city.value": 1,
                "profile.region.value": 1,
                "profile.trackDownloaded.value": 1,
                "profile.trackPlayed.value": 1,
                "profile.followers.value": 1,
                "profile.following.value": 1,
                "profile.playlists.value": 1,
                "profile.birthDay.value": 1,
                "profile.birthYear.value": 1,
                "profile.ageMin.value": 1,
                "profile.ageMax.value": 1,
                "profile.gender.value": 1,
                "profile.ancestry.value": 1,
                "profile.postal.value": 1,
                "profile.image.value": 1,
                "profile.image.share": 1,
                "devices.os": 1,
                "credentials": 1,
                "createdAt": 1,
                "lastActiveTime": 1,
                "wallets": 1
            }
        }';

        $mnm = new CMnm();
        //
        $documents = $mnm->find(COLL_USERS, json_decode($filter), json_decode($option, true));

        $users = [];
        foreach ($documents as $document) {
            if (isset($document['credentials']['email']['email'])) {
                $email = $document['credentials']['email']['email'];
            }
            else {
                $email = "";
            }

            $credentialName = '';
            foreach ($document['credentials'] as $credentialName => $credentialInfo) {
                $credentialName = $credentialName;
            }

            $users[$document['userId']] = [
                'country'           => isset($document['profile']['country']['value']) ? $document['profile']['country']['value'] : 'Other',
                'os'                => isset($document['devices'][0]['os']) ? self::checkDeviceOs($document['devices'][0]['os']) : 'Other',
                'credentialName'    => $credentialName,
                'useMode'           => $document['useMode'],
                'language'          => $document['profile']['language']['value'],
                'city'              => $document['profile']['city']['value'],
                'region'            => $document['profile']['region']['value'],
                'email'             => $email,
                'trackDownloaded'   => $document['profile']['trackDownloaded']['value'],
                'trackPlayed'       => $document['profile']['trackPlayed']['value'],
                'followers'         => $document['profile']['followers']['value'],
                'following'         => $document['profile']['following']['value'],
                'playlists'         => $document['profile']['playlists']['value'],
                'imageUrl'          => $document['profile']['image']['value'],
                'imageShare'        => $document['profile']['image']['share'],
                'birthDay'          => $document['profile']['birthDay']['value'],
                'birthYear'         => $document['profile']['birthYear']['value'],
                'ageMin'            => $document['profile']['ageMin']['value'],
                'ageMax'            => $document['profile']['ageMax']['value'],
                'gender'            => $document['profile']['gender']['value'],
                'ancestry'          => $document['profile']['ancestry']['value'],
                'postal'            => $document['profile']['postal']['value'],
                'createdAt'         => $document['createdAt'],
                'lastActiveTime'    => $document['lastActiveTime'],
                'wallets'           => $document['wallets']
            ];
        }

        return $users;
    }

    /**
     * @param $os
     * @return string
     */
    public static function checkDeviceOs($os) {
        if (strpos($os, 'iOS') !== false) {
            $osInfo = 'iOS';
        }
        else if (strpos($os, 'iPhone') !== false) {
            $osInfo = 'iOS';
        }
        else if (strpos($os, 'Android') !== false) {
            $osInfo = 'Android';
        }
        else {
            $osInfo = 'other';
        }

        return $osInfo;
    }

    /**
     * @param $usersInfo
     * @param $mainResult
     * @return mixed
     */
    public static function checkUsersCountryOs($usersInfo, $mainResult) {
        foreach ($usersInfo as $userInfo) {
            if (!isset($mainResult[$userInfo['country']])) {
                $mainResult[$userInfo['country']] = [];
            }

            if (!isset($mainResult[$userInfo['country']][$userInfo['os']])) {
                $mainResult[$userInfo['country']][$userInfo['os']] = 0;
            }

            $mainResult[$userInfo['country']][$userInfo['os']]++;
        }

        return $mainResult;
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

    /**
     * @param $collectionName
     * @param $os
     * @return array
     */
    public static function getHelperValuesDependOs($collectionName, $os) {
        $helperValues = new CHelperValues();

        $filter = ['os' => $os];

        $appVersions = $helperValues->find($collectionName, $filter);

        $valuesList = [];
        foreach ($appVersions as $appVersion) {
            $valuesList[] = $appVersion['name'];
        }

        sort($valuesList);

        return $valuesList;
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

    public static function executeFolderPhpFiles($path, $arguments) {
//        foreach (glob("{$path}*.php") as $fileName) {
////            $as = exec($fileName, $output);
//
//
//            $argv = [
//                'argv 1',
//                'argv 2',
//                'argv 3',
//            ];
//
//            $argvs = json_encode($argv);
//
//            $as = exec("php {$fileName} {$argvs}", $output);
//
//            var_dump($as);
////            var_dump($output);
//
//            return $as;
//        }
    }

    /**
     * @param $users
     * @return array
     */
    public static function getUsersByCountryOs($users) {
        $usersInfo = [];

        foreach ($users as $userId => $userInfo) {
            # check country
            if (!isset($usersInfo[$userInfo['country']])) {
                $usersInfo[$userInfo['country']] = [];
            }

            # check os
            if (!isset($usersInfo[$userInfo['country']][$userInfo['os']])) {
                $usersInfo[$userInfo['country']][$userInfo['os']] = [];
            }

            $usersInfo[$userInfo['country']][$userInfo['os']][] = (string)$userId;
        }

        return $usersInfo;
    }

    /**
     * @param $usersIds
     * @param $checkDateStart
     * @param $checkDateEnd
     * @return array
     */
    public static function getActiveUsersInSelectedDate($usersIds, $checkDateStart, $checkDateEnd) {
        $activeUser = new CActiveUser();

        $usersIdsChunks = array_chunk(array_map('strval', $usersIds), DEFAULT_CHUNK);

        $activeUsers = [];
        foreach ($usersIdsChunks as $usersIdsChunk) {
            $filter = [ 'userId' => [ '$in' => $usersIdsChunk ] ];

            $currentDate = $checkDateStart;
            while ($currentDate < $checkDateEnd) {
                $cursor = $activeUser->find(COLL_ACTIVE_USERS_DAY . $currentDate, $filter);

                foreach ($cursor as $document) {
                    if (!isset($activeUsers[$document['userId']])) {
                        $activeUsers[$document['userId']] = $document['userId'];
                    }
                }

                $currentDate += DAY;
            }
        }

        return $activeUsers;
    }

    public static function getTbActiveUsersForSelectedDay($date) {
        $filter = [];

        $tbActiveUser = new CTbActiveUser();
        //
        $cursor = $tbActiveUser->find(COLL_TB_ACTIVE_USERS_DAY . $date, $filter);

        $tbActiveUsers = [];
        foreach ($cursor as $document) {
            $tbActiveUsers[$document['userId']] = [
                'userId'            => (string)$document['userId'],
                'country'           => $document['country'],
                'os'                => $document['os'],
                'trackPlayed'       => 0,
                'trackDownloaded'   => 0,
                'trackPreviewed'    => 0
            ];
        }

        return $tbActiveUsers;
    }

    /**
     * @param $usersIds
     * @param $checkDateStart
     * @param $checkDateEnd
     * @return array
     */
    public static function getUsersDownloadsCountInSelectedDate($usersIds, $checkDateStart, $checkDateEnd) {
        $filter = [
            [
                '$match' => [
                    'userId' => [ '$in' => $usersIds ]
                ]
            ],
            [
                '$project' => [
                    'userId' => 1,
                    'tracks' => [
                        '$filter' => [
                            'input' => '$tracks',
                            'as' => 'track',
                            'cond' => [
                                '$and' => [
                                    ['$gte' => [ '$$track.downloadedDate', $checkDateStart ] ],
                                    ['$lt' => [ '$$track.downloadedDate', $checkDateEnd ] ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                '$project' => [
                    'userId' => 1,
                    'downloadsCount' => [ '$cond' => [ 'if' => [ '$isArray' => '$tracks' ], 'then' => [ '$size' => '$tracks' ], 'else' => "NA" ] ]
                ]
            ]
        ];

        $helper = new CHelper();
        //
        $cursor = $helper->aggregate(COLL_DOWNLOADS_BY_DATE, $filter);

        $usersDownloads = [];
        foreach ($cursor as $document) {
            $usersDownloads[$document['userId']] = $document['downloadsCount'];
        }

        return $usersDownloads;
    }

    public static function getUsersLikesCountInSelectedDate($usersIds, $checkDateStart, $checkDateEnd) {
        $filter = [
            '$and' => [
                [ 'userId'      => [ '$in'  => $usersIds ] ],
                [ 'createdAt'   => [ '$gte' => $checkDateStart ] ],
                [ 'createdAt'   => [ '$lt'  => $checkDateEnd ] ]
            ]
        ];

        $mnm = new CMnm();
        //
        $cursor = $mnm->find(COLL_MNM_LIKES, $filter);

        $usersLikes = [];
        foreach ($cursor as $document) {
            if (!isset($usersLikes[$document['userId']])) {
                $usersLikes[$document['userId']] = 0;
            }

            $usersLikes[$document['userId']]++;
        }

        return $usersLikes;
    }

    public static function getUsersCommentsCountInSelectedDate($usersIds, $checkDateStart, $checkDateEnd) {
        $filter = [
            '$and' => [
                [ 'userId'      => [ '$in'  => $usersIds ] ],
                [ 'createdAt'   => [ '$gte' => $checkDateStart ] ],
                [ 'createdAt'   => [ '$lt'  => $checkDateEnd ] ]
            ]
        ];

        $mnm = new CMnm();
        //
        $cursor = $mnm->find(COLL_MNM_COMMENTS, $filter);

        $usersComments = [];
        foreach ($cursor as $document) {
            if (!isset($usersComments[$document['userId']])) {
                $usersComments[$document['userId']] = 0;
            }

            $usersComments[$document['userId']]++;
        }

        return $usersComments;
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

