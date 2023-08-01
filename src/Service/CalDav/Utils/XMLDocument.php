<?php
namespace Services\CalDav\Utils;

/**
* A class for XML Documents which will contain namespaced XML elements
*
* @package   awl
*/
class XMLDocument {

  /**#@+
  * @access private
  */
  static public $ns_dav = 'DAV:';
  static public $ns_caldav = 'urn:ietf:params:xml:ns:caldav';
  static public $ns_carddav = 'urn:ietf:params:xml:ns:carddav';
  static public $ns_calendarserver = 'http://calendarserver.org/ns/';
  /**
  * holds the namespaces which this document has been configured for.
  * @var namespaces
  */
  private $namespaces;
  /**
  * holds the prefixes which are shorthand for the namespaces.
  * @var prefixes
  */
  private $prefixes;
  /**
  * Holds the root document for the tree
  * @var root
  */
  private $root;

  /**
  * Simple XMLDocument constructor
  *
  * @param array $namespaces An array of 'namespace' => 'prefix' pairs, where the prefix is used as a short form for the namespace.
  */
  function __construct( $namespaces = null ) {
    $this->namespaces = array();
    $this->prefixes = array();
    if ( $namespaces != null ) {
      foreach( $namespaces AS $ns => $prefix ) {
        $this->namespaces[$ns] = $prefix;
        $this->prefixes[$prefix] = $prefix;
      }
    }
    $this->next_prefix = 0;
  }

  /**
   * Return the default namespace for this document
   */
  function DefaultNamespace() {
    foreach( $this->namespaces AS $k => $v ) {
      if ( $v == '' ) {
        return $k;
      }
    }
    return '';
  }

  /**
  * Return a tag with namespace stripped and replaced with a short form, and the ns added to the document.
  *
  */
  function GetXmlNsArray() {

    $ns = array();
    foreach( $this->namespaces AS $n => $p ) {
      if ( $p == "" ) $ns["xmlns"] = $n; else $ns["xmlns:$p"] = $n;
    }

    return $ns;
  }

  /**
  * Return a tag with namespace stripped and replaced with a short form, and the ns added to the document.
  *
  * @param string $in_tag The tag we want a namespace prefix on.
  * @param string $namespace The namespace we want it in (which will be parsed from $in_tag if not present
  * @param string $prefix The prefix we would like to use.  Leave it out and one will be assigned.
  *
  * @return string The tag with a namespace prefix consistent with previous tags in this namespace.
  */
  function Tag( $in_tag, $namespace=null, $prefix=null ) {

    if ( $namespace == null ) {
      // Attempt to split out from namespace:tag
      if ( preg_match('/^(.*):([^:]+)$/', $in_tag, $matches) ) {
        $namespace = $matches[1];
        $tag = $matches[2];
      }
      else {
        // There is nothing we can do here
        return $in_tag;
      }
    }
    else {
      $tag = $in_tag;
    }

    if ( !isset($this->namespaces[$namespace]) ) {
      $this->AddNamespace( $namespace, $prefix );
    }
    $prefix = $this->namespaces[$namespace];

    return $prefix . ($prefix == "" ? "" : ":") . $tag;
  }

    /**
     * Add a new namespace to the document, optionally specifying it's short prefix
     *
     * @param string $namespace The full namespace name to be added
     * @param string $prefix An optional short form for the namespace.
     * @throws \Exception
     */
  function AddNamespace( $namespace, $prefix = null ) {
    if ( !isset($this->namespaces[$namespace]) ) {
      if ( isset($prefix) && ($prefix == "" || isset($this->prefixes[$prefix])) ) $prefix = null;
      if ( $prefix == null ) {
        //  Try and build a prefix based on the first alphabetic character of the last element of the namespace
        if ( preg_match('/^(.*):([^:]+)$/', $namespace, $matches) ) {
          $alpha = preg_replace( '/[^a-z]/i', '', $matches[2] );
          $prefix = strtoupper(substr($alpha,0,1));
        }
        else {
          $prefix = 'X';
        }
        $i = "";
        if ( isset($this->prefixes[$prefix]) ) {
          for ( $i=1; $i<10 && isset($this->prefixes["$prefix$i"]); $i++ ) {
          }
        }
        if ( isset($this->prefixes["$prefix$i"]) ) {
            throw new Exception("Cannot find a free prefix for this namespace", 1);

        }
        $prefix = "$prefix$i";
      }
      else if ( $prefix == "" || isset($this->prefixes[$prefix]) ) {
        throw new \Exception("Cannot assign the same prefix to two different namespaces",1);

      }

      $this->prefixes[$prefix] = $prefix;
      $this->namespaces[$namespace] = $prefix;
    }
    else {
      if ( isset($this->namespaces[$namespace]) && $this->namespaces[$namespace] != $prefix ) {
        throw new \Exception("Cannot use the same namespace with two different prefixes",1);

      }
      $this->prefixes[$prefix] = $prefix;
      $this->namespaces[$namespace] = $prefix;
    }
  }

  /**
  * Special helper for tags in the DAV: namespace.
  *
  * @param object $element The tag are adding a new namespaced element to
  * @param string $tag the tag name
  * @param mixed  $content The content of the tag
  * @param array  $attributes An array of key/value pairs of attributes.
  */
  function DAVElement( &$element, $tag, $content=false, $attributes=false ) {
    if ( !isset($this->namespaces[self::$ns_dav]) ) $this->AddNamespace( self::$ns_dav, '' );
    return $this->NSElement( $element, $tag, $content, $attributes, self::$ns_dav );
  }

