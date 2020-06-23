<?php

class CErrorHandlerManager {

    public function requireError($keyName, $description) {
        http_response_code(400);
        header('Content-Type: application/json');

        echo json_encode([
            'error'             => [ $keyName => $description ],
            'error_description' => $keyName . ' - ' . $description
        ]);
        exit();
    }

    public function textError($error) {
        http_response_code(400);
        header('Content-Type: application/json');

        echo json_encode([
            'error'             => $error,
            'error_description' => $error
        ]);
        exit();
    }

}