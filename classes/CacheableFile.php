<?php
class CacheableFile{

    private const DIR = "cached";
    private const ERR_PREFIX = "_err/";

    public $id;
    protected $remoteFile;
    public $localFile;
    public $localErrorFile;
    public $content;
    public $loaded = false;
    protected $hasError = false;
    // @TODO check timestamp to reload
    // public $updateTimestamp; 

    public function __construct( $remoteFile ){
        $this->remoteFile = $remoteFile;
        $this->id = md5( $remoteFile );
        $this->localFile = self::DIR . "/" . $this->id;
        $this->localErrorFile = self::DIR . "/" . self::ERR_PREFIX . $this->id;
    }

    public function load(){
        if( ENABLE_CACHE && $this->loadLocal() ){
            $this->loaded = true;
        }
        else{
            if( $this->loadRemote() ){
                $this->loaded = true;
                $this->saveLocal();
            }
            else{
                // echo "<br> error!";
                $this->hasError = true;
                $this->initCacheDir();
                $file = fopen( $this->localErrorFile, "w" ) or die( "Unable to open file!" );
                fwrite( $file, $this->content );
                fclose( $file );
            }
        }
    }

    public function clear(){
        unset( $this->content );
    }

    protected function loadLocal(){
        if( ENABLE_CACHE && is_file( $this->localFile ) ){
            $this->content = file_get_contents( $this->localFile );
            return ( $this->content ) ? true : false;
        }
        else if( is_file( $this->localErrorFile ) ){
            $this->hasError = true;
            return true;
        }
        else{
            return false;
        }
    }

    protected function saveLocal(){
        if( ENABLE_CACHE && $this->content ){
            $this->initCacheDir();
            $file = fopen( $this->localFile, "w" ) or die( "Unable to open file!" );
            fwrite( $file, $this->content );
            fclose( $file );
        }
    }

    private function loadRemote(){
        // echo "<br><br/>" . "loading remote file:";
        // echo "<br>" . $this->remoteFile;
        // echo "<br>" . $this->localFile;
        $this->content = @file_get_contents( $this->remoteFile );
        return ( $this->content ) ? true : false;
    }

    private function initCacheDir(){
        if( ENABLE_CACHE && !is_dir( CacheableFile::DIR ) ){
            mkdir( CacheableFile::DIR );
            mkdir( CacheableFile::DIR . "/" . CacheableFile::ERR_PREFIX );
        }
    }
}