    /**
     * Special helper for namespaced tags.
     *
     * @param object $element The tag are adding a new namespaced element to
     * @param string $tag the tag name, possibly prefixed with the namespace
     * @param mixed $content The content of the tag
     * @param array $attributes An array of key/value pairs of attributes.
     * @param string $namespace The namespace for the tag
     *
     * @throws \Exception
     */
  function NSElement( &$element, $in_tag, $content=false, $attributes=false, $namespace=null ) {
    if ( $namespace == null && preg_match('/^(.*):([^:]+)$/', $in_tag, $matches) ) {
      $namespace = $matches[1];
      if ( preg_match('{^[A-Z][A-Z0-9]*$}', $namespace ) ) {
        throw new \Exception("Dodgy looking namespace from '".$in_tag."'!");
      }
      $tag = $matches[2];
    }
    else {
      $tag = $in_tag;
      if ( isset($namespace) ) {
        $tag = str_replace($namespace.':', '', $tag);
      }
    }

    if ( isset($namespace) && !isset($this->namespaces[$namespace]) ) $this->AddNamespace( $namespace );
    return $element->NewElement( $tag, $content, $attributes, $namespace );
  }

  /**
  * Special helper for tags in the urn:ietf:params:xml:ns:caldav namespace.
  *
  * @param object $element The tag are adding a new namespaced element to
  * @param string $tag the tag name
  * @param mixed  $content The content of the tag
  * @param array  $attributes An array of key/value pairs of attributes.
  */
  function CalDAVElement( &$element, $tag, $content=false, $attributes=false ) {
    if ( !isset($this->namespaces[self::$ns_caldav]) ) $this->AddNamespace( self::$ns_caldav, 'C' );
    return $this->NSElement( $element, $tag, $content, $attributes, self::$ns_caldav );
  }


  /**
  * Special helper for tags in the urn:ietf:params:xml:ns:carddav namespace.
  *
  * @param object $element The tag are adding a new namespaced element to
  * @param string $tag the tag name
  * @param mixed  $content The content of the tag
  * @param array  $attributes An array of key/value pairs of attributes.
  */
  function CardDAVElement( &$element, $tag, $content=false, $attributes=false ) {
    if ( !isset($this->namespaces[self::$ns_carddav]) ) $this->AddNamespace( self::$ns_carddav, 'VC' );
    return $this->NSElement( $element, $tag, $content, $attributes, self::$ns_carddav );
  }


  /**
  * Special helper for tags in the urn:ietf:params:xml:ns:caldav namespace.
  *
  * @param object $element The tag are adding a new namespaced element to
  * @param string $tag the tag name
  * @param mixed  $content The content of the tag
  * @param array  $attributes An array of key/value pairs of attributes.
  */
  function CalendarserverElement( &$element, $tag, $content=false, $attributes=false ) {
    if ( !isset($this->namespaces[self::$ns_calendarserver]) ) $this->AddNamespace( self::$ns_calendarserver, 'A' );
    return $this->NSElement( $element, $tag, $content, $attributes, self::$ns_calendarserver );
  }

  /**
  * Render the document tree into (nicely formatted) XML
  *
  * @param mixed $root A root XMLElement or a tagname to create one with the remaining parameters.
  * @param mixed $content Either a string of content, or an array of sub-elements
  * @param array $attributes An array of attribute name/value pairs
  * @param array $xmlns An XML namespace specifier
  *
  * @return A rendered namespaced XML document.
  */
  function Render( $root, $content=false, $attributes=false, $xmlns=null ) {
    if ( is_object($root) ) {
      /** They handed us a pre-existing object.  We'll just use it... */
      $this->root = $root;
    }
    else {
      /** We got a tag name, so we need to create the root element */
      $this->root = $this->NewXMLElement( $root, $content, $attributes, $xmlns );
    }

    /**
    * Add our namespace attributes here.
    */
    foreach( $this->namespaces AS $n => $p ) {
      $this->root->SetAttribute( 'xmlns'.($p == '' ? '' : ':') . $p, $n);
    }

    /** And render... */
    return $this->root->Render(0,'<?xml version="1.0" encoding="utf-8" ?>');
  }

  /**
  * @param string $in_tag The tag name of the new element, possibly namespaced
  * @param mixed $content Either a string of content, or an array of sub-elements
  * @param array $attributes An array of attribute name/value pairs
  * @param array $xmlns An XML namespace specifier
  */
  function NewXMLElement( $in_tag, $content=false, $attributes=false, $xmlns=null ) {
    if ( $xmlns == null && preg_match('/^(.*):([^:]+)$/', $in_tag, $matches) ) {
      $xmlns = $matches[1];
      $tagname = $matches[2];
    }
    else {
      $tagname = $in_tag;
    }

    if ( isset($xmlns) && !isset($this->namespaces[$xmlns]) ) $this->AddNamespace( $xmlns );
    return new XMLElement($tagname, $content, $attributes, $xmlns );
  }

  /**
  * Return a DAV::href XML element, or an array of them
  * @param mixed $url The URL (or array of URLs) to be wrapped in DAV::href tags
  *
  * @return XMLElement The newly created XMLElement object.
  */
  function href($url) {
    if ( is_array($url) ) {
      $set = array();
      foreach( $url AS $href ) {
        $set[] = $this->href( $href );
      }
      return $set;
    }
    if ( preg_match('[@+ ]',$url) ) {
      $url = str_replace( '%2F', '/', rawurlencode($url));
    }
    return $this->NewXMLElement('href', $url, false, 'DAV:');
  }

}


