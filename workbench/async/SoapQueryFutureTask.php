<?php
include_once "futures.php";

class SoapQueryFutureTask extends QueryFutureTask {
    function query($soqlQuery,$queryAction,$queryLocator = null) {
        if (!WorkbenchConfig::get()->value("allowParentRelationshipQueries") && preg_match("/SELECT.*?(\w+\.\w+).*FROM/i", $soqlQuery, $matches)) {

            $msg = "Parent relationship queries are disabled in Workbench: " . $matches[1];

            if (WorkbenchConfig::get()->overrideable("allowParentRelationshipQueries")) {
                $msg .= "\n\nDue to issues rendering query results, parent relationship queries are disabled by default. " .
                    "If you understand these limitations, parent relationship queries can be enabled under Settings. " .
                    "Alternatively, parent relationship queries can be run with REST Explorer under the Utilities menu without issue.";
            }

            throw new WorkbenchHandledException($msg);
        }

        try {
            if ($queryAction == 'Query') $queryResponse = WorkbenchContext::get()->getPartnerConnection()->query($soqlQuery);
            if ($queryAction == 'QueryAll') $queryResponse = WorkbenchContext::get()->getPartnerConnection()->queryAll($soqlQuery);
        } catch (SoapFault $e) {
            foreach ($this->knownErrors() as $known) {
                if (strpos($e->getMessage(), $known) > -1) {
                    throw new WorkbenchHandledException($e->getMessage());
                }
            }
            throw $e;
        }

        if ($queryAction == 'QueryMore' && isset($queryLocator)) $queryResponse = WorkbenchContext::get()->getPartnerConnection()->queryMore($queryLocator);

        return $queryResponse;
    }

    function getQueryResultHeaders($sobject, $tail="") {
        if (!isset($headerBufferArray)) {
            $headerBufferArray = array();
        }

        if (isset($sobject->Id) && !isset($sobject->fields->Id)) {
            $headerBufferArray[] = $tail . "Id";
        }

        if (isset($sobject->fields)) {
            foreach ($sobject->fields->children() as $field) {
                $headerBufferArray[] = $tail . htmlspecialchars($field->getName(),ENT_QUOTES);
            }
        }

        if (isset($sobject->sobjects)) {
            foreach ($sobject->sobjects as $sobjects) {
                $recurse = $this->getQueryResultHeaders($sobjects, $tail . htmlspecialchars($sobjects->type,ENT_QUOTES) . ".");
                $headerBufferArray = array_merge($headerBufferArray, $recurse);
            }
        }

        if (isset($sobject->queryResult)) {
            if(!is_array($sobject->queryResult)) $sobject->queryResult = array($sobject->queryResult);
            foreach ($sobject->queryResult as $qr) {
                $headerBufferArray[] = $qr->records[0]->type;
            }
        }

        return $headerBufferArray;
    }

    function getQueryResultRow($sobject, $escapeHtmlChars=true) {

        if (!isset($rowBuffer)) {
            $rowBuffer = array();
        }

        if (isset($sobject->Id) && !isset($sobject->fields->Id)) {
            $rowBuffer[] = $sobject->Id;
        }

        if (isset($sobject->fields)) {
            foreach ($sobject->fields as $datum) {
                $rowBuffer[] = ($escapeHtmlChars ? htmlspecialchars($datum,ENT_QUOTES) : $datum);
            }
        }

        if (isset($sobject->sobjects)) {
            foreach ($sobject->sobjects as $sobjects) {
                $rowBuffer = array_merge($rowBuffer, $this->getQueryResultRow($sobjects,$escapeHtmlChars));
            }
        }

        if (isset($sobject->queryResult)) {
            $rowBuffer[] = $sobject->queryResult;
        }

        return localizeDateTimes($rowBuffer);
    }
}
?>
