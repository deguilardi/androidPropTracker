<?php
include_once( "GradleFile.php" );
include_once( "RepositoryCache.php" );
include_once( "config.inc" );
include_once( "MyDOMDocument.php" );
include_once( "MyDOMNode.php" );

class Repository{

    public const NONE = -1;
    public const PROJECT_DETECTED = 1;
    public const PROJECT_DETECTED_IN_FOLDER = 2;
    public const NO_PROJECT_DETECTED = 3;
    public const IGNORED = 4;

    public $repoEntity;
    public $rootGradle;
    public $modulesGradle = array();
    public $propertyChanges = array();
    public $propertyChangesContinuous = array();
    public $state = Repository::NONE;
    public $folders = array();
    private $granularity = "monthly";

    public function __construct( $repoEntity, $granularity ){
        global $ignoredRepositoriesNames;
        $this->repoEntity = $repoEntity;

        // ignore some repositories with some strings in their names
        if( ENABLE_IGNORING_NAMES ){
            foreach( $ignoredRepositoriesNames as $ignoredName ){
                if( strpos( strtolower( $repoEntity->repo ), $ignoredName ) !== false ){
                    $this->state = Repository::IGNORED;
                    $this->repoEntity = $repoEntity;
                    return;
                }
            }
        }

        // extract main branch
        if( !$this->repoEntity->branch ){
            $this->repoEntity->branch = $this->extractDefaultBranch();
        }
        $this->rootGradle = GradleFile::factoryRootLastVersion( $this->repoEntity, "build.gradle", null );

        // load cache
        if( ENABLE_CACHE && ENABLE_CACHE_RESULTS ){
            $cache = RepositoryCache::factoryResultsWithRepoEntity( $this->repoEntity );
            if( $cache ){
                $this->repoEntity = new RepositoryEntity( $cache[ "repoEntity" ][ "repo" ], $cache[ "repoEntity" ][ "branch" ], $cache[ "repoEntity" ][ "folder" ], );
                $this->propertyChanges = $cache[ "propertyChanges" ];
                $this->propertyChangesContinuous = $cache[ "propertyChangesContinuous" ];
                $this->state = $cache[ "state" ];
                $this->folders = $cache[ "folders" ];
                return;
            }
        }

        // root can also be the main module file
        // which means there is no modules
        // in this case the module file is just like the root one
        // but with no parent
        if( $this->rootGradle->isApplicationFile ){
            $this->modulesGradle[] = GradleFile::factoryModuleLastVersion( $this->repoEntity, "/build.gradle", null );
        }
        else{
            $moduleNames = $this->loadModuleNames();
            foreach( $moduleNames as $moduleName ){
                $this->modulesGradle[] = GradleFile::factoryModuleLastVersion( $this->repoEntity, $moduleName . "/build.gradle", $this->rootGradle );
            }
        }

        // no modules defined, will use "app" as default
        if( sizeof( $this->modulesGradle ) == 0 ){
            $this->modulesGradle[] = GradleFile::factoryModuleLastVersion( $this->repoEntity, "app" . "/build.gradle", $this->rootGradle );

            // the only module added wasn't loaded
            // the probable cause is the main gradle file is in a folder
            if( $this->modulesGradle[ 0 ]->hasError && !$this->repoEntity->folder ){
                $folders = $this->extractFolders();
                $this->state = sizeof( $folders ) > 0 ? Repository::PROJECT_DETECTED_IN_FOLDER : Repository::NO_PROJECT_DETECTED;
                $this->folders = $folders;
            }
            else if( !$this->modulesGradle[ 0 ]->hasError ){
                $this->state = Repository::PROJECT_DETECTED;
            }
            else{
                $this->state = Repository::NO_PROJECT_DETECTED;
            }
            RepositoryCache::save( $this );
            return;
        }
        else{
            $this->state = Repository::PROJECT_DETECTED;
        }

        $this->makePropertyChanges();
        RepositoryCache::save( $this );
    }

