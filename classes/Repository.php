<?php
include "GradleFile.php";
include "config.inc";

class Repository{
    public $repoEntity;
    public $rootGradle;
    public $modulesGradle = array();
    public $propertyChanges = array( "quartely" => array(), "monthly" => array(), "daily" => array() );

    public function __construct( $repoEntity ){
        $this->repoEntity = $repoEntity;
        $this->rootGradle = GradleFile::factoryMaster( $this->repoEntity, "build.gradle", null );

        $moduleNames = $this->loadModuleNames();
        foreach( $moduleNames as $moduleName ){
            $this->modulesGradle[] = GradleFile::factoryMaster( $this->repoEntity, $moduleName . "/build.gradle", $this->rootGradle );
        }
        if( sizeof( $this->modulesGradle ) == 0 ){
            $this->modulesGradle[] = GradleFile::factoryMaster( $this->repoEntity, "app" . "/build.gradle", $this->rootGradle );
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
}