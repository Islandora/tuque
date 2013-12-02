<?php

/**
 * @file
 * Bootstrap code runs before any PHPUnit tests.
 */
define('FEDORAVERSION', getenv('FEDORA_VERSION') ? getenv('FEDORA_VERSION') : '3.6.2');

// Append the Tuque ROOT directory as the include path.
define('TUQUE_ROOT', realpath(__DIR__ . '/..'));

// Initialize Tuque and enable autoclass loading.
include_once TUQUE_ROOT . '/Bootstrap.php';

/**
 * Generate a alpha/numeric string of the given $length.
 *
 * @param int $length
 *   The length of the string.
 *
 * @return string
 *   A alpha/numeric string of the given $length.
 */
function random_alpha_numeric_string($length) {
  static $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  return random_string($characters, $length);
}

/**
 * Generate a alphabetic string of the given $length.
 *
 * @param int $length
 *   The length of the string.
 *
 * @return string
 *   A alphabetic string of the given $length.
 */
function random_alpha_string($length) {
  static $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  return random_string($characters, $length);
}

/**
 * Generate a string of the given $length from the given $characters.
 *
 * @param string $characters
 *   The characters to use when generating the string.
 * @param int $length
 *   The length of the string.
 *
 * @return string
 *   A string of the given $length composed of the given $characters.
 */
function random_string($characters, $length) {
  $string = '';
  for ($p = 0; $p < $length; $p++) {
    $string .= $characters[mt_rand(0, (strlen($characters) - 1))];
  }
  return $string;
}
