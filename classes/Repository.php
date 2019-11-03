<?php
include "GradleFile.php";
include "config.inc";

class Repository{
    public $repo;
    public $rootGradle;
    public $modulesGradle;
    public $targetSdkVersionChanges = array();

    public function __construct( $repo ){
        $this->repo = $repo;
        $this->rootGradle = GradleFile::factoryMaster( $this->repo, GIT_BRANCH_DEFAULT, "build.gradle", null );



        $moduleNames = $this->loadModuleNames();
        foreach( $moduleNames as $moduleName ){
            $this->modulesGradle[] = GradleFile::factoryMaster( $this->repo, GIT_BRANCH_DEFAULT, $moduleName . "/build.gradle", $this->rootGradle );
        }


        echo "<pre>";
        foreach( $this->modulesGradle as $moduleGradle ){
            if( $moduleGradle->histories[ "targetSdkVersion" ] ){
                foreach( $moduleGradle->histories[ "targetSdkVersion" ] as $targetSdkVersionChange ){
                    $key = $targetSdkVersionChange->commit->date[ "year" ] 
                         . "-" . $targetSdkVersionChange->commit->date[ "month" ] 
                         . "-" . $targetSdkVersionChange->commit->date[ "day" ];
                    $this->targetSdkVersionChanges[ $key ][] = $targetSdkVersionChange->newValue;
                }
            }
        }
        ksort( $this->targetSdkVersionChanges );
        print_r( $this->targetSdkVersionChanges );

    }

    private function loadModuleNames(){
        $settingsFile = new GitFile( $this->repo, GIT_BRANCH_DEFAULT, "settings.gradle" );
        $settingsFile->load();

        $content = $settingsFile->content;
        $content = str_replace( '"', "'", $content );
        $content = str_replace( ' ', "", $content );

        $matches = array();
        $pattern = "/(file\(\')([a-zA-Z0-9\/\.\_\-]{1,})(\'\))/";
        preg_match_all( $pattern, $content, $matches );
        return $matches[ 2 ];
    }
}