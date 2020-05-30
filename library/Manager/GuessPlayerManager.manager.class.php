<?php

/**
 * Class CGuessPlayerManager
 */
final class CGuessPlayerManager extends CModelMongoGuessPlayer {

    /**
     * @var
     */
    private $possibleTypes;

    /**
     * @var
     */
    private $possibleStatuses;

    /**
     * @var
     */
    private $possibleFrequency;

    /**
     * CPushNotificationsManager constructor.
     */
    public function __construct() {
        date_default_timezone_set('America/Los_Angeles');

        $this->possibleTypes = [ 'Default', 'Background' ];
        $this->possibleStatuses = [ 'Active', 'Inactive', 'Archived', 'Trashed' ];
        $this->possibleFrequency = [ 'Daily', 'Scheduled', 'Immediately' ];
    }

    /**
     * @param $params
     */
    public function actionCountByFilter($params) {
        $errorHandler       = new CErrorHandlerManager();
        $dataValidator      = new CDataValidatorManager();
        $elasticSearch      = new CElasticSearch();
        $pushNotification   = new CPushNotification();

        if (!isset($_POST['data'])) {
            $errorHandler->requireError('data', 'Field required!');
        }
        else if ($_POST['data'] == '') {
            $errorHandler->textError('Please select some filter');
        }

        # save query
        $actionTime = time();

        $document = [
            'data'      => $_POST['data'],
            'format'    => 'base64',
            'date'      => CHelperManager::getDateFromTime($actionTime),
            'createdAt' => $actionTime
        ];
        //
        $insertResult = $pushNotification->insertOne(COLL_ELASTIC_SEARCH_QUERIES, $document);
        $queryId = (string)$insertResult->getInsertedId();

        # decode query data
        $data = json_decode(base64_decode($_POST['data'], true), true);

        # check data
        if (!$dataValidator->checkPushFilter($data)) {
            $errorHandler->textError('Please select correct filter and try again!');
        }

        $params = $elasticSearch->getParams11($data);
        //
        $result = $elasticSearch->count($params);

        echo json_encode([
            'filterId'          => $queryId,
            'usersInSegment'    => $result['count']
        ]);
        exit();
    }

    /**
     * @param $params
     */
    public function actionCreateCampaign($params) {
        $errorHandler   = new CErrorHandlerManager();
        $formManager    = new CFormManager();

        if (!isset($_POST['filterId'])) {
            $errorHandler->requireError('filterId', 'Field required!');
        }
        else if ($_POST['filterId'] == '') {
            $errorHandler->textError('filterId can\'t be empty!');
        }
        $filterId = $_POST['filterId'];

        if (!isset($_POST['name'])) {
            $errorHandler->requireError('name', 'Field required!');
        }
        else if ($_POST['name'] == '') {
            $errorHandler->textError('name can\'t be empty!');
        }
        $name = $_POST['name'];

        if (!isset($_POST['alert'])) {
            $errorHandler->requireError('alert', 'Field required!');
        }
        $alert = $_POST['alert'];

        if (!isset($_POST['deepLink'])) {
            $errorHandler->requireError('deepLink', 'Field required!');
        }
        $deepLink = $_POST['deepLink'];

        if (!isset($_POST['imageUrl'])) {
            $errorHandler->requireError('imageUrl', 'Field required!');
        }
        $imageUrl = $_POST['imageUrl'];

        if (!isset($_POST['type'])) { // background | default
            $errorHandler->requireError('type', 'Field required!');
        }
        else if (!in_array($_POST['type'], $this->possibleTypes)) {
            $errorHandler->textError('Type need to be one from this list' . implode(", ", $this->possibleTypes));
        }
        $type = $_POST['type'];

        if (!isset($_POST['frequency'])) { // daily | custom | immediately
            $errorHandler->requireError('frequency', 'Field required!');
        }
        else if (!in_array($_POST['frequency'], $this->possibleFrequency)) {
            $errorHandler->textError('Type need to be one from this list' . implode(", ", $this->possibleFrequency));
        }
        $frequency = $_POST['frequency'];

        if (!isset($_POST['frequencyValue'])) { // daily | custom
            $errorHandler->requireError('frequencyValue', 'Field required!');
        }
        else if ($frequency != 'Immediately' && $_POST['frequencyValue'] == '') {
            $errorHandler->textError('Frequency Value can\'t be empty');
        }
        $frequencyValue = $_POST['frequencyValue'];

        $boldIndexes = [];
        if ($type == 'Background') {
            # check bold indexes
            $boldIndexes = $formManager->getBoldIndexesFromAlert($alert);

            # check alert message
            $alert = $formManager->getAlertInfo($alert);
        }

        # save image in gallery list
        if ($imageUrl !== '') {
            $fileManager = new CFileManager();
            //
            $fileManager->saveImageUrl($imageUrl);
        }

        # check type
        if ($type == 'Default') {
            if (strlen($alert) < 5) {
                $errorHandler->textError('Default push length can\'t be less than 5!');
            }
        }

        # check frequency
        if ($frequency == 'Immediately') {
            $frequencyValue = CHelperManager::getDateForPush(time() + 2 * MINUTE);
        }

        $date = time();
        $data = [
            'filterId'          => $filterId,
            'name'              => $name,
            'data'              => [
                'alert'         => $alert,
                'deepLink'      => $deepLink,
                'imageUrl'      => $imageUrl,
                'boldIndexes'   => $boldIndexes
            ],
            'type'              => $type,
            'frequency'         => $frequency,
            'frequencyValue'    => $frequencyValue,
            'status'            => 'Active',
            'updatedAt'         => $date,
            'createdAt'         => $date
        ];

        $pushNotification = new CPushNotification();
        $result = $pushNotification->insertOne(COLL_CAMPAIGNS, $data);

        if ($result->getInsertedCount() == 1) {
            $resultInfo = [
                'insertedDocId' => (string)$result->getInsertedId(),
                'error'         => null
            ];
        }
        else {
            $resultInfo = [
                'insertedDocId' => null,
                'error'         => "Can't insert document please check content and try again"
            ];
        }

        echo json_encode($resultInfo);
        exit();
    }

