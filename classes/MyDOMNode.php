<?php
class MyDOMNode{

    private $node;

    function __construct( $node ){
        $this->node = $node;
    }

    function getNode(){
        return $this->node;
    }

    function getAttr( $attr ){
        return $this->node->getAttribute( $attr );
    }

    function childAt( $index ){
        return new MyDOMNode( $this->node->childNodes->item( $index ) );
    }

    function childWithTag( $tagName ){
        foreach( $this->node->childNodes as $item ){
            if( $item->nodeType == XML_ELEMENT_NODE && $item->tagName == $tagName ){
                return new MyDOMNode( $item );
            }
        }
        return null;
    }

    function childWithAttrValue( $attr, $value ){
        foreach( $this->node->childNodes as $item ){
            if( $item->nodeType == XML_ELEMENT_NODE && strpos( $item->getAttribute( $attr ), $value ) !== false ){
                return new MyDOMNode( $item );
            }
        }
        return null;
    }

    function childWithClass( $class ){
        return $this->childWithAttrValue( "class", $class );
    }
}