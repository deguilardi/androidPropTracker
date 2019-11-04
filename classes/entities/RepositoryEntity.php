<?php
class RepositoryEntity{
    public $repo;
    public $branch;

    public function __construct( $repo, $branch ){
        $this->repo = $repo;
        $this->branch = $branch;
    }

    public function getRawPathUrlForFile( $file ){
        return GIT_RAW_CODE_URL_BASE . $this->repo . "/" . $this->branch . "/" . $file;
    }

    public function getCommitsListUrlFirFile( $file ){
        return GIT_URL_BASE . $this->repo . "/commits/" . $this->branch . "/" . $file;
    }
}