    /**
     * @param $params
     */
    public function actionUpdateCampaign($params) {
        $errorHandler       = new CErrorHandlerManager();
        $pushNotification   = new CPushNotification();

        if (!isset($_POST['campaignId'])) {
            $errorHandler->requireError('campaignId', 'Field required!');
        }
        $campaignId = $_POST['campaignId'];

        if (!isset($_POST['status'])) {
            $errorHandler->requireError('status', 'Field required!');
        }
        else if (!in_array($_POST['status'], $this->possibleStatuses)) {
            $errorHandler->textError('Status need to be one from this list' . implode(", ", $this->possibleStatuses));
        }
        $status = $_POST['status'];

        $filter = [ '_id' => new \MongoDB\BSON\ObjectID( $campaignId ) ];

        $campaignInfo = $pushNotification->findOne(COLL_CAMPAIGNS, $filter);

        if ($campaignInfo == null) {
            $errorHandler->textError('Please check CampaignID and try again! | Campaign not found');
        }

        $updateFilter   = [ '_id' => new \MongoDB\BSON\ObjectID( $campaignId ) ];
        $updateInfo     = [ '$set' => [ 'status' => $status ] ];

        $pushNotification->updateOne(COLL_CAMPAIGNS, $updateFilter, $updateInfo);

        echo json_encode([
            'result' => 'You successfully updated campaign status'
        ]);
        exit();
    }

