<?php
class RestSObject extends SObject {
    public $type;
    public $anyFields;

    public function __construct($response=NULL) {
        if (isset($response)) {
            if (isset($response->Id)) $this->Id = $response->Id;
            if (isset($response->attributes->type)) $this->type = $response->attributes->type;
            if (isset($response->fieldsToNull)) $this->fieldsToNull = $response->fieldsToNull;
            foreach (get_object_vars($response) as $name => $value) {
                if ($name == "attributes") {
                    continue;
                } else if (isset($value->attributes)) {
                    $this->anyFields[$name] = new RestSObject($value);
                } else if (isset($value->totalSize, $value->done)) {
                    $this->anyFields[$name] = new RestQueryResult($value);
                } else if (endsWith($name,'Address', false) && WorkbenchContext::get()->isApiVersionAtLeast(30.0) && $value instanceof stdClass ) {
                    // TODO: Address and > 30.0 guard should not be needed, but protecting against other stdClass results that might leak in
                    $this->anyFields[$name] = new RestSObject($value);
                } else {
                    $this->anyFields[$name] = $value;
                }
            }
        }
    }
}

class RestQueryResult extends QueryResult {
    public $nextRecordsUrl;

    public function __construct($response) {
        if (isset($response->nextRecordsUrl)) {
            $this->nextRecordsUrl = $response->nextRecordsUrl;
            $this->queryLocator = preg_replace('!/services/data/v.{4}/query/!', '', $response->nextRecordsUrl);
        }
        $this->done = $response->done;
        $this->size = $response->totalSize;

        $this->records = array();

        if (isset($response->records)) {
            if (!is_array($response->records)) {
                $response->records = array($response->records);
            }

            foreach ($response->records as $record) {
                $this->records[] =  new RestSObject($record);;
            }
        }
    }
}
?>