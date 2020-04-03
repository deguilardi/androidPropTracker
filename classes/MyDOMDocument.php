<?php
class MyDOMDocument extends DOMDocument{

    function __construct( $content ){
        libxml_use_internal_errors( true );
        parent::__construct();
        parent::loadHTML( $content );
    }

    function childAt( $index ){
        return new MyDOMNode( $this->childNodes->item( $index ) );
    }

    function childWithTag( $tagName ){
        foreach( $this->childNodes as $item ){
            if( $item->nodeType == XML_ELEMENT_NODE && $item->tagName == $tagName ){
                return new MyDOMNode( $item );
            }
        }
        return null;
    }
}