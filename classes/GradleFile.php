<?php
include "GitFile.php";

class GradleFile extends GitFile{

    private const NOT_LOADED = -1;

    private $parent;
    private $strippedContent;

    public $minSdkVersion = GradleFile::NOT_LOADED;
    public $targetSdkVersion = GradleFile::NOT_LOADED;
    public $compileSdkVersion = GradleFile::NOT_LOADED;

    public function __construct( $repository, $file, $parent ){
        parent::__construct( $repository, $file );
        $this->parent = $parent;
        $this->load();
        $this->parse();
        $this->clear();

        echo "<pre>";
        print_r( $this->commits );
    }

    public function clear(){
        parent::clear();
        unset( $this->strippedContent );
    }

    private function parse(){
        if( !$this->content ){
            return;
        }

        $this->strippedContent = str_replace( ' ', '', $this->content );
        $this->strippedContent = str_replace( '=', '', $this->strippedContent );
        $this->parseSection( "ext" );
        $this->parseSection( "android" );
    }

    private function parseSection( $section ){
        $sectionStartPos = strpos( $this->strippedContent, $section . "{" );
        if( !$sectionStartPos ){
            return;
        }

        $sectionEndPos = strpos( $this->strippedContent, "}", $sectionStartPos );
        $extContent = substr( $this->strippedContent, $sectionStartPos, $sectionEndPos - $sectionStartPos + 1 );

        $this->minSdkVersion = $this->extractProperty( "minSdkVersion", $extContent );
        $this->targetSdkVersion = $this->extractProperty( "targetSdkVersion", $extContent );
        $this->compileSdkVersion = $this->extractProperty( "compileSdkVersion", $extContent );
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
}