<?php
include "GitFile.php";
include "entities/PropertyHistoryEntity.php";
include "entities/ExtVarEntity.php";

class GradleFile extends GitFile{

    public const NOT_LOADED = -1;
    private $parent;
    public $propertyValue = self::NOT_LOADED;
    public $propertyHistory = array();
    public $extVars = array();
    public $isApplicationFile = false;

    protected function __construct( $repoEntity, $file, $parent, $isLastVersion ){
        parent::__construct( $repoEntity, $file );
        $this->parent = $parent;
        $this->load();

        // some projects have kts suffix
        if( $this->hasError ){
            $match = preg_match( '/build\.gradle$/', $file );
            if( $match ){
                $file .= ".kts";
                parent::__construct( $repoEntity, $file );
                $this->load();
            }
        }

        if( $isLastVersion ){
            $this->loadCommits();
        }
        $this->initialParse();
        $this->clear();
    }

    public function factoryRootLastVersion( $repoEntity, $file ){
        // echo "<hr/><hr/><hr/>ROOT<hr/><hr/><hr/>";
        $gradleFile = new GradleFile( $repoEntity, $file, $parent, true );
        $gradleFile->_debug( "factory root last version: ". $file, "<hr/>" );
        $gradleFile->_debug( "remote file: " . $gradleFile->remoteFile );
        return $gradleFile;
    }

    public function factoryModuleLastVersion( $repoEntity, $file, $parent ){
        // echo "<hr/><hr/><hr/>MODULE<hr/><hr/><hr/>";
        $gradleFile = new GradleFile( $repoEntity, $file, $parent, true );
        $gradleFile->_debug( "factory module last version: ". $file );
        $gradleFile->_debug( "remote file: " . $gradleFile->remoteFile );
        $gradleFile->_debug( "parent remote file: " . $parent->remoteFile );
        $gradleFile->_debug( "number of commits before merge: " . sizeof( $gradleFile->commits ) );
        $gradleFile->mergeCommits( $parent->commits );
        $gradleFile->_debug( "number of commits after merge: " . sizeof( $gradleFile->commits ) );
        $gradleFile->extractPropertyChangeHistory( $gradleFile, 0, sizeof( $gradleFile->commits ) - 1, $gradleFile );
        return $gradleFile;
    }

    public function factoryFromCommit( $repoEntity, $file, $hasParent ){
        $parent = null;
        if( $hasParent ){
            $parent = self::factoryFromCommit( $repoEntity, $this->parent->path, null, false );
        }
        $gradleFile = new GradleFile( $repoEntity, $file, $parent, false );
        return $gradleFile;
    }

    private function initialParse(){
        if( !$this->content ){
            return;
        }
        $content = $this->content;
        $this->clearContent( $content );
        $this->checkRootAndModuleAtSameTime( $content );
        $this->loadGradleDotPropertiesFile();
        $this->concatExternalFiles( $content );
        $this->loadExtVars( $content );
        $this->propertyValue = $this->parseSection( "android", $content );
        if( $this->propertyValue == self::NOT_LOADED ){
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
            $this->_debug( "concat external file: ". $file );
            $gitFile = new GitFile( $this->repoEntity, $file, $this->parent );
            $gitFile->load();
            $gitFile->loadCommits();
            $this->mergeCommits( $gitFile->commits );
            $newContent = $gitFile->content;
            $this->clearContent( $newContent );
            $content .= $newContent;
        }
    }

