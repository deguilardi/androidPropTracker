<?php
include "GitFile.php";
include "entities/PropertyHistoryEntity.php";

class GradleFile extends GitFile{

    private const NOT_LOADED = -1;

    private $parent;

    public $minSdkVersion = GradleFile::NOT_LOADED;
    public $targetSdkVersion = GradleFile::NOT_LOADED;
    public $compileSdkVersion = GradleFile::NOT_LOADED;

    public $histories = array();

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
        // $gradleFile->extractPropertyChangeHistory( $gradleFile, "minSdkVersion", 0, sizeof( $gradleFile->commits ) - 1, $gradleFile );
        $gradleFile->extractPropertyChangeHistory( $gradleFile, "targetSdkVersion", 0, sizeof( $gradleFile->commits ) - 1, $gradleFile );
        // $gradleFile->extractPropertyChangeHistory( $gradleFile, "compileSdkVersion", 0, sizeof( $gradleFile->commits ) - 1, $gradleFile );
        echo "<pre>"; print_r( $gradleFile->histories );
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

        $values = $this->parseWhole( $this->content );
        $this->minSdkVersion = $values[ "minSdkVersion" ];
        $this->targetSdkVersion = $values[ "targetSdkVersion" ];
        $this->compileSdkVersion = $values[ "compileSdkVersion" ];
    }

    private function parseWhole( $content ){
        $content = str_replace( ' ', '', $content );
        $content = str_replace( '=', '', $content );

        $values = $this->parseSection( "ext", $content );
        $minSdkVersion = $values[ "minSdkVersion" ];
        $targetSdkVersion = $values[ "targetSdkVersion" ];
        $compileSdkVersion = $values[ "compileSdkVersion" ];

        $values = $this->parseSection( "android", $content );
        if( $values ){
            $minSdkVersion = $values[ "minSdkVersion" ];
            $targetSdkVersion = $values[ "targetSdkVersion" ];
            $compileSdkVersion = $values[ "compileSdkVersion" ];
        }

        return array(
            "minSdkVersion" => $minSdkVersion,
            "targetSdkVersion" => $targetSdkVersion,
            "compileSdkVersion" => $compileSdkVersion
        );
    }

    private function parseSection( $section, $content ){
        $sectionStartPos = strpos( $content, $section . "{" );
        if( !$sectionStartPos ){
            return;
        }

        $sectionEndPos = strpos( $content, "}", $sectionStartPos );
        $extContent = substr( $content, $sectionStartPos, $sectionEndPos - $sectionStartPos + 1 );

        return array(
            "minSdkVersion" => $this->extractProperty( "minSdkVersion", $extContent ),
            "targetSdkVersion" => $this->extractProperty( "targetSdkVersion", $extContent ),
            "compileSdkVersion" => $this->extractProperty( "compileSdkVersion", $extContent )
        );
    }

    private function extractProperty( $property, $content ){
        $matches = array();
        $regexp = "/($property)([a-zA-Z0-9\.]{0,})/";
        preg_match($regexp, $content, $matches, PREG_OFFSET_CAPTURE);
        if( sizeof( $matches ) >= 3 ){
            $value = $matches[ 2 ][ 0 ];
            if( strpos( $value, "root" ) === 0 ){
                return $this->parent->$property;
            }
            else{
                return $value;
            }
        }
        else{
            return GradleFile::NOT_LOADED;
        }
    }

    private function extractPropertyChangeHistory( $baseGradleFile, $property, $leftIndex, $rightIndex, $lastGradleFile ){
        $middleIndex = $leftIndex + floor( ( $rightIndex - $leftIndex ) / 2 );
        if( $middleIndex == sizeof( $this->commits ) - 2 ){
            return;
        }

        // echo "<pre>" . $leftIndex . " - " . $middleIndex . " - " . $rightIndex . " -- ";
        $commit = $this->commits[ $middleIndex ];
        $hash = $commit->hash;
        $gradleFile = GradleFile::factoryWithCommit( $baseGradleFile->repo, $hash, $this->path, $this->parent ? true : false );

        if( $rightIndex <= $leftIndex || $middleIndex == $leftIndex ){
            $oldValue = $lastGradleFile->$property;
            if( $oldValue ){
                $this->histories[ $property ][] = new PropertyHistoryEntity( $commit, $oldValue, $baseGradleFile->$property );
            }
            $this->extractPropertyChangeHistory( $lastGradleFile, $property, $leftIndex, sizeof( $this->commits ) - 1, $lastGradleFile );
            return;
        }

        if( $baseGradleFile->$property != $gradleFile->$property ){
            $rightIndex = $middleIndex;
        }
        else{
            $leftIndex = $middleIndex;
        }
        $this->extractPropertyChangeHistory( $baseGradleFile, $property, $leftIndex, $rightIndex, $gradleFile );
    }
}