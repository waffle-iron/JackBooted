<?php
namespace Jackbooted\Html;

use \Jackbooted\Forms\Request;
use \Jackbooted\Util\Log4PHP;
use \Jackbooted\Util\Invocation;
/** Utilities.php - Utility functions
 *
 * @copyright Confidential and copyright (c) 2016 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 *
 */

class Tag extends \Jackbooted\Util\JB {
    private static $LF = '';
    private static $log;

    public static function init () {
        self::$log = Log4PHP::logFactory ( __CLASS__ );

    }

    public function __call ( $name, $arguments ) {
        if ( substr( $name, 0, 1 ) == '_' ) {
            return self::_hTag ( substr ( $name, 1 ) );
        }
        else {
            return self::hTag ( $name ( count ( $arguments ) > 0 ) ? $arguments[0] :  [] );
        }
    }

    /**
     * As of PHP 5.3.0
     * Automatically handle opening and closing html
     */
    public static function __callStatic ( $name, $arguments ) {
        if ( substr( $name, 0, 1 ) == '_' ) {
            return self::_hTag ( substr ( $name, 1 ) );
        }
        else {
            return self::hTag ( $name ( count ( $arguments ) > 0 ) ? $arguments[0] :  [] );
        }
    }

    /**
     * Sets the linefeed on or off. During development it is good to have it on.
     * @param boolean $flag true means want line feeds
     */
    public static function setLineFeed ( $flag ) {
        self::$LF = ( $flag ) ? "\n" : '';
    }
    /**
     * Creates the html for href
     * @param string $url
     * @param string display
     * @param array $attribs extra attributes style events etc
     * @returns string
     */
    public static function hRef ( $url, $display, $attribs= [] ) {
        return self::hTag ( 'a', array_merge (  [ 'href' => $url ], $attribs ) ) .
                 $display .
               self::_hTag ( 'a' );
    }

    /** Creates and returns the HTML required for the beginning of a form
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    private static $formCount=0;
    public static function form ( $attribs= [], $doubleClickProtection=true ) {
        if ( ! isset ( $attribs['action'] ) ) $attribs['action'] = '?';
        if ( ! isset ( $attribs['method'] ) ) $attribs['method'] = 'post';
        if ( ! isset ( $attribs['id'] ) )     $attribs['id'] = 'FRM_' . self::$formCount ++;

        if ( isset ( $attribs['submitmsg'] ) ) {
            $submitMsg = $attribs['submitmsg'];
            unset ( $attribs['submitmsg'] );
        }
        else {
            $submitMsg = 'Submitting...';
        }


        if ( isset ( $attribs['onsubmit'] ) ) {
            $attribs['onSubmit'] = $attribs['onsubmit'];
            unset ( $attribs['onsubmit'] );
        }

        $html = '';

        if ( $doubleClickProtection ) {
            $killDblClk = "$('#{$attribs['id']} input[type=submit]').val('{$submitMsg}').attr('disabled',true);";

            if ( isset ( $attribs['onSubmit'] ) ) {
                if ( preg_match ( '/^(.*)(return true;)$/', $attribs['onSubmit'], $matches ) ) {
                    $attribs['onSubmit'] = $matches[1] . $killDblClk . $matches[2];
                }
                else {
                    $attribs['onSubmit'] = $killDblClk . $attribs['onSubmit'];
                }
            }
            else {
                $attribs['onSubmit'] = $killDblClk;
            }
            $html .= JS::library ( JS::JQUERY );
        }

        $html .= self::hTag ( 'form', $attribs );
        return $html;
    }
    /** Creates and returns the HTML required for the end of a form
     * @returns string The resulting HTML
     * @public
     */
    public static function _form ( ) {
        return  self::_hTag ( 'form' );
    }

    /**
     *  Creates and returns the HTML required to display an image
     * @param $s The src of the image
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function img ( $s, $attribs= [] ) {
        if ( ! isset ( $attribs['border'] ) ) $attribs['border'] = 0;
        return '<img src="' . $s . '"' . self::toAttribs ( $attribs ) . '/>' . self::$LF;
    }


    /**
     * function to generate &lt;table&gt;
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function table ( $attribs= [] ) {
        return self::hTag ( 'table', $attribs );
    }

    /**
     * function to generate &lt;br/&gt;
     * @returns string The resulting HTML
     */
    public static function br ( ) {
        return '<br/>';
    }

