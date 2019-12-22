<?php
include_once( "classes/Repository.php" );

class Result{

    public const NONE = -1;
    public const NO_PROJECT_DETECTED = 3;
    public const IGNORED = 4;
    public const WILL_TRY_TO_DETECT_CHANGES = 5;

    public $state = Result::NONE;
    public $repositoryString;
    public $repositories = array();

    public function __construct( $repositoryString, $granularity ){
        if( ! $repositoryString ){ return; }
        $this->repositoryString = $repositoryString;
        $parts = explode( ":", $repositoryString );
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
                        $this->repositories[] = $innerRepository;
                        $projecDetectedInFolder = true;
                    }
                }
                if( !$projecDetectedInFolder ){
                    $this->state = Result::NO_PROJECT_DETECTED;
                }
                else{
                    $this->state = Result::WILL_TRY_TO_DETECT_CHANGES;
                }
                break;

            case Repository::IGNORED:
                $this->state = Result::IGNORED;
                break;

            case Repository::NO_PROJECT_DETECTED:
                $this->state = Result::NO_PROJECT_DETECTED;
                break;

            default:
                $this->state = Result::WILL_TRY_TO_DETECT_CHANGES;
                $this->repositories[] = $repository;
                break;
        }
    }

    public function toJson(){
        return json_encode( $this );
    }
}
?>