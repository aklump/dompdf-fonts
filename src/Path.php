<?php

namespace AKlump\Dompdf;

use Symfony\Component\Filesystem\Filesystem;

/**
 * A shim class for wider version support.
 */
class Path {

  public static function makeAbsolute(string $path, string $basePath): string {
    if (class_exists('\Symfony\Component\Filesystem\Path')) {
      return \Symfony\Component\Filesystem\Path::makeAbsolute($path, $basePath);
    }

    // Path is already absolute.
    if ('/' === substr($path, 0, 1)) {
      return $path;
    }

    return rtrim($basePath, '/') . "/$path";
  }

  public static function makeRelative(string $path, string $basePath): string {
    if (class_exists('\Symfony\Component\Filesystem\Path')) {
      return \Symfony\Component\Filesystem\Path::makeRelative($path, $basePath);
    }
    $fs = new Filesystem();
    $fs->makePathRelative($path, $basePath);
  }
}