    /**
     * function to generate &lt;/table&gt;
     * @returns string The resulting HTML
     */
    public static function _table ( ) {
        return self::_hTag ( 'table' );
    }

    /**
     * function to generate &lt;div&gt;
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function div ( $attribs= [] ) {
        return self::hTag ( 'div', $attribs );
    }

    /**
     * function to generate &lt;/div&gt;
     * @returns string The resulting HTML
     */
    public static function _div ( ) {
        return self::_hTag ( 'div' );
    }

    /**
     * function to generate &lt;td&gt;
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function tr ( $attribs= [] ) {
        return self::hTag ( 'tr', $attribs );
    }

    /**
     * function to generate &lt;td&gt;
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function td ( $attribs= [] ) {
        return self::hTag ( 'td', $attribs );
    }


    /**
     * function to generate &lt;th&gt;
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function th ( $attribs= [] ) {
        return self::hTag ( 'th', $attribs );
    }


    /**
     * function to generate &lt;/tr&gt;
     * @returns string The resulting HTML
     */
    public static function _tr ( ) {
        return self::_hTag ( 'tr' );
    }

    /**
     * function to generate &lt;/td&gt;
     * @returns string The resulting HTML
     */
    public static function _td ( ) {
        return self::_hTag ( 'td' );
    }

    /**
     * function to generate &lt;p&gt;
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function p ( $attribs= [] ) {
        return self::hTag ( 'p', $attribs );
    }

    /**
     * function to generate &lt;/p&gt;
     * @returns string The resulting HTML
     */
    public static function _p ( ) {
        return self::_hTag ( 'p' );
    }
    /**
     * function to generate &lt;li&gt;
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function li ( $attribs= [] ) {
        return self::hTag ( 'li', $attribs );
    }

    /**
     * function to generate &lt;/li&gt;
     * @returns string The resulting HTML
     */
    public static function _li ( ) {
        return self::_hTag ( 'li' );
    }
    /**
     * function to generate &lt;ul&gt;
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function ul ( $attribs= [] ) {
        return self::hTag ( 'ul', $attribs );
    }

    /**
     * function to generate &lt;/ul&gt;
     * @returns string The resulting HTML
     */
    public static function _ul ( ) {
        return self::_hTag ( 'ul' );
    }
    /**
     * Arbitary Tag
     * function to generate &lt;p&gt;
     * @param $attribs extra attributes style events etc
     * @returns string The resulting HTML
     */
    public static function hTag ( $t, $attribs= [] ) {
        return '<' . $t . self::toAttribs ( $attribs ) . '>';
    }

    /**
     * Arbitary Tag end
     * function to generate &lt;?&gt;
     * @returns string The resulting HTML
     */
    public static function _hTag ( $t ) {
        return ( '</' . $t . '>'  . self::$LF );
    }

    /**
     * function to generate &lt;/th&gt;
     * @returns string The resulting HTML
     */
    public static function _th ( ) {
        return self::_hTag ( 'th' );
    }

    /** Creates and returns the HTML required to display generic Input
     * form field
     * @param varargs $attribs attributes for this input
     * @returns string The resulting HTML
     */
    private static $inputID = 0;
    public static function input ( $attrib1=[], $attrib2=[] ) {
        $attribs = array_merge ( $attrib1, $attrib2 );
        if ( ! isset ( $attribs['id'] ) ) {
            $attribs['id'] = 'ID_' . self::$inputID++;
        }

        return '<input' . self::toAttribs ( $attribs ) . '/>' . self::$LF;
    }
    /** Creates and returns the HTML required to display ( sic ) a hidden
     * form field
     * @param string $name The name of the field
     * @param string $value The value of the field
     * @param array $attribs Attribs for the hidden tag
     * @returns string The resulting HTML
     * @public
     */
    public static function hidden ( $name, $value='', $attribs=[] ) {
        return self::input (  [ 'type' => 'hidden', 'name' => $name , 'value' => $value ], $attribs  );
    }
    /** Creates and returns the HTML required to display radio
     * @param string $name The name of the field
     * @param string $value The value of the field
     * @param array $attribs Attribs for the hidden tag
     * @returns string The resulting HTML
     * @public
     */
    static function radio ( $name, $val, $checked=FALSE, $attribs=[] ) {
        $inpAttribs = ( is_array ( $checked  ) ) ? $checked :  [];
        $inpAttribs['type'] = 'radio';
        $inpAttribs['name'] = $name;
        $inpAttribs['value'] = $val;
        if ( is_bool ( $checked ) && $checked ) $inpAttribs['checked'] = 'checked';
        return self::input ( $attribs, $inpAttribs );
    }

