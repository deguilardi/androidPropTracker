<?php
include_once( "classes/Repository.php" );
include_once( "classes/Result.php" );

class Results{

	public $resultsByPeriod = array();
	public $resultsByValue = array();
	public $resultsContinuous = array();
	public $hasProjects = false;
	public $hasResults = false;
	public $max = 0;
	public $maxContinuous = 0;

	public $repoCounts = array(
		"all" => 0,
		"duplicated" => 0,
		"unique" => 0,
		"ignored" => 0,
		"withNoProjectDetected" => 0,
		"willTryToDetectChanges" => 0,
		"withNoChangesDetected" => 0,
		"withChangesDetected" => 0,
	);

	public $repoLists = array(
		"duplicated" => array(),
		"ignored" => array(),
		"withNoProjectDetected" => array(),
		"withNoChangesDetected" => array(),
		"withChangesDetected" => array(),
	);

	public function __construct( $repositories, $granularity ){
		if( !$repositories || !sizeof( $repositories) ){ return; };
		$this->hasProjects = true;

		// discard repeated repositories
		$this->setNumRepositories( "all", sizeof( $repositories ) );
		$hashSet = array();
		foreach( $repositories as $key => $repository ){
			if( array_key_exists( $repository[ "repositoryString" ], $hashSet ) ){
				unset( $repositories[ $key ] );
				$this->addResult( "duplicated", $repository );
			}
			else{
				$hashSet[ $repository[ "repositoryString" ] ] = true;
			}
		}
		$this->setNumRepositories( "unique", sizeof( $repositories ) );

		$repos = array();
		foreach( $repositories as $repository ){
			switch( $repository[ "state" ] ){
    			case Result::NO_PROJECT_DETECTED:
    				$this->addResult( "withNoProjectDetected", $repository );
    				break;

    			case Result::IGNORED:
	            	$this->addResult( "ignored", $repository );
    				break;

    			case Result::WILL_TRY_TO_DETECT_CHANGES:
    				foreach( $repository[ "repositories"] as $innerRepository ){
    					$repos[] = $innerRepository;
    				}
    				break;
			}
		}

		// determine min and max months
		$minMonth = date( "Y-m" );
		$maxMonth = "2006-01";
		foreach( $repos as $repo ){
			if( !array_key_exists( "propertyChanges", $repo ) ){ continue; }
			$repoMinMonth = array_key_first( $repo[ "propertyChanges" ] );
			$minMonth = $repoMinMonth && $repoMinMonth < $minMonth
			          ? $repoMinMonth
			          : $minMonth;
			$repoMaxMonth = array_key_last( $repo[ "propertyChanges" ] );
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
			$key = $this->getPeriodWithGranularity( $key, $granularity );
			$this->resultsByPeriod[ $key ] = array();
			// $this->resultsContinuous[ $key ] = array();
			$month++;
			if( $month > 12 ){
				$month = 1;
				$year++;
			}
		}

		// distribute results into periods
		foreach( $repos as $repo ){
			if( !array_key_exists( "propertyChanges", $repo ) ){ continue; }
			foreach( $repo[ "propertyChanges"] as $period => $changes ){
				$numItems = sizeof( $changes );
				foreach( $changes as $propValue => $change ){
					if( $propValue == GradleFile::NOT_LOADED ){ continue; }
					if( $propValue < RANGE_MIN || $propValue > RANGE_MAX ){ continue; }
					if( array_key_exists( $propValue, $this->resultsByPeriod[ $period ] ) ){
						$this->resultsByPeriod[ $period ][ $propValue ] += $change;
					}
					else{
						$this->resultsByPeriod[ $period ][ $propValue ] = $change;
					}
					$this->calculateMax( $this->resultsByPeriod[ $period ][ $propValue ] );
				}
			}
		}

		// results grouped by value
		$this->resultsByValue = array();
		foreach( $repos as $repo ){
			if( !array_key_exists( "propertyChanges", $repo ) ){ continue; }
			foreach( $repo[ "propertyChanges"] as $period => $changes ){
				foreach( $changes as $propValue => $change ){
					if( $propValue == GradleFile::NOT_LOADED ){ continue; }
                	if( $propValue < RANGE_MIN || $propValue > RANGE_MAX ){ continue; }

					// initialize with zeroes
					if( !array_key_exists( $propValue, $this->resultsByValue ) ){
						foreach( $this->resultsByPeriod as $key2 => $result ){
							$this->resultsByValue[ $propValue ][ $key2 ] = 0;
						}
					}

					$this->resultsByValue[ $propValue ][ $period ] += $change;
				}
			}
		}

		foreach( $repos as $repo ){
			if( !array_key_exists( "propertyChangesContinuous", $repo ) ){ continue; }
			foreach( $repo[ "propertyChangesContinuous" ] as $period => $changes ){
				foreach( $changes as $propValue => $change ){
					if( $propValue == GradleFile::NOT_LOADED ){ continue; }
                	if( $propValue < RANGE_MIN || $propValue > RANGE_MAX ){ continue; }

					// initialize with zeroes
					if( !array_key_exists( $propValue, $this->resultsContinuous ) ){
						foreach( $this->resultsByPeriod as $key2 => $result ){
							$this->resultsContinuous[ $propValue ][ $key2 ] = 0;
						}
					}

					$this->resultsContinuous[ $propValue ][ $period ] += $change;
					$this->calculateMaxContinuous( $this->resultsContinuous[ $propValue ][ $period ] );
				}
			}

			$lastPeriod = array_key_last( $repo[ "propertyChangesContinuous" ] );
			if( $lastPeriod !== false && is_array( $repo[ "propertyChangesContinuous" ][ $lastPeriod ] ) ){
				foreach( $repo[ "propertyChangesContinuous" ][ $lastPeriod ] as $propValue => $change ){
                	if( $propValue < RANGE_MIN || $propValue > RANGE_MAX ){ continue; }
					$this->fillResultsGap( 
						$this->resultsContinuous, 
						$propValue, 
						$change, 
						$lastPeriod, 
						$maxMonth
					);
				}
			}
		}

		// has results?
		foreach( $repos as $repo ){
			if( array_key_exists( "propertyChanges", $repo ) && is_array( $repo[ "propertyChanges" ] ) && sizeof( $repo[ "propertyChanges" ] ) ){
	            $this->addResult( "withChangesDetected", $repo );
			}
			else{
	            $this->addResult( "withNoChangesDetected", $repo );
			}
		}

		if( sizeof($this->resultsByValue) ){
			$this->hasResults = true;
	    	ksort( $this->resultsByValue );
			ksort( $this->resultsContinuous );
		}
	}

