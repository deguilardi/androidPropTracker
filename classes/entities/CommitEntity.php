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
    	return $this->_isAfterThanWithSeparatedComponents( $compDate[ "year" ], $compDate[ "month" ], $compDate[ "day" ] );
    }

    public function isAfterThanWithString( $compare ){
    	$year = substr( $compare, 0, 4 );
    	$month = substr( $compare, 5 );
    	$day = 1;
    	return $this->_isAfterThanWithSeparatedComponents( $year, $month, $day );
    }

    private function _isAfterThanWithSeparatedComponents( $year, $month, $day ){
		if( $this->date[ "year" ] > $year ){
			return true;
		}
		else if( $this->date[ "year" ] == $year ){
			if( $this->date[ "month" ] > $month ){
				return true;
			}
			else if( $this->date[ "month" ] == $month &&
			    $this->date[ "day" ] > $day ){
				return true;
			}
		}
		return false;
    }
}