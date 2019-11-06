<?php
include "GitFile.php";
include "entities/PropertyHistoryEntity.php";
include "entities/ExtVarEntity.php";

class GradleFile extends GitFile{

    private const NOT_LOADED = -1;
    private $parent;
    public $propertyValue = GradleFile::NOT_LOADED;
    public $propertyHistory = array();
    public $extVars = array();

    public function __construct( $repoEntity, $file, $parent ){
        parent::__construct( $repoEntity, $file );
        $this->parent = $parent;
        $this->load();
        $this->initialParse( $this->content );
        $this->clear();
    }

    public function factoryMaster( $repoEntity, $file, $parent ){
        $gradleFile = new GradleFile( $repoEntity, $file, $parent );
        $gradleFile->loadCommits();
        $gradleFile->extractPropertyChangeHistory( $gradleFile, 0, sizeof( $gradleFile->commits ) - 1, $gradleFile );
        return $gradleFile;
    }

    public function factoryWithCommit( $repoEntity, $file, $hasParent ){
        $parent = null;
        if( $hasParent ){
            $parent = GradleFile::factoryWithCommit( $repoEntity, $this->parent->path, false );
        }
        $gradleFile = new GradleFile( $repoEntity, $file, $parent );
        return $gradleFile;
    }

    private function initialParse( $content ){
        if( !$this->content ){
            return;
        }
        $content = $this->content;
        $this->clearContent( $content );
        $this->concatExternalFiles( $content );
        $this->loadExtVars( $content );
        $this->propertyValue = $this->parseSection( "android", $content );
    }

    private function concatExternalFiles( &$content ){
        $matches = array();
        $regexp = "/(applyfrom\:\')([\$]\{rootDir\}\/){0,}([a-zA-Z0-9\$\{\}\.\/\_\-]{1,})(\')/";
        preg_match( $regexp, $content, $matches, PREG_OFFSET_CAPTURE );
        if( sizeof( $matches ) && sizeof( $matches[ 3 ] ) > 0 ){
            $file = $matches[ 3 ][ 0 ];
            $gitFile = new GitFile( $this->repoEntity, $file, $this->parent );
            $gitFile->load();
            $newContent = $gitFile->content;
            $this->clearContent( $newContent );
            $content .= $newContent;
        }
    }

    private function loadExtVars( $content ){

        // a whole section ext{}
        $sectionStartPos = strpos( $content, "ext{" );
        if( $sectionStartPos ){
            $sectionEndPos = strpos( $content, "}", $sectionStartPos );
            $sectionContent = substr( $content, $sectionStartPos, $sectionEndPos - $sectionStartPos + 1 );
            
            // each var setted as var=value
            $matches = array();
            $regexp = "/([a-zA-Z0-9]{1,})(\=)([a-zA-Z0-9\.\_]{1,})/";
            preg_match_all( $regexp, $sectionContent, $matches, PREG_OFFSET_CAPTURE );
            foreach( $matches[ 1 ] as $k => $var ){
                $varName = $var[ 0 ];
                $this->extVars[ $varName ] = new ExtVarEntity( $varName, $matches[ 3 ][ $k ][ 0 ] );
            }
        }

        // @TODO
        // properties like ext.prop=value
        // $sectionStartPos = strpos( $content, "ext." );
        // if( $sectionStartPos ){

        // }

        // properties as array ext.prop=[...]
        $sectionStartPos = strpos( $content, "ext." );
        if( $sectionStartPos ){
            $matches = array();
            $regexp = "/(ext\.)([a-zA-Z0-9]{1,})(\=\[)([a-zA-Z0-9\n\'\:\,\-\_\.]{0,})(\])/";
            preg_match_all( $regexp, $content, $matches );
            $propertiesListStringList = $matches[ 4 ];
            if( sizeof( $propertiesListStringList ) != 0 ){
                $varPrefix = $matches[ 2 ][ 0 ];
                foreach( $propertiesListStringList as $propertiesListString){
                    $propertiesListString = str_replace( "'", "" , $propertiesListString );
                    $propertiesListString = str_replace( "\n", "" , $propertiesListString );
                    $propertiesList = explode( ",", $propertiesListString );
                    foreach( $propertiesList as $property ){
                        $parts = explode( ":", $property );
                        $varName = $varPrefix . "." . $parts[ 0 ];
                        $this->extVars[ $varName ] = new ExtVarEntity( $varName, $parts[ 1 ] );
                    }
                }
            }
        }
    }

