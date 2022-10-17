#!/usr/bin/env php
<?php

/**
 * @file
 * Font importer
 *
 * @code
 * php import.php path_to_config.yml
 * @endcode
 */

namespace AKlump\Dompdf;

use Symfony\Component\Yaml\Yaml;

require_once $GLOBALS['_composer_autoload_path'];

try {
  if (!isset($argv[1])) {
    throw new \InvalidArgumentException('You must provide the path to "dompdf-fonts.config.yml" as the first argument to this script.');
  }
  $config_path = Path::makeAbsolute($argv[1], getcwd());
  $config = Yaml::parseFile($config_path);

  // Resolve all relative config paths.
  $base_path = dirname($config_path);
  $resolve = function (string &$path) use ($base_path) {
    $path = Path::makeAbsolute($path, $base_path);
  };
  foreach ($config['sources'] as &$source) {
    $resolve($source);
  }
  $resolve($config['output']['path']);

  $output = new Output();
  $service = new Importer($config, $output);
  $available = $service->getAvailableFonts();
  if (empty($available)) {
    $output->writeln('Nothing to do. No fonts found in "%s".', Path::makeRelative($service->getOutputPath(), getcwd()));
    exit(0);
  }

  $service->flushOutputFiles();
  foreach (array_keys($available) as $font_family_name) {
    $font = $available[$font_family_name];
    if (!$font) {
      $output->writeln('Skipping "%s":', $font_family_name);
      $output->writeln('  No available font by that name.');
    }
    else {
      $result = $service->importFont($available[$font_family_name]);
      if ($result) {
        foreach ($available[$font_family_name] as $typeface) {
          $output->writeln('%s; font-weight: %s; font-style: %s', $typeface['family'], $typeface['weight'], $typeface['style']);
        }
      }
      else {
        $output->writeln('"%s" FAILED.', $font_family_name);
      }
      $output->writeln('---');
    }
  }
  exit(0);
}
catch (\Exception $exception) {
  echo $exception->getMessage() . PHP_EOL;
  exit(1);
}