    private function loadExtVars( $content ){
        // $this->_debug( $content );

        // a whole section ext{}
        $sectionContent = $this->extractSection( "ext", $content );
        // $this->_debug( $sectionContent );
        if( $sectionContent ){

            // each var setted as var=value
            $matches = array();
            $regexp = "/([a-zA-Z0-9]{1,})(\=)([a-zA-Z0-9\.\_\']{1,})/";
            preg_match_all( $regexp, $sectionContent, $matches, PREG_OFFSET_CAPTURE );
            foreach( $matches[ 1 ] as $k => $var ){
                $varName = $var[ 0 ];
                if( !$this->extVars[ $varName ] ){
                    $this->extVars[ $varName ] = new ExtVarEntity( $varName, $matches[ 3 ][ $k ][ 0 ] );
                }
            }
        }

        // properties as array ext.prop=[...]
        // $sectionStartPos = strpos( $content, "ext." );
        // if( $sectionStartPos ){
            $matches = array();
            $regexp = "/([a-zA-Z0-9\_]{1,}\.){0,}([a-zA-Z0-9\_]{1,})(\=\[)([a-zA-Z0-9\n\'\:\,\-\_\.\/]{0,})(\])/";
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
                if( strpos( $varName, "ext." ) === 0 ){
                    $varName2 = str_replace( "ext.", "", $varName );
                    $this->extVars[ $varName2 ] = new ExtVarEntity( $varName2, $matches[ 3 ][ $k ] );
                }
                $this->extVars[ $varName ] = new ExtVarEntity( $varName, $matches[ 3 ][ $k ] );
            }
        }
        
        $this->_debug( $this->extVars );
    }

    private function parseSection( $section, $content ){
        $sectionContent = $this->extractSection( $section, $content );
        return $this->extractProperty( $sectionContent );
    }

    /**
     * Extracts a whole section like ext{ ... }
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
        $this->_debug( "extracting property" );
        $property = PARAM_TO_TRACK;

        // property is explicit
        $matches = array();
        $regexp = "/($property)([\(\=]{0,})([a-zA-Z0-9\.\_]{0,})/";
        preg_match($regexp, $content, $matches, PREG_OFFSET_CAPTURE);
        // $this->_debug( $matches );
        // $this->_debug( $content );
        if( sizeof( $matches ) == 4 ){
            $value = $matches[ 3 ][ 0 ];

            // property points to root
            if( strpos( $value, "rootProject" ) === 0 ){
                $varValueName = $value;
                $varValueName = substr( $value, 12 );
                $varValueName = str_replace( "ext.", "", $varValueName );
                $this->_debug( "    var value name in rootProject: set as: ". $varValueName );
            }
            else if( strpos( $value, "project" ) === 0 ){
                $varValueName = substr( $value, 8 );
                $varValueName = str_replace( "ext.", "", $varValueName );
                $this->_debug( "    var value name in project: set as: ". $varValueName );
            }
            else{
                if( is_numeric( $value ) ){
                    $this->_debug( "    property found: numeric in file ".$value );
                    return $value;
                }
                else{
                    $varValueName = $value;
                    $this->_debug( "    var value name set as: ". $varValueName );
                }
            }

            $output = $this->parent->extVars[ $varValueName ]->value;
            $this->_debug( "    output in parent ext var: " . $output );
            $output = ( is_numeric( $output ) ) ? $output : $this->parent->extVars[ $output ]->value;
            $this->_debug( "    final output: " . $output );
            return ( $output ) ? $output : self::NOT_LOADED;
        }
        else{
            return self::NOT_LOADED;
        }
    }

    private function extractPropertyChangeHistory( $baseGradleFile, $leftIndex, $rightIndex ){
        $property = PARAM_TO_TRACK;
        if( !$this->loaded || sizeof( $this->commits ) <= 1 ){ return; }
        $middleIndex = $leftIndex + ceil( ( $rightIndex - $leftIndex ) / 2 );
        $leftDistance = $middleIndex - $leftIndex;
        $rightDistance = $rightIndex - $middleIndex;
        $minorDistance = min( $leftDistance, $rightDistance );
        $majorDistance = max( $leftDistance, $rightDistance );
        $this->_debug( "in: [$leftIndex,$middleIndex,$rightIndex] - [$leftDistance,$rightDistance,$minorDistance,$majorDistance]", "<hr>" );
        
        // load gradle file in the middle
        $commit = $this->commits[ $middleIndex ];
        $hash = $commit->hash;
        $repoEntity = clone( $baseGradleFile->repoEntity );
        $repoEntity->branch = $hash;
        $middleGradleFile = GradleFile::factoryFromCommit( $repoEntity, $this->path, $this->parent ? true : false );
        $this->_debug( $middleGradleFile->propertyValue . " middle -> base " . $baseGradleFile->propertyValue );
        $this->_debug( "middle remote file: " . $middleGradleFile->remoteFile );
        $this->_debug( "base remote file: " . $baseGradleFile->remoteFile );

        if( $middleIndex == $rightIndex ){
            if( $baseGradleFile->propertyValue != self::NOT_LOADED && 
                $middleGradleFile->propertyValue != self::NOT_LOADED &&
                $middleGradleFile->propertyValue != $baseGradleFile->propertyValue ){
                $this->_debug( "CHANGE DETECTED: ". $middleGradleFile->propertyValue . " - " . $baseGradleFile->propertyValue );
                $this->propertyHistory[] = new PropertyHistoryEntity( $commit, 
                                                                      $middleGradleFile->propertyValue, 
                                                                      $baseGradleFile->propertyValue );
                $this->extractPropertyChangeHistory( $middleGradleFile, $leftIndex + 1, sizeof( $this->commits ) - 1 );
            }
            return;
        }

        if( $baseGradleFile->propertyValue != self::NOT_LOADED && 
            $middleGradleFile->propertyValue != self::NOT_LOADED &&
            $middleGradleFile->propertyValue != $baseGradleFile->propertyValue ){
            $rightIndex = $middleIndex;
        }
        else{
            $leftIndex = $middleIndex;
        }

        return $this->extractPropertyChangeHistory( $baseGradleFile, $leftIndex, $rightIndex );
    }

    private function checkRootAndModuleAtSameTime( &$content ){
        $this->isApplicationFile = ( strpos( $content, "applyplugin:'com.android.application'" ) !== false );
    }

    private function clearContent( &$content ){
        $content = str_replace( ' ', '', $content );
        $content = str_replace( '"', "'", $content );
    }

    private function _debug( $message, $before = "" ){
        if( !DEBUG_GRADLE_FILE ){ return; }
        if( DEBUG_GRADLE_FILE_REMOTE_ADDR && $this->remoteFile != DEBUG_GRADLE_FILE_REMOTE_ADDR ){ return; }
        echo $before . "<pre>";
        print_r( $message );
        echo "</pre>"; 
    }
}