    /**
     * Generates the HTML for a label element
     * @param string $for ID of the element that this label is for
     * @param string $display Text to display
     * @return string
     */
    static function label ( $for, $display, $attribs=[] ) {
        $attribs['for'] = $for;
        return Tag::hTag ( 'label', $attribs ) . $display . Tag::_hTag ( 'label' );
    }

    /**
     * Generates the text tag
     * @param array $attribs array of attributes to output
     * @returns string The resulting HTML
     */
    public static function text ( $name, $value='', $attribs=[] ) {
        $extraAttribs =  [ 'name' => $name ];
        if ( ! isset ( $attribs['type'] ) ) {
            $extraAttribs['type'] = 'text';
        }

        if ( is_array ( $value ) ) {
            foreach ( $value as $key => $val ) {
                $extraAttribs[$key] = $val;
            }
        }
        else if ( $value != '' ) {
            $extraAttribs['value'] = $value;
        }

        foreach ( $attribs as $key => $val ) {
            $extraAttribs[$key] = $val;
        }

        /* Fix this Should be key_exists or something like that */
        if ( ! array_key_exists ( 'value', $extraAttribs ) ) {
            $extraAttribs['value'] = Request::get ( $name );
        }

        return self::input ( $extraAttribs );
    }

    /**
     * Generates the button tag
     * @param string $name name of the button
     * @param array $attribs array of attributes to output
     * @returns string The resulting HTML
     */
    public static function button ( $name, $attribs=[] ) {
        return self::input (  [ 'type' => 'button', 'value' => $name ], $attribs );
    }
    /**
     * Generates the image input
     * @param string $name name of the button
     * @param array $attribs array of attributes to output
     * @returns string The resulting HTML
     */
    public static function image ( $name, $attribs=[] ) {
        return self::input (  [ 'type' => 'image', 'value' => $name ], $attribs );
    }

    /**
     * Generates a password field
     * @param <type> $name name of the field
     * @param <type> $attribs additional attributes
     * @returns string The resulting HTML
     */
    public static  function password ( $name, $attribs=[] ) {
        return self::input (  [ 'type' => 'password', 'name' => $name ], $attribs );
    }

    /**
     * Generates the submit tag
     * @param array $attribs array of attributes to output
     * @returns string The resulting HTML
     */
    public static function submit ( $name, $value='', $attribs=[] ) {
        if ( is_array ( $value ) ) {
            $attribs = $value;
            $value   = $name;
        }
        else if ( ( ! isset( $value ) || $value == false ) ) {
            $value = $name;
        }

        return self::input (  [ 'type' => 'submit', 'name' => $name, 'value' => $value ], $attribs );
    }

    /**
     * Generates the submit tag
     * @param array $attribs array of attributes to output
     * @returns string The resulting HTML
     */
    public static function submitUI ( $name, $value='', $attribs=[] ) {
        if ( is_array ( $value ) ) {
            $attribs = $value;
            $value   = $name;
        }
        else if ( ( ! isset( $value ) || $value == false ) ) {
            $value = $name;
        }
        if ( ! isset( $attribs['id'] ) ) {
            $attribs['id'] = 'linkButton_' . Invocation::next();
        }

        return Widget::button( "#{$attribs['id']}" ) .
               self::input (  [ 'type' => 'submit', 'name' => $name, 'value' => $value ], $attribs );
    }
    /**
     * Generates HTML for checkbox
     * @param string $name
     * @param string $value
     * @param boolean $checked
     * @param array $attribs array of attributes to output
     * @returns string The resulting HTML
     */
    static function checkBox ( $name, $val, $checked=FALSE, $attribs=[] ) {
        $inpAttribs =  [ 'type' => 'checkbox', 'name' => $name, 'value' => $val ];
        if ( $checked ) $inpAttribs['checked'] = 'checked';
        return self::input ( $attribs, $inpAttribs );
    }

    /**
     * Create a standard button that goes to a link
     * @param string $name Name on the button
     * @param string $url url to go to
     * @param string $title title if necessary
     * @param array $attribs extra stuff
     * @return string Html tag
     */
    public static function linkButton ( $url, $name, $attribs=[] ) {
        if ( is_string ( $attribs ) ) $attribs =  [ $attribs ];
        if ( isset ( $attribs['onClick'] ) ) {
            $xtraJS = $attribs['onClick'];
            unset ( $attribs['onClick'] );
        }
        else {
            $xtraJS = 'true';
        }
        $extraAttribs =  [ 'onClick' => "if($xtraJS){location.href='$url';return true;}else{return false;}" ];
        return self::button ( $name,  array_merge ( $extraAttribs, $attribs ) );
    }

