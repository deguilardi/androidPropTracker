<?php
include "classes/Repository.php";

class Results{

	private $resultsByPeriod = array();
	private $resultsByValue = array();
	public $hasProjects = false;
	public $hasResults = false;
	private $max = 0;
	public $reposWithNoChangesDetected = array();
	public $reposWithChangesDetected = array();

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
				$minMonth = $repoMinMonth && $repoMinMonth < $minMonth
				          ? $repoMinMonth
				          : $minMonth;
				$repoMaxMonth = array_key_last( $repo->propertyChanges[ "monthly" ] );
				$maxMonth = $repoMaxMonth && $repoMaxMonth > $maxMonth
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

			// distribute results into periods
			foreach( $repos as $repo ){
				foreach( $repo->propertyChanges[ $granulatity ] as $period => $changes ){
					$numItems = sizeof( $changes );
					foreach( $changes as $propValue => $change ){
						if( $propValue == GradleFile::NOT_LOADED ){ continue; }
						$this->resultsByPeriod[ $period ][ $propValue ] += $change;
						$this->calculateMax( $this->resultsByPeriod[ $period ][ $propValue ] );
					}
				}
			}

			// results grouped by value
			$this->resultsByValue = array();
			foreach( $repos as $repo ){
				foreach( $repo->propertyChanges[ $granulatity ] as $period => $changes ){
					foreach( $changes as $propValue => $change ){
						if( $propValue == GradleFile::NOT_LOADED ){ continue; }

						// initialize with zeroes
						if( !$this->resultsByValue[ $propValue ] ){
							foreach( $this->resultsByPeriod as $key2 => $result ){
								$this->resultsByValue[ $propValue ][ $key2 ] = 0;
							}
						}

						$this->resultsByValue[ $propValue ][ $period ] += $change;
					}
				}
			}

			// has results?
			foreach( $repos as $repo ){
				if( sizeof( $repo->propertyChanges[ $granulatity ] ) ){
					$this->reposWithChangesDetected[] = $repo;
				}
				else{
					$this->reposWithNoChangesDetected[] = $repo;
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

	public function getColorForValue( $value, $resultHeatColors ){
		$index = ceil( $value / $this->max * sizeof( $resultHeatColors ) ) - 1;
		return( $resultHeatColors[ $index ] );
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

	private function calculateMax( $value ){
		$this->max = ( $value > $this->max ) ? $value : $this->max;
	}
}
?>