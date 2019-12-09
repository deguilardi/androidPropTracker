<?php
include "classes/Repository.php";

class Results{

	private $resultsByPeriod = array();
	private $resultsByValue = array();
	private $resultsContinuous = array();
	public $hasProjects = false;
	public $hasResults = false;
	private $max = 0;

	private $repoCounts = array(
		"all" => 0,
		"duplicated" => 0,
		"unique" => 0,
		"ignored" => 0,
		"withNoProjectDetected" => 0,
		"willTryToDetectChanges" => 0,
		"withNoChangesDetected" => 0,
		"withChangesDetected" => 0,
	);

	private $repoLists = array(
		"ignored" => array(),
		"withNoProjectDetected" => array(),
		"withNoChangesDetected" => array(),
		"withChangesDetected" => array(),
	);

	public function __construct( $repositories, $granularity ){
		if( !$repositories || !sizeof( $repositories) ){ return; };
		$this->hasProjects = true;

		// discard non repeated repositories
		$this->setNumRepositories( "all", sizeof( $repositories ) );
		$repositories = array_unique( $repositories );
		$this->setNumRepositories( "unique", sizeof( $repositories ) );
		$this->setNumRepositories( "duplicated", $this->repoCounts[ "all" ] - $this->repoCounts[ "unique" ] );


		$repos = array();
		foreach( $repositories as $item ){
			$parts = explode( ":", $item );
			$repoEntity = new RepositoryEntity( $parts[ 0 ], $parts[ 1 ], $parts[ 2 ] );
			$repository = new Repository( $repoEntity, $granularity );

			// make different lists depending on each repository state
			switch( $repository->state ){
				case Repository::PROJECT_DETECTED_IN_FOLDER:
					$projecDetectedInFolder = false;
	                foreach( $repository->folders as $folder ){
	                    $innerRepoEntity = clone( $repoEntity );
	                    $innerRepoEntity->folder = $folder;
	                    $innerRepository = new Repository( $innerRepoEntity, $granularity );
	                    if( $innerRepository->state == Repository::PROJECT_DETECTED ){
	                    	$repos[] = $innerRepository;
	                    	$projecDetectedInFolder = true;
	                    }
	                }
	                if( !$projecDetectedInFolder ){
	            		$this->addResult( "withNoProjectDetected", $repository );
	                }
	                else{
	            		$this->incrementNumRepositories( "willTryToDetectChanges" );
	                }
	                break;

	            case Repository::IGNORED:
	            	$this->addResult( "ignored", $repository );
					break;

				case Repository::NO_PROJECT_DETECTED:
	            	$this->addResult( "withNoProjectDetected", $repository );
					break;

				default:
	            	$this->incrementNumRepositories( "willTryToDetectChanges" );
					$repos[] = $repository;
					break;
			}
		}	

		// determine min and max months
		$minMonth = date( "Y-m" );
		$maxMonth = "2006-01";
		foreach( $repos as $repo ){
			$repoMinMonth = array_key_first( $repo->propertyChanges );
			$minMonth = $repoMinMonth && $repoMinMonth < $minMonth
			          ? $repoMinMonth
			          : $minMonth;
			$repoMaxMonth = array_key_last( $repo->propertyChanges );
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
			foreach( $repo->propertyChanges as $period => $changes ){
				$numItems = sizeof( $changes );
				foreach( $changes as $propValue => $change ){
					if( $propValue == GradleFile::NOT_LOADED ){ continue; }
                	if( $propValue < RANGE_MIN || $propValue > RANGE_MAX ){ continue; }
					$this->resultsByPeriod[ $period ][ $propValue ] += $change;
					$this->calculateMax( $this->resultsByPeriod[ $period ][ $propValue ] );
				}
			}
		}

		// results grouped by value
		$this->resultsByValue = array();
		foreach( $repos as $repo ){
			foreach( $repo->propertyChanges as $period => $changes ){
				foreach( $changes as $propValue => $change ){
					if( $propValue == GradleFile::NOT_LOADED ){ continue; }
                	if( $propValue < RANGE_MIN || $propValue > RANGE_MAX ){ continue; }

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

		foreach( $repos as $repo ){
			foreach( $repo->propertyChangesContinuous as $period => $changes ){
				foreach( $changes as $propValue => $change ){
					if( $propValue == GradleFile::NOT_LOADED ){ continue; }
                	if( $propValue < RANGE_MIN || $propValue > RANGE_MAX ){ continue; }

					// initialize with zeroes
					if( !$this->resultsContinuous[ $propValue ] ){
						foreach( $this->resultsByPeriod as $key2 => $result ){
							$this->resultsContinuous[ $propValue ][ $key2 ] = 0;
						}
					}

					$this->resultsContinuous[ $propValue ][ $period ] += $change;
				}
			}

			$lastPeriod = array_key_last( $repo->propertyChangesContinuous );
			if( $lastPeriod !== false && is_array( $repo->propertyChangesContinuous[ $lastPeriod ] ) ){
				foreach( $repo->propertyChangesContinuous[ $lastPeriod ] as $propValue => $change ){
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
			if( sizeof( $repo->propertyChanges ) ){
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

	// private function fillResultsGap( &$target, $propValue, $change, $ini, $end ){
	// 	if( $ini == $end ){ return; }
 //        $key = $ini;
	// 	$key = $this->incrementPeriod( $key );
	// 	$end = $this->incrementPeriod( $end );
 //        $i = 0;
 //        while( $key != $end && $i < 100 ){
 //            $target[ $key ][ $propValue ] += $change;
 //            $key = $this->incrementPeriod( $key );
 //            $i++;
 //        }
 //    }

    private function incrementPeriod( $key ){
        $year = substr( $key, 0, 4 );
        $month = substr( $key, 5, 2 );
        $month++;
        if( $month > 12 ){
            $month = 1;
            $year++;
        }
        $key = $year . "-" . str_pad( $month, 2, '0', STR_PAD_LEFT );
        $key = $this->getPeriodWithGranularity( $key, $granularity );
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

	public function getRepositoriesResultCount( $key ){
		return $this->repoCounts[ $key ];
	}

	public function getRepositoriesResult( $key ){
		return $this->repoLists[ $key ];
	}

	public function getResultsByPeriod(){
		return $this->resultsByPeriod;
	}

	public function getResultsByValue(){
		return $this->resultsByValue;
	}

	public function getResultsContinuous(){
		return $this->resultsContinuous;
	}

	public function getColorForValue( $value, $resultHeatColors ){
		$index = ceil( $value / $this->max * sizeof( $resultHeatColors ) ) - 1;
		return( $resultHeatColors[ $index ] );
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
		$this->max = ( $value > $this->max ) ? $value : $this->max;
	}
}
?>