    private function parseSection( $section, $content ){
        $sectionStartPos = strpos( $content, $section . "{" );
        if( !$sectionStartPos ){
            return;
        }

        $sectionEndPos = strpos( $content, "}", $sectionStartPos );
        $sectionContent = substr( $content, $sectionStartPos, $sectionEndPos - $sectionStartPos + 1 );

        return $this->extractProperty( $sectionContent );
    }

    private function extractProperty( $content ){
        $property = PARAM_TO_EXTRACT;

        // property is explicit
        $matches = array();
        $regexp = "/($property)([a-zA-Z0-9\.]{0,})/";
        preg_match($regexp, $content, $matches, PREG_OFFSET_CAPTURE);
        if( sizeof( $matches ) >= 3 ){
            $value = $matches[ 2 ][ 0 ];

            // property points to root
            if( strpos( $value, "rootProject" ) === 0 ){
                $varValueName = $value;
                $varValueName = substr( $value, 12 );
                $varValueName = str_replace( "ext.", "", $varValueName );
            }
            else if( strpos( $value, "project" ) === 0 ){
                $varValueName = substr( $value, 8 );
                $varValueName = str_replace( "ext.", "", $varValueName );
            }
            else{
                $varValueName = $value;
            }

            $output = $this->parent->extVars[ $varValueName ]->value;
            return ( $output ) ? $output : GradleFile::NOT_LOADED;
        }
        else{

            // @TODO
            // property is loaded from ext

            return GradleFile::NOT_LOADED;
        }
    }

    private function extractPropertyChangeHistory( $baseGradleFile, $leftIndex, $rightIndex, $lastGradleFile ){
        $property = PARAM_TO_EXTRACT;
        if( !$this->loaded || sizeof( $this->commits ) <= 1 ){ return; }
        $middleIndex = $leftIndex + floor( ( $rightIndex - $leftIndex ) / 2 );
        if( $middleIndex == sizeof( $this->commits ) - 2 ){ return; }

        // echo "<pre>" . $leftIndex . " - " . $middleIndex . " - " . $rightIndex . " -- ";
        $commit = $this->commits[ $middleIndex ];
        $hash = $commit->hash;
        $repoEntity = clone( $baseGradleFile->repoEntity );
        $repoEntity->branch = $hash;
        $gradleFile = GradleFile::factoryWithCommit( $repoEntity, $this->path, $this->parent ? true : false );

        if( $rightIndex <= $leftIndex || $middleIndex == $leftIndex ){
            $oldValue = $lastGradleFile->propertyValue;
            if( $oldValue ){
                $this->propertyHistory[] = new PropertyHistoryEntity( $commit, $oldValue, $baseGradleFile->propertyValue );
            }
            $this->extractPropertyChangeHistory( $lastGradleFile, $leftIndex, sizeof( $this->commits ) - 1, $lastGradleFile );
            return;
        }

        if( $baseGradleFile->propertyValue != $gradleFile->propertyValue ){
            $rightIndex = $middleIndex;
        }
        else{
            $leftIndex = $middleIndex;
        }
        $this->extractPropertyChangeHistory( $baseGradleFile, $leftIndex, $rightIndex, $gradleFile );
    }

    private function clearContent( &$content ){
        $content = str_replace( ' ', '', $content );
        $content = str_replace( '"', "'", $content );
    }
}