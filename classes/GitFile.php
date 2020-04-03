<?php
include_once( "CacheableFile.php" );
include_once( "MyDOMDocument.php" );
include_once( "MyDOMNode.php" );
include_once( "entities/CommitEntity.php" );
include_once( "entities/RepositoryEntity.php" );

class GitFile extends CacheableFile{

    protected $repoEntity;
    protected $path;
    protected $commits = array();

    public function __construct( $repoEntity, $path ){
        $this->repoEntity = $repoEntity;
        $this->path = $path;
        parent::__construct( $this->repoEntity->getRawPathUrlForFile( $this->path ), $this->repoEntity->repo );
    }

    protected function loadCommits(){
        if( $this->loaded && !$this->hasError ){
            $this->loadCommitsFromPage( $this->repoEntity->getCommitsListUrlForFile( $this->path ) );
        }
    }

    private function loadCommitsFromPage( $url ){
        $commitsListFile = new CacheableFile( $url, $this->repoEntity->repo );
        $commitsListFile->load();

        if( !$commitsListFile->content ){
            return;
        }

        $htmlDoc = new MyDOMDocument( $commitsListFile->content );
        $htmlElem = $htmlDoc->childWithTag( "html" );
        $bodyElem = $htmlElem->childWithTag( "body" );
        $appElem = $bodyElem->childWithClass( "application-main" );
        $mainElem = $appElem->childAt( 1 )->childWithTag( "main" );
        $timelineElem = $mainElem->childWithClass( "new-discussion-timeline" );
        $repoContentElem = $timelineElem->childWithClass( "repository-content" );
        $commitsListElem = $repoContentElem->childWithClass( "commits-listing" );

        foreach( $commitsListElem->getNode()->childNodes as $item ){
            if( $item->nodeType != XML_ELEMENT_NODE ){ continue; }

            if( $item->getAttribute( "class" ) == "commit-group-title" ){
                $date = $this->extractCommitDateFromCommitsListTitleTag( $item );
            }
            else if( $item->tagName == "ol" ){
                foreach( $item->childNodes as $commitItem ){
                    if( $commitItem->nodeType != XML_ELEMENT_NODE ){ continue; }
                    if( $commitItem->tagName != "li" ){ continue; }
                    $hash = $this->extractCommitHashFromCommitsListItemTag( $commitItem );
                    $this->commits[] = new CommitEntity( $date, $hash );
                }
            }
        }

        // check for other pages
        $pagesContainersElem = $repoContentElem->childAt( 5 );
        if( $pagesContainersElem ){
            $nextPageElem = $pagesContainersElem->childAt( 1 )->childAt( 1 );
            $href = $nextPageElem->getNode()->getAttribute( "href" );
            if( $href ){
                $this->loadCommitsFromPage( $href );
            } 
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

    protected function mergeCommits( $otherCommits ){
        if( !$otherCommits ){ return; }
        $output = array();
        for( $i = 0; $i < sizeof( $this->commits ); $i++ ){
            for( $ii = 0; $ii < sizeof( $otherCommits ); $ii++ ){
                if( $this->commits[ $i ]->isAfterThan( $otherCommits[ $ii ] ) ){
                    $output[] = array_shift( $this->commits );
                    $i--;
                    continue 2;
                }
                else if( $this->commits[ $i ]->hash == $otherCommits[ $ii ]->hash ){
                    array_shift( $otherCommits );
                    $ii--;
                }
                else{
                    $output[] = array_shift( $otherCommits );
                    $ii--;
                }
            }
        }
        $this->commits = array_merge( $output, $this->commits );
    }
}