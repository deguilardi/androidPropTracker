<?php
class PropertyHistoryEntity{
    public $commit;
    public $oldValue;
    public $newValue;

    public function __construct( $commit, $oldValue, $newValue ){
        $this->commit = $commit;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }
}