<?php
class CacheableFile{

    private const DIR = "cached";

    public $id;
    protected $remoteFile;
    public $localFile;
    public $content;
    public $loaded = false;
    // @TODO check timestamp to reload
    // public $updateTimestamp; 

    public function __construct( $remoteFile ){
        $this->remoteFile = $remoteFile;
        $this->id = md5( $remoteFile );
        $this->localFile = self::DIR . "/" . $this->id;
    }

    public function load(){
        if( !$this->loadLocal() ){
            $this->loadRemote();
        }
    }

    public function clear(){
        unset( $this->content );
    }

    protected function loadLocal(){
        if( is_file( $this->localFile ) ){
            $this->content = file_get_contents( $this->localFile );
            $this->loaded = $this->content ? true : false;
            return $this->loaded;
        }
        else{
            return false;
        }
    }

    protected function saveLocal(){
        if( $this->content ){
            $this->initCacheDir();
            $file = fopen( $this->localFile, "w" ) or die( "Unable to open file!" );
            fwrite( $file, $this->content );
            fclose( $file );
        }
    }

    private function loadRemote(){
        $this->content = @file_get_contents( $this->remoteFile );
        if( $this->content ){
            $this->loaded = true;
            $this->saveLocal();
        }
    }

    private function initCacheDir(){
        if( !is_dir( CacheableFile::DIR ) ){
            mkdir( CacheableFile::DIR );
        }
    }
}