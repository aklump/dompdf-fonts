<?php

namespace AKlump\Dompdf;

use Symfony\Component\Yaml\Yaml;

require_once "./vendor/autoload.php";

// Load the configuration JSON
$config = __DIR__ . '/config.yml';
$config = Yaml::parseFile($config);
$config['resolve_path'] = __DIR__;
if (!is_dir($config['resolve_path'])) {
  throw new \RuntimeException();
}

try {
  $output = new Output();
  $service = new DomPdfFontLoader($config, $output);
  $available = $service->getAvailableFonts();
  if (empty($available)) {
    $output->writeln('Nothing to do. No fonts found.');
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
        $output->writeln('"%s" IMPORTED.', $font_family_name);
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
