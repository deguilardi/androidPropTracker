<?php
include "GradleFile.php";
include "RepositoryCache.php";
include "config.inc";

class Repository{

    public const NONE = -1;
    public const PROJECT_DETECTED = 1;
    public const PROJECT_DETECTED_IN_FOLDER = 2;
    public const NO_PROJECT_DETECTED = 3;
    public const IGNORED = 4;

    public $repoEntity;
    public $rootGradle;
    public $modulesGradle = array();
    public $propertyChanges = array( "quartely" => array(), "monthly" => array(), "daily" => array() );
    public $state = Repository::NONE;
    public $folders = array();

    public function __construct( $repoEntity ){
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
        if( ENABLE_CACHE_RESULTS ){
            $cache = RepositoryCache::factoryResultsWithRepoEntity( $this->repoEntity );
            if( $cache ){
                $this->repoEntity = new RepositoryEntity( $cache[ "repoEntity" ][ "repo" ], $cache[ "repoEntity" ][ "branch" ], $cache[ "repoEntity" ][ "folder" ], );
                $this->propertyChanges = $cache[ "propertyChanges" ];
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

        foreach( $this->modulesGradle as $moduleGradle ){
            if( $moduleGradle->propertyHistory ){
                foreach( $moduleGradle->propertyHistory as $targetSdkVersionChange ){
                    $year = $targetSdkVersionChange->commit->date[ "year" ];
                    $month = $targetSdkVersionChange->commit->date[ "month" ];

                    // quartely
                    $quarter = ceil( $month / 3 );
                    $key = $year . "-q" . $quarter;
                    $this->propertyChanges[ "quartely" ][ $key ][ $targetSdkVersionChange->newValue ]++;

                    // monthly
                    $paddedMonth = str_pad( $month, 2, '0', STR_PAD_LEFT );
                    $key = $year . "-" . $paddedMonth;
                    $this->propertyChanges[ "monthly" ][ $key ][ $targetSdkVersionChange->newValue ]++;

                    // daily
                    $paddedDay = str_pad( $targetSdkVersionChange->commit->date[ "day" ], 2, '0', STR_PAD_LEFT );
                    $key .= "-" . $paddedDay;
                    $this->propertyChanges[ "daily" ][ $key ][ $targetSdkVersionChange->newValue ]++;
                }
            }
        }

        ksort( $this->propertyChanges[ "quartely" ] );
        ksort( $this->propertyChanges[ "monthly" ] );
        ksort( $this->propertyChanges[ "daily" ] );
        RepositoryCache::save( $this );
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
        $fileNavigation = $this->getMainProjectGitPageElementWithClass( "file-navigation in-mid-page d-flex flex-items-start" );
        if( $fileNavigation ){
            return $fileNavigation->childNodes->item( 1 )->childNodes->item( 1 )->childNodes->item( 3 )->childNodes->item( 0 )->wholeText;
            
        }
        else{
            return null;
        }
    }

    private function extractFolders(){
        $output = array();
        $fileWrap = $this->getMainProjectGitPageElementWithClass( "file-wrap" );
        if( $fileWrap ){
            $tableBody = $fileWrap->childNodes->item( 3 )->childNodes->item( 3 );
            if( $tableBody ){
                foreach( $tableBody->childNodes as $item ){
                    if( $item->nodeType != XML_ELEMENT_NODE || $item->getAttribute("class") != "js-navigation-item" ){ continue; }
                    $icon = $item->childNodes->item( 1 )->childNodes->item( 1 );
                    if( $icon->getAttribute( "aria-label" ) != "directory" ){
                        continue;
                    }

                    $content = $item->childNodes->item( 3 )->childNodes->item( 1 )->childNodes->item( 0 )->childNodes->item( 0 )->wholeText;
                    if( $content ){
                        $output[] = $content;
                    }
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

        $htmlDoc = new DOMDocument();
        libxml_use_internal_errors( true );
        $htmlDoc->loadHTML( $projectRootFile->content );
        $htmlElem = $htmlDoc->childNodes->item( 1 );
        $bodyElem = $htmlElem->childNodes->item( 3 );
        $appElem = $bodyElem->childNodes->item( 7 );
        $mainElem = $appElem->childNodes->item( 1 )->childNodes->item( 1 );

        // this element can be in many different positions
        foreach( $mainElem->childNodes as $item ){
            if( $item->nodeType == XML_ELEMENT_NODE && strpos( $item->getAttribute( "class" ), "container") !== false ){
                $containerElem = $item;break;
            }
        }

        $repoContentElem = $containerElem->childNodes->item( 1 );
        foreach( $repoContentElem->childNodes as $item ){
            if( $item->nodeType == XML_ELEMENT_NODE && $item->getAttribute( "class" ) == $class ){
                break;
            }
        }                
        return $item;
    }
}