    public static function hRefButton ( $url, $name, $attribs=[] ) {
        if ( is_string ( $attribs ) ) $attribs =  [ $attribs ];
        if ( ! isset( $attribs['id'] ) ) {
            $attribs['id'] = 'linkButton_' . Invocation::next();
        }

        return Widget::button( "#{$attribs['id']}" ) .
               self::hRef( $url, $name, $attribs );
    }



    /**
     * Generates the select tag
     * @param array $attribs array of attributes to output
     * @returns string The resulting HTML
     */
    public static function select ( $name='', $attribs= [] ) {
        $inpAttribs =  [ ];
        if ( isset ( $name ) && $name != false ) $inpAttribs['NAME'] = $name;
        return self::hTag ( 'select', array_merge ( $inpAttribs, $attribs ) ) . self::$LF;
    }

    /**
     * function to close the select tag
     * @returns string The resulting HTML
     */
    public static function _select ( ) {
        return self::_hTag ( 'select' ) . self::$LF;
    }

    /**
     * Generates an Option tag
     * @param string $displayedOnList The string to be displayed on the list
     * @param string $value Value for this option
     * @param boolean $selected
     * @returns string The resulting HTML
     */
    public static function optionTag ( $value, $displayedOnList, $selected=FALSE ) {
        $attrib =  [ 'value' => $value ];
        if ( $selected ) $attrib['selected'] = 'selected';

        return self::hTag ( 'option', $attrib ) .
                 $displayedOnList .
               self::_hTag ( 'option' ) . self::$LF;
    }

    /**
     * Generates the Text area tag with included text
     * @param string $name name of this tag
     * @param array $attribs attributes to write out
     * @returns string The resulting HTML
     */
    public static function textArea ( $name, $val='', $attribs=[] ) {
        return self::textAreaTag ( $name, $attribs ) . $val . self::_textareaTag ( );
    }

    /**
     * Generates text area tag
     * @param string $name name of this tag
     * @param array $attribs attributes to write out
     * @returns string The resulting HTML
     */
    static function textAreaTag ( $name, $attribs=[] ) {
        return self::hTag ( 'textarea', array_merge (  [ 'name' => $name ], $attribs ) ) . self::$LF;
    }

    /**
     * generates a close text area tag
     * @returns string The resulting HTML
     */
    public static function _textAreaTag ( ) {
        return self::_hTag ( 'textarea' ) . self::$LF;
    }

    /**
     * Encodes the string with special characters to protect from XSS
     * @param type $s The string to encode
     * @return string This is the encoded string
     */
    public static function e ( $s ) {
        return htmlentities ( $s );
    }

    /**
     * Converts an array of key/val pairs to html attributes
     * @param array $attribs attributes to write out
     */
    private static function toAttribs ( $attribs=[] ) {
        // Need to have this for backward compatibility
        if ( $attribs == null ) {
            return '';
        }
        else if ( is_string ( $attribs ) ) {
            self::depricated ( $attribs );
            return ' ' . $attribs;
        }
        else {
            $tag = '';
            foreach ( $attribs as $key => $val ) {
                if ( is_int ( $key ) ) {
                    self::depricated ( $val );
                    $tag .= ' ' . $val;
                }
                else {
                    $tag .= ' ' . strtolower ( $key ) . '="' . htmlspecialchars ( $val ) . '"';
                }
            }
            return $tag;
        }
    }
    private static function depricated ( $attrib ) {
        // Try to do less calculations if this is not being displayed
        if ( ! self::$log->isDisplayed ( Log4PHP::TRACE ) ) return;

        $stack = debug_backtrace ();
        foreach ( $stack as $key => $calledFrom ) {
            if ( ! in_array ( $calledFrom['class'],  [ 'Tag', 'Lists' ] ) ) break;
        }
        $stackMessage = '';
        for ( $i=$key; $i<count($stack); $i++ ) {
            $stackMessage .= basename ( $stack[$i]['file'], '.php' ) . '('. $stack[$i]['line'] . ') ';
        }

        self::$log->warn ( $stackMessage . $attrib );
    }
}
