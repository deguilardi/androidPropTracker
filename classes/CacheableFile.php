<?php
class CacheableFile{

    private const DIR = "cached";
    private const ERR_PREFIX = "__err/";

    public $id;
    protected $remoteFile;
    public $localFile;
    private $localFolder;
    public $localErrorFile;
    public $content;
    public $loaded = false;
    public $hasError = false;
    // @TODO check timestamp to reload
    // public $updateTimestamp; 

    public function __construct( $remoteFile, $localFolder ){
        $this->loaded = false;
        $this->hasError = false;
        $this->localFolder = str_replace( array( "-", ".", "/" ), "_", $localFolder );
        $this->remoteFile = $remoteFile;
        $this->id = md5( $remoteFile );
        $this->localFile = $this->id;
        $this->localErrorFile = self::ERR_PREFIX . $this->id;
    }

    public function load(){
        if( ENABLE_CACHE && $this->loadLocal() ){
            $this->loaded = true;
        }
        else{
            if( is_file( CacheableFile::DIR . "/" . $this->localErrorFile ) ){
                // echo "<br>cached error";
                $this->hasError = true;
            }
            else if( $this->loadRemote() ){
                $this->loaded = true;
                $this->saveLocal();
            }
            else{
                // echo "<br> error!";
                $this->hasError = true;
                $this->initCacheDir();
                $file = fopen( CacheableFile::DIR . "/" . $this->localErrorFile, "w" ) or die( "Unable to open file! (load)" );
                fwrite( $file, $this->content );
                fclose( $file );
            }
        }
    }

    public function clear(){
        unset( $this->content );
    }

    protected function loadLocal(){
        if( ENABLE_CACHE && is_file( $this->getFullLocalPath() ) ){
            $this->content = file_get_contents( $this->getFullLocalPath() );
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
            $file = fopen( $this->getFullLocalPath(), "w" ) or die( "Unable to open file! (saveLocal)" );
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
        if( ENABLE_CACHE ){
            if( !is_dir( CacheableFile::DIR ) ){
                mkdir( CacheableFile::DIR );
                mkdir( CacheableFile::DIR . "/" . CacheableFile::ERR_PREFIX );
            }
            if( !is_dir( CacheableFile::DIR . "/" . $this->localFolder ) ){
                mkdir( CacheableFile::DIR . "/" . $this->localFolder );
            }
        }
    }

    private function getFullLocalPath(){
        return CacheableFile::DIR . "/" . $this->localFolder ."/". $this->localFile;
    }
}