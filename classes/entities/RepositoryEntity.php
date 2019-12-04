<?php
class RepositoryEntity{
    public $repo;
    public $branch;
    public $folder;

    public function __construct( $repo, $branch, $folder ){
        $this->repo = $repo;
        $this->branch = $branch;
        $this->folder = $folder;
    }

    public function getRootUrl(){
        return GIT_URL_BASE . $this->repo;
    }

    public function getRawPathUrlForFile( $file ){
        return GIT_RAW_CODE_URL_BASE . $this->repo
                                     . "/" . $this->branch
                                     . ( $this->folder ? "/" . $this->folder : "" )
                                     . "/" . $file;
    }

    public function getCommitsListUrlForFile( $file ){
        return GIT_URL_BASE . $this->repo
                            . "/commits"
                            . "/" . $this->branch
                            . ( $this->folder ? "/" . $this->folder : "" )
                            . "/" . $file;
    }

    public function getHash(){
        return md5( $this->repo . $this->branch . $this->folder );
    }
}