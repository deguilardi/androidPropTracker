<?php
class RepositoryCache{
	
    private const DIR = "cached/__results";

    public static function factoryResultsWithRepoEntity( $repoEntity ){
        $localFile = self::getFilePath( $repoEntity );
        if( ENABLE_CACHE && is_file( $localFile ) ){
        	$content = file_get_contents( $localFile );
        	return json_decode( $content, true );
        }
        return false;
    }

    public static function save( $repository ){
        self::initCacheDir();
		$localFile = self::getFilePath( $repository->repoEntity );
        $file = fopen( $localFile, "w" ) or die( "Unable to open file!" );
        $resultsJson = json_encode( $repository );
        fwrite( $file, $resultsJson );
        fclose( $file );
    }

    private static function getFilePath( $repoEntity ){
    	$id = $repoEntity->getHash();
        return self::DIR . "/" . $id;
    }

    private static function initCacheDir(){
        if( ENABLE_CACHE && !is_dir( self::DIR ) ){
            mkdir( self::DIR );
        }
    }
}
?>