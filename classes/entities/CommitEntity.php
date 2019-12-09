<?php
class CommitEntity{
    public $hash;
    public $date;

    public function __construct( $date, $hash ){
        $this->date = $date;
        $this->hash = $hash;
    }

    public function isAfterThan( $otherCommit ){
    	$compDate = $otherCommit->date;
		if( $this->date[ "year" ] > $compDate[ "year" ] ){
			return true;
		}
		else if( $this->date[ "year" ] == $compDate[ "year" ] ){
			if( $this->date[ "month" ] > $compDate[ "month" ] ){
				return true;
			}
			else if( $this->date[ "month" ] == $compDate[ "month" ] &&
			    $this->date[ "day" ] > $compDate[ "day" ] ){
				return true;
			}
		}
		return false;
    }
}