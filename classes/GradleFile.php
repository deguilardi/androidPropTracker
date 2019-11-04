<?php
include "GitFile.php";
include "entities/PropertyHistoryEntity.php";

class GradleFile extends GitFile{

    private const NOT_LOADED = -1;
    private $parent;
    public $propertyValue = GradleFile::NOT_LOADED;
    public $propertyHistory = array();

    private function __construct( $repo, $branch, $file, $parent ){
        parent::__construct( $repo, $branch, $file );
        $this->parent = $parent;
        $this->load();
        $this->initialParse( $this->content );
        $this->clear();
    }

    public function factoryMaster( $repo, $branch, $file, $parent ){
        $gradleFile = new GradleFile( $repo, $branch, $file, $parent );
        $gradleFile->loadCommits();
        $gradleFile->extractPropertyChangeHistory( $gradleFile, 0, sizeof( $gradleFile->commits ) - 1, $gradleFile );
        return $gradleFile;
    }

    public function factoryWithCommit( $repo, $branch, $file, $hasParent ){
        $parent = null;
        if( $hasParent ){
            $parent = GradleFile::factoryWithCommit( $repo, $branch, $this->parent->path, false );
        }
        $gradleFile = new GradleFile( $repo, $branch, $file, $parent );
        return $gradleFile;
    }

    public function clear(){
        parent::clear();
    }

    private function initialParse( $content ){
        if( !$this->content ){
            return;
        }
        $this->propertyValue = $this->parseWhole( $this->content );
    }

    private function parseWhole( $content ){
        $content = str_replace( ' ', '', $content );
        $content = str_replace( '=', '', $content );

        $extValue = $this->parseSection( "ext", $content );
        $androidValue = $this->parseSection( "android", $content );
        return ( $androidValue ) ? $androidValue : $extValue;
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
        $matches = array();
        $regexp = "/($property)([a-zA-Z0-9\.]{0,})/";
        preg_match($regexp, $content, $matches, PREG_OFFSET_CAPTURE);
        if( sizeof( $matches ) >= 3 ){
            $value = $matches[ 2 ][ 0 ];
            if( strpos( $value, "root" ) === 0 ){
                return $this->parent->propertyValue;
            }
            else{
                return $value;
            }
        }
        else{
            return GradleFile::NOT_LOADED;
        }
    }

    private function extractPropertyChangeHistory( $baseGradleFile, $leftIndex, $rightIndex, $lastGradleFile ){
        if( !$this->loaded ){ return; }
        $property = PARAM_TO_EXTRACT;
        $middleIndex = $leftIndex + floor( ( $rightIndex - $leftIndex ) / 2 );
        if( $middleIndex == sizeof( $this->commits ) - 2 ){
            return;
        }

        // echo "<pre>" . $leftIndex . " - " . $middleIndex . " - " . $rightIndex . " -- ";
        $commit = $this->commits[ $middleIndex ];
        $hash = $commit->hash;
        $gradleFile = GradleFile::factoryWithCommit( $baseGradleFile->repo, $hash, $this->path, $this->parent ? true : false );

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
}