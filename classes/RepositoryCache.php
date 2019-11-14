<?php
class RepositoryCache{
	
    private const DIR = "cached/_results";

    public static function factoryResultsWithRepoEntity( $repoEntity ){
        $localFile = self::getFilePath( $repoEntity );
        if( is_file( $localFile ) ){
        	$content = file_get_contents( $localFile );
        	return json_decode( $content, true );
        }
        return false;
    }

    public static function save( $repository ){
        $repoEntity = $repository->repoEntity;
        $results = $repository->propertyChanges;
    	if( $results ){
    		$localFile = self::getFilePath( $repoEntity );
            $file = fopen( $localFile, "w" ) or die( "Unable to open file!" );
            $resultsJson = json_encode( $results );
            fwrite( $file, $resultsJson );
            fclose( $file );
    	}
    }

    private static function getFilePath( $repoEntity ){
    	$id = $repoEntity->getHash();
        return self::DIR . "/" . $id;
    }
}
?>