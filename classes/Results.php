<?php
include "classes/Repository.php";

class Results{

	private $resultsByPeriod = array();
	private $resultsByValue = array();
	public $hasProjects = false;
	public $hasResults = false;

	public function __construct( $projects, $granulatity ){
		if( @sizeof( $projects) ){
			$this->hasProjects = true;
			$repos = array();
			foreach( $projects as $project ){
				$parts = explode( ":", $project );
				$repoEntity = new RepositoryEntity( $parts[ 0 ], $parts[ 1 ], $parts[ 2 ] );
				$repos[] = new Repository( $repoEntity );
			}

			// determine min and max months
			$minMonth = date( "Y-m" );
			$maxMonth = "2006-01";
			foreach( $repos as $repo ){
				$repoMinMonth = array_key_first( $repo->propertyChanges[ "monthly" ] );
				$minMonth = $repoMinMonth < $minMonth
				          ? $repoMinMonth
				          : $minMonth;
				$repoMaxMonth = array_key_last( $repo->propertyChanges[ "monthly" ] );
				$maxMonth = $repoMaxMonth > $maxMonth
				          ? $repoMaxMonth
				          : $maxMonth;
			}
			$maxMonth = ( $maxMonth == "2006-01" ? $minMonth : $maxMonth );

			// determine diff in months
			$datetimeMin = date_create( $minMonth . "-01" ); 
			$datetimeMax = date_create( $maxMonth . "-01" );
			$interval = date_diff( $datetimeMin, $datetimeMax );
			$diffInMonths = $interval->format( '%y' ) * 12 + $interval->format( '%m' ) + 1;

			// init empty results array
			$year = date_format( $datetimeMin, "Y" );
			$month = date_format( $datetimeMin, "m" );
			for( $i = 0; $i < $diffInMonths; $i++ ){
				$key = $year . "-" . str_pad( $month, 2, '0', STR_PAD_LEFT );
				$key = $this->getPeriodWithGranulatity( $key, $granulatity );
				$this->resultsByPeriod[ $key ] = array();
				$month++;
				if( $month > 12 ){
					$month = 1;
					$year++;
				}
			}

			// distribute results into period
			foreach( $repos as $repo ){
				foreach( $repo->propertyChanges[ $granulatity ] as $period => $changes ){
					foreach( $changes as $key => $change ){
						$this->resultsByPeriod[ $period ][ $key ] += $change;
					}

				}
			}

			// results grouped by value
			$this->resultsByValue = array();
			foreach( $repos as $repo ){
				foreach( $repo->propertyChanges[ $granulatity ] as $period => $changes ){
					foreach( $changes as $key => $change ){
						if( $key == GradleFile::NOT_LOADED ){ continue; }

						// initialize with zeroes
						if( !$this->resultsByValue[ $key ] ){
							foreach( $this->resultsByPeriod as $key2 => $result ){
								$this->resultsByValue[ $key ][ $key2 ] = 0;
							}
						}

						$this->resultsByValue[ $key ][ $period ] += $change;
					}
				}
			}
			if( sizeof($this->resultsByValue) ){
				$this->hasResults = true;
		    	ksort( $this->resultsByValue );
			}
		}
	}

	public function getResultsByPeriod(){
		return $this->resultsByPeriod;
	}

	public function getResultsByValue(){
		return $this->resultsByValue;
	}

	private function getPeriodWithGranulatity( $value, $granulatity ){
		if( $granulatity == "quartely" ){
			$year = substr( $value, 0, 4 );
			$month = (int) substr( $value, -2 );
			$quarter = ceil( $month / 3 );
			return $year . "-q" . $quarter;
		}
		else{
			return $value;
		}
	}
}
?>