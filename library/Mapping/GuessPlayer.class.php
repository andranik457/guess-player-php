<?php

/**
 * Class CGuessPlayer
 */
final class CGuessPlayer extends CModelMongoGuessPlayer {

    /**
     * @param $collectionName
     * @param $criteria
     * @param array $options
     * @return array|null|object
     */
    public function findOne($collectionName, $criteria, $options = []) {
        $collection = $this->db->selectCollection( $collectionName );

        $cursor = $collection->findOne($criteria, $options);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $criteria
     * @param $options
     * @return \MongoDB\Driver\Cursor
     */
    public function find($collectionName, $criteria, $options = []) {
        $collection = $this->db->selectCollection( $collectionName );
        
        $cursor = $collection->find($criteria, $options);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $criteria
     * @param array $options
     * @return int
     */
    public function count($collectionName, $criteria, $options = []) {
        $collection = $this->db->selectCollection($collectionName);

        $cursor = $collection->count($criteria, $options);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $criteria
     * @return Traversable
     */
    public function aggregate($collectionName, $criteria, $option = []) {
        $collection = $this->db->selectCollection($collectionName);

        $cursor = $collection->aggregate($criteria, $option);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $criteria
     * @return \MongoDB\InsertManyResult
     */
    public function insertMany($collectionName, $criteria) {
        $collection = $this->db->selectCollection($collectionName);

        $cursor = $collection->insertMany($criteria);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $criteria
     * @return \MongoDB\InsertOneResult
     */
    public function insertOne($collectionName, $criteria) {
        $collection = $this->db->selectCollection($collectionName);

        $cursor = $collection->insertOne($criteria);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $filter
     * @param $document
     * @param array $option
     * @return \MongoDB\UpdateResult
     */
    public function update($collectionName, $filter, $document, $option = []) {
        $collection = $this->db->selectCollection($collectionName);

        $cursor = $collection->updateMany($filter, $document, $option);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $filter
     * @param $document
     * @param array $option
     * @return \MongoDB\UpdateResult
     */
    public function updateOne($collectionName, $filter, $document, $option = []) {
        $collection = $this->db->selectCollection($collectionName);

        $cursor = $collection->updateOne($filter, $document, $option);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $filter
     * @param array $option
     * @return \MongoDB\DeleteResult
     */
    public function deleteMany($collectionName, $filter, $option = []) {
        $collection = $this->db->selectCollection($collectionName);

        $cursor = $collection->deleteMany($filter, $option);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $filter
     * @param array $option
     * @return \MongoDB\DeleteResult
     */
    public function deleteOne($collectionName, $filter, $option = []) {
        $collection = $this->db->selectCollection($collectionName);

        $cursor = $collection->deleteOne($filter, $option);

        return $cursor;
    }

    /**
     * @param $collectionName
     * @param $bulkInfo
     * @return \MongoDB\BulkWriteResult
     */
    public function bulkWrite($collectionName, $bulkInfo) {
        $collection = $this->db->selectCollection($collectionName);

        $cursor = $collection->BulkWrite($bulkInfo);

        return $cursor;
    }

}