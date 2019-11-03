<?php
include "GradleFile.php";
include "config.inc";

class Repository{
    public $repo;
    public $rootGradle;
    public $moduleGradle;

    public function __construct( $repo ){
        $this->repo = $repo;
        $this->rootGradle = GradleFile::factoryMaster( $this->repo, GIT_BRANCH_DEFAULT, "build.gradle", null );
        $this->moduleGradle = GradleFile::factoryMaster( $this->repo, GIT_BRANCH_DEFAULT, "/android/build.gradle", $this->rootGradle );
    }
}