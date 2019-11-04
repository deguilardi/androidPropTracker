<?php
include "GradleFile.php";
include "config.inc";

class Repository{
    public $repoEntity;
    public $rootGradle;
    public $modulesGradle;
    public $targetSdkVersionChanges = array();

    public function __construct( $repo, $folder = "" ){
        $this->repoEntity = new RepositoryEntity( $repo, GIT_BRANCH_DEFAULT, $folder );
        $this->rootGradle = GradleFile::factoryMaster( $this->repoEntity, "build.gradle", null );

        $moduleNames = $this->loadModuleNames();
        foreach( $moduleNames as $moduleName ){
            $this->modulesGradle[] = GradleFile::factoryMaster( $this->repoEntity, $moduleName . "/build.gradle", $this->rootGradle );
        }

        foreach( $this->modulesGradle as $moduleGradle ){
            if( $moduleGradle->propertyHistory ){
                foreach( $moduleGradle->propertyHistory as $targetSdkVersionChange ){
                    $key = $targetSdkVersionChange->commit->date[ "year" ] 
                         . "-" . str_pad( $targetSdkVersionChange->commit->date[ "month" ], 2, '0', STR_PAD_LEFT ) 
                         . "-" . str_pad( $targetSdkVersionChange->commit->date[ "day" ], 2, '0', STR_PAD_LEFT );
                    $this->targetSdkVersionChanges[ $key ][] = $targetSdkVersionChange->newValue;
                }
            }
        }
        ksort( $this->targetSdkVersionChanges );

        echo "<pre>";
        print_r( $this->targetSdkVersionChanges );
        // print_r( $this->rootGradle );
    }

    private function loadModuleNames(){

        // load file
        $settingsFile = new GitFile( $this->repoEntity, "settings.gradle" );
        $settingsFile->load();

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
        $pattern = "/(project\(\')([a-zA-Z0-9\:\-\,]{1,})(\'\)\.projectDir\=)(file\(\')([a-zA-Z0-9\/\.\_\-]{1,})(\'\))/";
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
            $modules[ $k ] = str_replace( ":", "/", $module );
        }

        return $modules;
    }
}