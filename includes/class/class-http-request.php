<?php

namespace http;
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 13/08/2018
 * Time: 11:28
 */

class Request {
  public static function getValue( $name, $def = false ) {
    if ( ! isset( $name ) || empty( $name ) || ! is_string( $name ) ) {
      return $def;
    }
    $returnValue = isset( $_POST[ $name ] ) ? trim( $_POST[ $name ] ) : ( isset( $_GET[ $name ] ) ? trim( $_GET[ $name ] ) : $def );
    $returnValue = urldecode( preg_replace( '/((\%5C0+)|(\%00+))/i', '', urlencode( $returnValue ) ) );

    return ! is_string( $returnValue ) ? $returnValue : stripslashes( $returnValue );
  }
}