<?php
include_once "futures.php";
require_once "restclient/RestObjects.php";

class RestQueryFutureTask extends QueryFutureTask {

    function throwRestError($result) {
        if (isset($result[0])) {
            $result = $result[0]; // TODO
            if (isset($result->errorCode, $result->message) && in_array($result->errorCode, $this->knownErrors())) {
                throw new WorkbenchHandledException($result->errorCode . ": " . $result->message);
            }
        }
        throw new Exception("Unknown REST query error"); // TODO!
    }

    function query($soqlQuery,$queryAction,$queryLocator = null) {
        $url = "/services/data/";
        $url .= "v". WorkbenchContext::get()->getApiVersion();
        if ($queryLocator) {
            $url .= "/query/" . $queryLocator;
        } else {
            $url .= "/" . strtolower($queryAction);
            $url .= "?" . http_build_query(array("q" => $soqlQuery));
        }

        $response = WorkbenchContext::get()->getRestDataConnection()->send("GET", $url, null, null, false);

        if (strpos($response->header, "Content-Type: application/json") === false) {
            throw new Exception("Unknown response type: $response->header");
        }

        $result = json_decode($response->body);

        if (strpos($response->header, "200 OK") === false) {
            $this->throwRestError($result);
        }

        return new RestQueryResult($result);
    }

    function getQueryResultHeaders($sobject, $tail="") {
        $headerBufferArray = array();

        if (isset($sobject->anyFields)) {
            foreach ($sobject->anyFields as $anyFieldName => $anyFieldValue) {
                if ($anyFieldValue instanceof RestSObject) {
                    $recurse = $this->getQueryResultHeaders($anyFieldValue, $tail . htmlspecialchars($anyFieldName,ENT_QUOTES) . ".");
                    $headerBufferArray = array_merge($headerBufferArray, $recurse);
                } else if ($anyFieldValue instanceof RestQueryResult) {
                    $headerBufferArray[] = $anyFieldValue->records[0]->type;
                } else {
                    $headerBufferArray[] = $tail . htmlspecialchars($anyFieldName,ENT_QUOTES);
                }
            }
        }

        return $headerBufferArray;
    }

    function getQueryResultRow($sobject, $escapeHtmlChars=true) {
        $rowBuffer = array();

        if (isset($sobject->anyFields)) {
            foreach ($sobject->anyFields as $anyFieldName => $anyFieldValue) {
                if ($anyFieldValue instanceof RestSObject) {
                    $rowBuffer = array_merge($rowBuffer, $this->getQueryResultRow($anyFieldValue,$escapeHtmlChars));
                } else if ($anyFieldValue instanceof RestQueryResult) {
                    $rowBuffer[] = $anyFieldValue;
                } else {
                    $rowBuffer[] = ($escapeHtmlChars ? htmlspecialchars($anyFieldValue,ENT_QUOTES) : $anyFieldValue);
                }
            }
        }

        return localizeDateTimes($rowBuffer);
    }
}
?>