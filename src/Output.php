<?php

namespace AKlump\Dompdf;

/**
 * Handles the output messaging.
 */
class Output {

  /**
   * @param string $message
   * @param ... Any number of replacements tokens for $message
   *
   * @return void
   *
   * @see \sprintf()
   */
  public function writeln(string $message = '', ...$values) {
    if ('---' === $message) {
      $message = str_repeat('-', 80);
    }
    if (func_num_args() > 1) {
      $values = func_get_args();
      array_shift($values);
      echo sprintf($message, ...$values) . PHP_EOL;

      return;
    }
    echo $message . PHP_EOL;
  }
}
