<?php
include "CacheableFile.php";
include "entities/CommitEntity.php";

class GitFile extends CacheableFile{

    protected $repo;
    protected $path;
    protected $commits = array();

    public function __construct( $repo, $branch, $path ){
        $this->repo = $repo;
        $this->path = $path;
        $this->branch = $branch;
        parent::__construct( GIT_RAW_CODE_URL_BASE . $repo . "/" . $this->branch . "/" . $path );
    }

    protected function loadCommits(){
        $url = GIT_URL_BASE . $this->repo . "/commits/" . $this->branch . "/" . $this->path;
        $this->loadCommitsFromPage( $url );
    }

    private function loadCommitsFromPage( $url ){
        $commitsListFile = new CacheableFile( $url );
        $commitsListFile->load();

        $htmlDoc = new DOMDocument();
        libxml_use_internal_errors( true );
        $htmlDoc->loadHTML( $commitsListFile->content );
        $htmlElem = $htmlDoc->childNodes->item( 1 );
        $bodyElem = $htmlElem->childNodes->item( 3 );
        $appElem = $bodyElem->childNodes->item( 7 );
        $mainElem = $appElem->childNodes->item( 1 )->childNodes->item( 1 );
        $repoContentElem = $mainElem->childNodes->item( 3 )->childNodes->item( 1 );
        $commitsListElem = $repoContentElem->childNodes->item( 3 );

        foreach( $commitsListElem->childNodes as $item ){
            if( $item->nodeType != XML_ELEMENT_NODE ){ continue; }

            if( $item->getAttribute( "class" ) == "commit-group-title" ){
                $date = $this->extractCommitDateFromCommitsListTitleTag( $item );
            }
            else if( $item->tagName == "ol" ){
                foreach( $item->childNodes as $commitItem ){
                    if( $commitItem->nodeType != XML_ELEMENT_NODE ){ continue; }
                    if( $commitItem->tagName != "li" ){ continue; }
                    $hash = $this->extractCommitHashFromCommitsListItemTag( $commitItem );
                }
                $this->commits[] = new CommitEntity( $date, $hash );
            }
        }

        // check for other pages
        $pagesContainersElem = $repoContentElem->childNodes->item( 5 );
        $nextPageElem = $pagesContainersElem->childNodes->item( 1 )->childNodes->item( 1 );
        $href = $nextPageElem->getAttribute( "href" );
        if( $href ){
            $this->loadCommitsFromPage( $href );
        }
    }

    private function extractCommitDateFromCommitsListTitleTag( $item ){
        $value = trim( $item->nodeValue );
        $value = str_replace( "Commits on ", "", $value );
        return date_parse( $value );
    }

    private function extractCommitHashFromCommitsListItemTag( $item ){
        $info = $item->getAttribute( "data-channel" );
        $matches = array();
        $regexp = "/(repo:[0-9]{1,}:commit:)(([a-zA-Z0-9]{1,}))/";
        preg_match_all( $regexp, $info, $matches, PREG_OFFSET_CAPTURE );
        return $matches[ 3 ][ 0 ][ 0 ];
    }
}