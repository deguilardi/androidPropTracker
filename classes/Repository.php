<?php
include "GradleFile.php";
include "config.inc";

class Repository{
    public $repo;
    public $rootGradle;
    public $moduleGradle;

    public function __construct( $repo ){
        $this->repo = $repo;
        $this->rootGradle = new GradleFile( $this, "build.gradle", null );
        // $this->moduleGradle = new GradleFile( $repo . "/" . GIT_BRANCH_DEFAULT . "/android/build.gradle", $this->rootGradle );


    }
}