	public function toJson(){
		return json_encode( $this );
	}

	private function fillResultsGap( &$target, $propValue, $change, $ini, $end ){
		if( $ini == $end ){ return; }
        $key = $ini;
		$key = $this->incrementPeriod( $key );
		$end = $this->incrementPeriod( $end );
        $i = 0;
        while( $key != $end && $i < 100 ){
            $target[ $propValue ][ $key ] += $change;
            $key = $this->incrementPeriod( $key );
            $i++;
        }
    }

    private function incrementPeriod( $key ){
        $year = substr( $key, 0, 4 );
        $month = substr( $key, 5, 2 );
        $month++;
        if( $month > 12 ){
            $month = 1;
            $year++;
        }
        $key = $year . "-" . str_pad( $month, 2, '0', STR_PAD_LEFT );
        $key = $this->getPeriodWithGranularity( $key, "monthly" );
        return $key;
    }

	private function setNumRepositories( $key, $val ){
		$this->repoCounts[ $key ] = $val;
	}

	private function incrementNumRepositories( $key ){
		$this->repoCounts[ $key ]++;
	}

	private function addResult( $key, $repository ){
		$this->incrementNumRepositories( $key );
		$this->repoLists[ $key ][] = $repository;
	}

	private function getPeriodWithGranularity( $value, $granularity ){
		if( $granularity == "quartely" ){
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
		$this->max = max( $value, $this->max );
	}

	private function calculateMaxContinuous( $value ){
		$this->maxContinuous = max( $value, $this->maxContinuous );
	}
}
?>