<?php
class CommitEntity{
    public $hash;
    public $date;

    public function __construct( $date, $hash ){
        $this->date = $date;
        $this->hash = $hash;
    }
}