    private function makePropertyChanges(){
        $finalValues = array();
        foreach( $this->modulesGradle as $moduleGradle ){
            if( $moduleGradle->propertyHistory ){
                $prevKey = null;
                $prevValue = null;
                foreach( $moduleGradle->propertyHistory as $targetSdkVersionChange ){
                    $key = $targetSdkVersionChange->getFormatedDate( $this->granularity );
                    if( !array_key_exists( $key, $this->propertyChanges ) ){
                        $this->propertyChanges[ $key ] = array();
                    }
                    if( array_key_exists( $targetSdkVersionChange->newValue, $this->propertyChanges[ $key ] ) ){
                        $this->propertyChanges[ $key ][ $targetSdkVersionChange->newValue ]++;
                    }
                    else{
                        $this->propertyChanges[ $key ][ $targetSdkVersionChange->newValue ] = 1;
                    }
                    if( !$prevKey ){
                        $finalValues[] = $targetSdkVersionChange;
                    }
                    else{
                        $this->fillResultsGap( $this->propertyChangesContinuous, $prevValue, $key, $prevKey );
                    }
                    $prevKey = $key;
                    $prevValue = $targetSdkVersionChange->oldValue;
                }
            }
        }

        ksort( $this->propertyChanges );
        ksort( $this->propertyChangesContinuous );
        ksort( $finalValues );

        // find the very last period
        $lastPeriod = array_key_last( $this->propertyChangesContinuous );
        if( $lastPeriod ){
            foreach( $finalValues as $finalValue ){
                if( $finalValue->commit->isAfterThanWithString( $lastPeriod ) ){
                    $lastPeriod = $finalValue->commit->date[ "year" ] . "-" . str_pad( $finalValue->commit->date[ "month" ], 2, '0', STR_PAD_LEFT );
                }
            }
        }

        // fill the remaining gaps
        if( $lastPeriod ){
            foreach( $finalValues as $finalValue ){
                $period = $finalValue->getFormatedDate( $this->granularity );
                $this->fillResultsGap( $this->propertyChangesContinuous, $finalValue->newValue, $period, $lastPeriod );
                if( !array_key_exists( $lastPeriod, $this->propertyChangesContinuous ) ){
                    $this->propertyChangesContinuous[ $lastPeriod ] = array();
                }
                if( array_key_exists( $finalValue->newValue, $this->propertyChangesContinuous[ $lastPeriod ] ) ){
                    $this->propertyChangesContinuous[ $lastPeriod ][ $finalValue->newValue ]++;
                }
                else{
                    $this->propertyChangesContinuous[ $lastPeriod ][ $finalValue->newValue ] = 1;
                }
            }
        }
        else{
            $this->propertyChangesContinuous = $this->propertyChanges;
        }
    }

