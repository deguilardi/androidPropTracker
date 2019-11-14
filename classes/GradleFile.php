<?php
include "GitFile.php";
include "entities/PropertyHistoryEntity.php";
include "entities/ExtVarEntity.php";

class GradleFile extends GitFile{

    public const NOT_LOADED = -1;
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
        // @TODO this doesn't need to be called every time!
        // to test, a project that needs it is: facebook/fresco
        $this->loadGradleDotPropertiesFile();
        $this->concatExternalFiles( $content );
        $this->loadExtVars( $content );
        $this->propertyValue = $this->parseSection( "android", $content );
        if( $this->propertyValue == GradleFile::NOT_LOADED ){
            $this->propertyValue = $this->parseSection( "defaultConfig", $content );
        }
    }

    private function loadGradleDotPropertiesFile(){
        $file = "gradle.properties";
        $gitFile = new GitFile( $this->repoEntity, $file, $this->parent );
        $gitFile->load();

        // properties like ext.prop=value
        $matches = array();
        $regexp = "/([a-zA-Z0-9\_\.]{1,})(\=)([a-zA-Z0-9\'\:\,\-\_\.]{1,})/";
        preg_match_all( $regexp, $gitFile->content, $matches );
        if( sizeof( $matches ) && sizeof( $matches[ 3 ] ) > 0 ){
            foreach( $matches[ 1 ] as $k => $varName ){
                // echo "<br>" . $varName . " - ". $matches[ 3 ][ $k ];
                $this->extVars[ $varName ] = new ExtVarEntity( $varName, $matches[ 3 ][ $k ] );
            }
        }
    }

    private function concatExternalFiles( &$content ){

        // files with "applyFrom"
        $matches = array();
        $regexpFirstPart = "(applyfrom\:(rootProject\.file\(){0,}\'([\$]\{rootDir\}\/){0,})";
        $regexpMiddleFile = "([a-zA-Z0-9\$\{\}\.\/\_\-]{1,})";
        $regextEnding = "(\')";
        $regexp = "/".$regexpFirstPart.$regexpMiddleFile.$regextEnding."/";
        preg_match( $regexp, $content, $matches, PREG_OFFSET_CAPTURE );
        if( sizeof( $matches ) && sizeof( $matches[ 4 ] ) > 0 ){
            $file = $matches[ 4 ][ 0 ];
            $gitFile = new GitFile( $this->repoEntity, $file, $this->parent );
            $gitFile->load();
            $newContent = $gitFile->content;
            $this->clearContent( $newContent );
            $content .= $newContent;
        }
    }

    private function loadExtVars( $content ){

        // a whole section ext{}
        $sectionContent = $this->extractSection( "ext", $content );
        if( $sectionContent ){

            // each var setted as var=value
            $matches = array();
            $regexp = "/([a-zA-Z0-9]{1,})(\=)([a-zA-Z0-9\.\_\']{1,})/";
            preg_match_all( $regexp, $sectionContent, $matches, PREG_OFFSET_CAPTURE );
            foreach( $matches[ 1 ] as $k => $var ){
                $varName = $var[ 0 ];
                $this->extVars[ $varName ] = new ExtVarEntity( $varName, $matches[ 3 ][ $k ][ 0 ] );
            }
        }

        // properties as array ext.prop=[...]
        // $sectionStartPos = strpos( $content, "ext." );
        // if( $sectionStartPos ){
            $matches = array();
            $regexp = "/([a-zA-Z0-9\_]{1,}\.){0,}([a-zA-Z0-9\_]{1,})(\=\[)([a-zA-Z0-9\n\'\:\,\-\_\.]{0,})(\])/";
            preg_match_all( $regexp, $content, $matches );
            $propertiesListStringList = $matches[ 4 ];
            if( sizeof( $propertiesListStringList ) != 0 ){
                $varPrefix = $matches[ 2 ][ 0 ];
                foreach( $propertiesListStringList as $propertiesListString ){

                    // filter some array initialization
                    if( $propertiesListString == ":" ){
                        continue;
                    }

                    $propertiesListString = str_replace( "'", "" , $propertiesListString );
                    $propertiesListString = str_replace( "\n", "" , $propertiesListString );
                    $propertiesList = explode( ",", $propertiesListString );
                    foreach( $propertiesList as $property ){
                        $parts = explode( ":", $property );
                        $varName = $varPrefix . "." . $parts[ 0 ];
                        $varValue = $parts[ 1 ];
                        if( $varName && $varValue ){
                            $this->extVars[ $varName ] = new ExtVarEntity( $varName, $varValue );
                        }
                    }
                }
            }
        // }

        // properties like ext.prop=value
        $matches = array();
        $regexp = "/([a-zA-Z0-9\_]{0,}\.[a-zA-Z0-9\_]{1,})(\=)([a-zA-Z0-9\'\:\,\-\_\.]{1,})/";
        preg_match_all( $regexp, $content, $matches );
        if( sizeof( $matches ) && sizeof( $matches[ 3 ] ) > 0 ){
            foreach( $matches[ 1 ] as $k => $varName ){
                $this->extVars[ $varName ] = new ExtVarEntity( $varName, $matches[ 3 ][ $k ] );
            }
        }
    }

    private function parseSection( $section, $content ){
        $sectionContent = $this->extractSection( $section, $content );
        return $this->extractProperty( $sectionContent );
    }

    /**
     * Extracts a wholse section like ext{ ... }
     * Sometimes this can be tricky like:
     * section{
     *    var=[ ${something}.value ]
     * }
     */
    private function extractSection( $section, $content ){
        $sectionStartPos = strpos( $content, $section . "{" ) + strlen( $section );
        if( !$sectionStartPos ){
            return;
        }

        // makes shure all inner "{}" are considered
        $openBracketPos = $sectionStartPos + 1;
        $closeBracketPos = -1;
        while( $nextOpenBracketPos = @strpos( $content, "{", $openBracketPos ) ){
            $closeBracketPos = strpos( $content, "}", $openBracketPos );
            if( $closeBracketPos ){
                if( $closeBracketPos < $nextOpenBracketPos ){
                    break;
                }
                else{ 
                    $nextOpenBracketPos = $closeBracketPos;
                }
            }
            $openBracketPos = $nextOpenBracketPos + 1;
        }

        $sectionEndPos = @strpos( $content, "}", $openBracketPos );
        $sectionEndPos = $sectionEndPos - $sectionStartPos + 1;
        return substr( $content, $sectionStartPos, $sectionEndPos );
    }

    private function extractProperty( $content ){
        $property = PARAM_TO_TRACK;

        // property is explicit
        $matches = array();
        $regexp = "/($property)([a-zA-Z0-9\.\_]{0,})/";
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
                if( is_numeric( $value ) ){
                    return $value;
                }
                else{
                    $varValueName = $value;
                }
            }

            $output = $this->parent->extVars[ $varValueName ]->value;
            $output = ( is_numeric( $output ) ) ? $output : $this->parent->extVars[ $output ]->value;
            return ( $output ) ? $output : GradleFile::NOT_LOADED;
        }
        else{
            return GradleFile::NOT_LOADED;
        }
    }

    private function extractPropertyChangeHistory( $baseGradleFile, $leftIndex, $rightIndex, $lastGradleFile ){
        $property = PARAM_TO_TRACK;
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