    /**
     * @param $params
     */
    public function actionGetProperties($params) {
        $operators = [
            'string' => [
                'eq'    => '=',
                'ne'    => '!='
            ],
            'number' => [
                'eq'    => '=',
                'ne'    => '!=',
                'gte'   => '>=',
                'lte'   => '<=',
                'range' => 'range'
            ],
            'date' => [
                'gte'           => 'On or after',
                'lte'           => 'On or prior',
                'range'         => 'range',
                'eqDays'        => 'Exactly (days ago)',
                'gteDays'       => 'More than (days ago)',
                'lteDays'       => 'Less than (days ago)',
                'rangeDays'     => 'Range (days)',
                'eqHours'       => 'Exactly (hours ago)',
                'gteHours'      => 'More than (hours ago)',
                'lteHours'      => 'Less than (hours ago)',
                'rangeHours'    => 'Range (hours)',
            ],
            'textArray' => [
                'eq'    => '=',
                'ne'    => '!=',
                'like'  => 'Like'
            ]
        ];

        $properties = [
//            [
//                'displayName'       => 'Ancestry',
//                'campaignKey'       => 'ancestry',
//                'autocompleteKey'   => 'ancestry',
//                'type'              => 'string',
//                'selectType'        => null
//            ],
            [
                'displayName'       => 'App Build Number',
                'campaignKey'       => 'appBuildNumber',
                'autocompleteKey'   => 'appBuildNumber',
                'type'              => 'number',
                'selectType'        => null
            ],
            [
                'displayName'       => 'App Version',
                'campaignKey'       => 'appVersion',
                'autocompleteKey'   => 'appVersions',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
//            [
//                'displayName'       => 'Birth Day',
//                'campaignKey'       => 'birthDay',
//                'autocompleteKey'   => 'birthDay',
//                'type'              => 'string',
//                'selectType'        => 'dynamic'
//            ],
            [
                'displayName'       => 'Birth Year',
                'campaignKey'       => 'birthYear',
                'autocompleteKey'   => 'birthYear',
                'type'              => 'number',
                'selectType'        => null
            ],
            [
                'displayName'       => 'Brand',
                'campaignKey'       => 'brand',
                'autocompleteKey'   => 'brand',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'City',
                'campaignKey'       => 'city',
                'autocompleteKey'   => 'city',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Country',
                'campaignKey'       => 'country',
                'autocompleteKey'   => 'country',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Credential',
                'campaignKey'       => 'credential',
                'autocompleteKey'   => 'credential',
                'type'              => 'string',
                'selectType'        => 'static'
            ],
            [
                'displayName'       => 'Downloads Count',
                'campaignKey'       => 'downloadsCount',
                'autocompleteKey'   => 'downloadsCount',
                'type'              => 'number',
                'selectType'        => null
            ],
            [
                'displayName'       => 'Downloaded Artists',
                'campaignKey'       => 'downloadedArtists',
                'autocompleteKey'   => 'trackArtist',
                'type'              => 'textArray',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Downloaded Genres',
                'campaignKey'       => 'downloadedGenres',
                'autocompleteKey'   => 'trackGenre',
                'type'              => 'textArray',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Itunes Permission',
                'campaignKey'       => 'itunesPermission',
                'autocompleteKey'   => 'itunesPermission',
                'type'              => 'string',
                'selectType'        => 'static'
            ],
            [
                'displayName'       => 'Favorite Genres',
                'campaignKey'       => 'favoriteGenres',
                'autocompleteKey'   => 'trackGenre',
                'type'              => 'textArray',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Followers',
                'campaignKey'       => 'followers',
                'autocompleteKey'   => 'followers',
                'type'              => 'number',
                'selectType'        => null
            ],
            [
                'displayName'       => 'Following',
                'campaignKey'       => 'following',
                'autocompleteKey'   => 'following',
                'type'              => 'number',
                'selectType'        => null
            ],
            [
                'displayName'       => 'Gender',
                'campaignKey'       => 'gender',
                'autocompleteKey'   => 'gender',
                'type'              => 'string',
                'selectType'        => 'static'
            ],
            [
                'displayName'       => 'Language',
                'campaignKey'       => 'language',
                'autocompleteKey'   => 'language',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Last Active date',
                'campaignKey'       => 'updatedAt',
                'autocompleteKey'   => 'updatedAt',
                'type'              => 'date',
                'selectType'        => null
            ],
            [
                'displayName'       => 'Last Download Date',
                'campaignKey'       => 'lastDownloadDate',
                'autocompleteKey'   => 'lastDownloadDate',
                'type'              => 'date',
                'selectType'        => null
            ],
//            [
//                'displayName'       => 'Last Notification Device Date',
//                'campaignKey'       => 'lastNotificationDeviceTime',
//                'autocompleteKey'   => 'lastNotificationDeviceTime',
//                'type'              => 'date',
//                'selectType'        => null
//            ],
            [
                'displayName'       => 'Limit Ad Tracking',
                'campaignKey'       => 'limitAdTracking',
                'autocompleteKey'   => 'limitAdTracking',
                'type'              => 'string',
                'selectType'        => 'static'
            ],
            [
                'displayName'       => 'Location Permission',
                'campaignKey'       => 'locationPermission',
                'autocompleteKey'   => 'locationPermission',
                'type'              => 'string',
                'selectType'        => 'static'
            ],
            [
                'displayName'       => 'Manufacturer',
                'campaignKey'       => 'manufacturer',
                'autocompleteKey'   => 'manufacturer',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Notification Allowed',
                'campaignKey'       => 'notificationAllowed',
                'autocompleteKey'   => 'notificationAllowed',
                'type'              => 'string',
                'selectType'        => 'static'
            ],
            [
                'displayName'       => 'Model',
                'campaignKey'       => 'model',
                'autocompleteKey'   => 'model',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Os',
                'campaignKey'       => 'os',
                'autocompleteKey'   => 'osType',
                'type'              => 'string',
                'selectType'        => 'static'
            ],
            [
                'displayName'       => 'Os Version',
                'campaignKey'       => 'osVersion',
                'autocompleteKey'   => 'osVersion',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Playlists Count',
                'campaignKey'       => 'playlists',
                'autocompleteKey'   => 'playlists',
                'type'              => 'number',
                'selectType'        => null
            ],
            [
                'displayName'       => 'Postal Code',
                'campaignKey'       => 'postal',
                'autocompleteKey'   => 'postal',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Region',
                'campaignKey'       => 'region',
                'autocompleteKey'   => 'region',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'Registration Date',
                'campaignKey'       => 'createdAt',
                'autocompleteKey'   => 'createdAt',
                'type'              => 'date',
                'selectType'        => null
            ],
            [
                'displayName'       => 'UserId',
                'campaignKey'       => 'userId',
                'autocompleteKey'   => 'userId',
                'type'              => 'string',
                'selectType'        => null
            ],

            [
                'displayName'       => 'Idfa',
                'campaignKey'       => 'idfa',
                'autocompleteKey'   => 'idfa',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
            [
                'displayName'       => 'AdId',
                'campaignKey'       => 'adId',
                'autocompleteKey'   => 'adId',
                'type'              => 'string',
                'selectType'        => 'dynamic'
            ],
        ];

        foreach ($properties as $key => $property) {
            $properties[$key]['operators'] = $operators[$property['type']];
        }

        echo json_encode([
            'result' => $properties
        ]);
        exit();
    }

    /**
     * @param $params
     */
    public function actionGetNotificationsList($params) {
        $pushNotification   = new CPushNotification();
        $errorHandler       = new CErrorHandlerManager();

        $possibleStatuses   = [ 'Default', 'Archived', 'Trashed' ];
        $possibleTypes      = [ 'Default', 'Background' ];

        $filter = ['$and' => []];

        # Next page
        if (isset($_GET['nextPage'])) {
            $lastCheckedDocumentId = new \MongoDB\BSON\ObjectID( $_GET['nextPage'] );

            $filter['$and'][] = [ '_id' => [ '$lt' => $lastCheckedDocumentId ] ];
        }

        # Status
        if (!isset($_GET['status'])) {
            $errorHandler->requireError('status', 'Field required!');
        }
        else if (!in_array($_GET['status'], $possibleStatuses)) {
            $errorHandler->textError('Status need to be one from this list' . implode(", ", $possibleStatuses));
        }
        else {
            $status = $_GET['status'];

            if ($status == 'Archived') {
                $filter['$and'][] = ['status' => ['$eq' => 'Archived']];
            }
            else if ($status == 'Trashed') {
                $filter['$and'][] = ['status' => ['$eq' => 'Trashed']];
            }
            else if ($status == 'Default') {
                $filter['$and'][] = [
                    '$or' => [
                        ['status' => 'Active'],
                        ['status' => 'Inactive'],
                        ['status' => 'Send']
                    ]
                ];
            }
        }

        # Type
        if (!isset($_GET['type'])) {
            $errorHandler->requireError('type', 'Field required!');
        }
        else if (!in_array($_GET['type'], $possibleTypes)) {
            $errorHandler->textError('Type need to be one from this list' . implode(", ", $possibleTypes));
        }
        else {
            $type = $_GET['type'];

            $filter['$and'][] = [ 'type' => $type ];
        }

        $options = [
            'sort'  => [ '_id' => -1 ],
            'limit' => 51
        ];

        $cursor = $pushNotification->find(COLL_CAMPAIGNS, $filter, $options);

        $i = 0;
        $docId = '';
        $lastCheckedDocumentId = '';
        $pushNotificationsList = [];
        $filterIds = [];
        foreach ($cursor as $document) {
            $documentId     = (string)$document['_id'];
            $boldIndexes    = isset($document['data']['boldIndexes']) ? $document['data']['boldIndexes'] : [];
            $imageUrl       = isset($document['data']['imageUrl']) ? $document['data']['imageUrl'] : '';

            $i++;

            if ($i == 51) {
                $lastCheckedDocumentId = $docId;
                continue;
            }

            if ($document['filterId'] != '' && strlen($document['filterId']) == 24) {
                $filterIds[] = new \MongoDB\BSON\ObjectID( $document['filterId'] );
            }

            $pushNotificationsList[$documentId] = [
                'campaignId'        => $documentId,
                'filterId'          => $document['filterId'],
                'name'              => $document['name'],
                'alert'             => $document['data']['alert'],
                'deepLink'          => $document['data']['deepLink'],
                'imageUrl'          => $imageUrl,
                'boldIndexes'       => $boldIndexes,
                'type'              => $document['type'],
                'frequency'         => $document['frequency'],
                'frequencyValue'    => $document['frequencyValue'],
                'status'            => $document['status'],
                'updatedAt'         => $document['updatedAt'],
                'createdAt'         => $document['createdAt']
            ];

            $docId = $documentId;
        }

        # get filters info
        $filtersInfo = $this->getFiltersInfo($filterIds);

        foreach ($pushNotificationsList as $pushNotificationId => $pushNotificationInfo) {
            $filterData = '';

            if ($filtersInfo[$pushNotificationInfo['filterId']]) {
                $filterData = $filtersInfo[$pushNotificationInfo['filterId']]['data'];
            }

            $pushNotificationsList[$pushNotificationId]['filterData'] = $filterData;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'result'    => $pushNotificationsList,
            'nextPage'  => $lastCheckedDocumentId
        ]);
        exit();
    }

    /**
     * @param $filtersIds
     * @return array
     */
    public function getFiltersInfo($filtersIds) {
        $pushNotification = new CPushNotification();

        $filter = [ '_id' => [ '$in' => $filtersIds ] ];
        //
        $cursor = $pushNotification->find(COLL_ELASTIC_SEARCH_QUERIES, $filter);

        $data = [];
        foreach ($cursor as $document) {
            $docId = (string)$document['_id'];

            $data[$docId] = [
                'data'      => $document['data'],
                'format'    => $document['format'],
                'date'      => $document['date'],
                'createdAt' => $document['createdAt']
            ];
        }

        return $data;
    }

    /**
     * @param $params
     */
    public function actionGetNotificationsStatistics($params) {
        $pushNotification   = new CPushNotification();

        $filter = [
            '$and' => [
                [ 'status' => 'Send' ]
            ]
        ];

        # Next page
        if (isset($_GET['nextPage'])) {
            $lastCheckedDocumentId = new \MongoDB\BSON\ObjectID( $_GET['nextPage'] );

            $filter['$and'][] = [ '_id' => [ '$lt' => $lastCheckedDocumentId ] ];
        }

        if (isset($_GET['campaignId']) && $_GET['campaignId'] != '') {
            $filter['$and'][] = [ '_id' => new \MongoDB\BSON\ObjectID( $_GET['campaignId'] ) ];
        }

        $options = [
            'sort'  => ['_id' => -1],
            'limit' => 51
        ];

        # get push list with DESC sorting
        $cursor = $pushNotification->find(COLL_CAMPAIGNS, $filter, $options);

        $i = 0;
        $docId = '';
        $lastCheckedDocumentId = '';
        $pushNotificationsList = [];
        foreach ($cursor as $document) {
            $pushId = (string)$document['_id'];

            $i++;

            if ($i == 51) {
                $lastCheckedDocumentId = $docId;
                continue;
            }

            $pushNotificationsList[$pushId] = [
                'campaignId'            => $pushId,
                'createdAt'             => $document['createdAt'],
                'sendTime'              => $document['frequencyValue'],
                'name'                  => $document['name'],
                'alert'                 => $document['data']['alert'],
                'type'                  => $document['type'],
                'appOpenIos'            => 0,
                'pushClickIos'          => 0,
                'appOpenAndroid'        => 0,
                'pushClickAndroid'      => 0,
                'usersInSegment'        => 0,
                'usersWithToken'        => 0,
                'usersWithoutToken'     => 0,
                'sentLogIosSuccess'     => 0,
                'sentLogIosFail'        => 0,
                'sentLogAndroidSuccess' => 0,
                'sentLogAndroidFail'    => 0,
            ];

            $docId = (string)$document['_id'];
        }

        $campaignStatistics = $this->getStatisticsForePushList(array_keys($pushNotificationsList));

        foreach ($campaignStatistics as $campaignKey => $campaignStatistic) {
            if (isset($pushNotificationsList[$campaignKey])) {
                $pushNotificationsList[$campaignKey]['appOpenIos'] = $campaignStatistic['appOpenIos'];
                $pushNotificationsList[$campaignKey]['pushClickIos'] = $campaignStatistic['pushClickIos'];
                $pushNotificationsList[$campaignKey]['appOpenAndroid'] = $campaignStatistic['appOpenAndroid'];
                $pushNotificationsList[$campaignKey]['pushClickAndroid'] = $campaignStatistic['pushClickAndroid'];
                //
                $pushNotificationsList[$campaignKey]['usersInSegment'] = $campaignStatistic['usersInSegment'];
                $pushNotificationsList[$campaignKey]['usersWithToken'] = $campaignStatistic['usersWithToken'];
                $pushNotificationsList[$campaignKey]['usersWithoutToken'] = $campaignStatistic['usersWithoutToken'];
                //
                $pushNotificationsList[$campaignKey]['sentLogIosSuccess'] = $campaignStatistic['sentLogIosSuccess'];
                $pushNotificationsList[$campaignKey]['sentLogIosFail'] = $campaignStatistic['sentLogIosFail'];
                $pushNotificationsList[$campaignKey]['sentLogAndroidSuccess'] = $campaignStatistic['sentLogAndroidSuccess'];
                $pushNotificationsList[$campaignKey]['sentLogAndroidFail'] = $campaignStatistic['sentLogAndroidFail'];
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'result'    => array_values($pushNotificationsList),
            'nextPage'  => $lastCheckedDocumentId
        ]);
        exit();
    }

    /**
     * @param $campaignIds
     * @return array
     */
    private function getStatisticsForePushList($campaignIds) {
        $push = new CPush();

        $statisticsFilter = [ 'pushId' => ['$in' => $campaignIds] ];

        $cursor = $push->find(COLL_STATISTICS, $statisticsFilter);

        $pushInfo = [];
        foreach ($cursor as $document) {
            # app open info
            $pushInfo[$document['pushId']]['appOpenIos']        = $document['openInfo']['iOS']['appOpen'] ?? 0;
            $pushInfo[$document['pushId']]['pushClickIos']      = $document['openInfo']['iOS']['pushClick'] ?? 0;
            $pushInfo[$document['pushId']]['appOpenAndroid']    = $document['openInfo']['Android']['appOpen'] ?? 0;
            $pushInfo[$document['pushId']]['pushClickAndroid']  = $document['openInfo']['Android']['pushClick'] ?? 0;
            # filter result info
            $pushInfo[$document['pushId']]['usersInSegment']    = $document['filterResult']['usersInSegment'] ?? 0;
            $pushInfo[$document['pushId']]['usersWithToken']    = $document['filterResult']['usersWithToken'] ?? 0;
            $pushInfo[$document['pushId']]['usersWithoutToken'] = $document['filterResult']['usersWithoutToken'] ?? 0;
            # sent log
            $pushInfo[$document['pushId']]['sentLogIosSuccess']     = $document['sentLog']['iOS']['success'] ?? 0;
            $pushInfo[$document['pushId']]['sentLogIosFail']        = $document['sentLog']['iOS']['fail'] ?? 0;
            $pushInfo[$document['pushId']]['sentLogAndroidSuccess'] = $document['sentLog']['Android']['success'] ?? 0;
            $pushInfo[$document['pushId']]['sentLogAndroidFail']    = $document['sentLog']['Android']['fail'] ?? 0;
        }

        return $pushInfo;
    }

}