    private function fillResultsGap( &$target, $propValue, $ini, $end ){
        $key = $ini;
        $i = 0;
        while( $key != $end && $i < 100 ){
            if( !array_key_exists( $key, $target ) ){
                $target[ $key ] = array();
            }
            if( array_key_exists( $propValue, $target[ $key ] ) ){
                $target[ $key ][ $propValue ]++;
            }
            else{
                $target[ $key ][ $propValue ] = 1;
            }
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

    private function loadModuleNames(){

        // load file
        $settingsFile = new GitFile( $this->repoEntity, "settings.gradle" );
        $settingsFile->load();

        // some projects have kts suffix
        if( $settingsFile->hasError ){
            $settingsFile = new GitFile( $this->repoEntity, "settings.gradle.kts" );
            $settingsFile->load();
        }

        if( $settingsFile->hasError ){
            return array();
        }


        // apply some filters
        $content = $settingsFile->content;
        $content = str_replace( '"', "'", $content );
        $content = str_replace( ' ', "", $content );

        // extract all includes
        $matches = array();
        $pattern = "/(include\')([a-zA-Z0-9\'\:\-\,]{1,})(\')/";
        preg_match_all( $pattern, $content, $matches );
        $groupedModules = $matches[ 2 ];

        // implode includes into single entries
        $modules = array();
        foreach( $groupedModules as $groupedModule ){
            $groupedModule = str_replace( "'", "", $groupedModule );
            // $groupedModule = str_replace( ":", "/", $groupedModule );
            $modulesTemp = explode( ",", $groupedModule );
            $modules = array_merge( $modules, $modulesTemp );
        }

        // match modules with their spscific paths (if specified)
        $matches = array();
        $pattern = "/(project\(\')([a-zA-Z0-9\:\-\,]{1,})(\'\)\.projectDir\=)([new]{0,}[fF]{1,}ile\([rootDir,]{0,}\')([a-zA-Z0-9\/\.\_\-]{1,})(\'\))/";
        preg_match_all( $pattern, $content, $matches );
        $matchesModules = $matches[ 2 ];
        $matchesPaths = $matches[ 5 ];
        if( is_array( $matchesModules ) ){
            foreach( $modules as $k => $module ){
                $key = array_search( $module, $matchesModules );
                if( $key !== false ){
                    $path = $matchesPaths[ $key ];
                    $modules[ $k ] = $path;
                }
            }
        }

        // normalize output
        foreach( $modules as $k => $module ){
            $module = str_replace( ":", "/", $module );
            if( strpos( $module, "/" ) === 0 ){
                $module = substr( $module, 1 );
            }
            $modules[ $k ] = $module;
        }

        return $modules;
    }

    private function extractDefaultBranch(){
        $fileNavigation = $this->getMainProjectGitPageElementWithClass( "file-navigation" );
        if( $fileNavigation ){
            $detailsElem = $fileNavigation->childWithTag( "details" );
            $summaryElem = $detailsElem->childWithTag( "summary" );
            $branchName = $summaryElem->childAt( 3 )->childAt( 0 )->getNode()->wholeText;

            // long branch names have elipsis
            // example: https://github.com/WycliffeAssociates/translationRecorder
            $hasElipsis = strpos( $branchName, "…" );
            return( $hasElipsis ) ? $summaryElem->getAttr( "title" ) : $branchName;
        }
        else{
            return null;
        }
    }

    private function extractFolders(){
        $output = array();
        $fileWrap = $this->getMainProjectGitPageElementWithClass( "Box mb-3" );
        if( $fileWrap ){
            $includeElem = $fileWrap->childWithTag( "include-fragment" );
            if( $includeElem ){
                $tableElem = $includeElem->childWithTag( "table" );
            }
            else{
                $tableElem = $fileWrap->childWithTag( "table" );
            }
            $tableBody = $tableElem->childWithTag( "tbody" );
            if( $tableBody ){
                foreach( $tableBody->getNode()->childNodes as $item ){
                    if( $item->nodeType != XML_ELEMENT_NODE || $item->getAttribute("class") != "js-navigation-item" ){ continue; }
                    $icon = $item->childNodes->item( 1 )->childNodes->item( 1 );
                    if( $icon->getAttribute( "aria-label" ) != "directory" ){
                        continue;
                    }

                    $contentElem = $item->childNodes->item( 3 )->childNodes->item( 1 );
                    $linkElem = $contentElem->childNodes->item( 0 );
                    $output[] = $linkElem->nodeValue;
                }
            }
        }
        return $output;
    } 

    private function getMainProjectGitPageElementWithClass( $class ){
        $url = $this->repoEntity->getRootUrl();
        $projectRootFile = new CacheableFile( $url, $this->repoEntity->repo );
        $projectRootFile->load();

        if( !$projectRootFile->content ){
            return false;
        }

        $htmlDoc = new MyDOMDocument( $projectRootFile->content );
        $htmlElem = $htmlDoc->childWithTag( "html" );
        $bodyElem = $htmlElem->childWithTag( "body" );
        $appElem = $bodyElem->childWithClass( "application-main" );
        $mainElem = $appElem->childAt( 1 )->childWithTag( "main" );
        $containerElem = $mainElem->childWithClass( "container" );
        $repoContentElem = $containerElem->childWithClass( "repository-content" );
        return $repoContentElem->childWithClass( $class );
    }
}