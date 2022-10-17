<?php

namespace AKlump\Dompdf;

use Dompdf\Dompdf;
use FontLib\Font;
use FontLib\Table\Type\name;
use Symfony\Component\Filesystem\Path;

/**
 * @link https://github.com/dompdf/php-font-lib
 * @see \FontLib\AdobeFontMetrics
 */
class Importer {

  /**
   * @var array
   */
  protected $config = [];

  /**
   * @var \Dompdf\Dompdf
   */
  protected $dompdf;

  /**
   * @var \Output
   */
  protected $output;

  public function __construct(array $config, $output = NULL) {
    $this->config = $config;
    $this->output = $output;
    $this->dompdf = new Dompdf();
    $output_path = $this->getOutputPath();
    $this->ensureDirectoryExists($output_path);
    $this->dompdf->getOptions()->set('fontDir', $output_path);
  }

  public function flushOutputFiles() {
    $files = [
      $this->getScssPartialPath(),
      $this->getOutputPath() . '/installed-fonts.json',
    ];
    $files = array_merge(
      $files,
      glob($this->getOutputPath() . '/*.ttf'),
      glob($this->getOutputPath() . '/*.ufm')
    );
    foreach ($files as $file) {
      if (file_exists($file)) {
        unlink($file);
      }
    }
  }

  public function getOutputPath(): string {
    $path = $this->config['output']['path'] ?? NULL;

    return rtrim($path, '/');
  }

  public function getOutputUrl(): ?string {
    if (!isset($this->config['output']['url'])) {
      return NULL;
    }

    return rtrim($this->config['output']['url'], '/');
  }

  protected function getScssPartialPath(): string {
    return $this->getOutputPath() . '/_style.scss';
  }

  public function getAvailableFonts(): array {
    $fonts = [];
    foreach (($this->config['sources'] ?? []) as $glob_string) {
      $paths = glob($glob_string);
      foreach ($paths as $path) {
        $record = [];
        $font = Font::load($path);
        $font->parse();
        $records = $font->getData("name", "records");
        $record['name'] = $font->getFontName();
        $record['family'] = strval($records[name::NAME_PREFERRE_FAMILY] ?? $record['name']);
        $record['weight'] = $font->getFontWeight();
        $record['format'] = strtolower($font->getFontType());
        $record['path'] = $path;
        $record['style'] = 'normal';
        $subfamily = $font->getFontSubfamily();
        if (strpos(strtolower($subfamily), 'italic') !== FALSE) {
          $record['style'] = 'italic';
        }
        $font->close();
        $fonts[$record['family']][] = $record;
      }
    }

    return $fonts;
  }

  public function importFont(array $typefaces): bool {
    $get = function (string $type) use ($typefaces) {
      return array_values(array_filter($typefaces, function ($typeface) use ($type) {
          switch ($type) {
            case 'normal':
              return 'normal' === $typeface['style'] && $typeface['weight'] < 700;

            case 'bold':
              return 'normal' === $typeface['style'] && $typeface['weight'] >= 700;

            case 'italic':
              return 'italic' === $typeface['style'] && $typeface['weight'] < 700;

            case 'bold-italic':
              return 'italic' === $typeface['style'] && $typeface['weight'] >= 700;

            default:
              return FALSE;
          }
        }))[0] ?? NULL;
    };

    // Extract certain typefaces in a specific order.
    $typefaces = [
      $get('normal'),
      $get('bold'),
      $get('italic'),
      $get('bold-italic'),
    ];
    $paths = array_map(function ($typeface) {
      if (empty($typeface['path'])) {
        return NULL;
      }

      return $typeface['path'];
    }, $typefaces);
    list($normal, $bold, $italic, $bold_italic) = $paths;
    if ($normal) {
      $this->importHelper($typefaces[0]['name'], $normal, $bold, $italic, $bold_italic);

      // Add to the SCSS file.
      $scss_partial = $this->getScssPartialPath();
      if ($scss_partial) {
        $scss_helper = new SCSS($this);
        $fp = fopen($scss_partial, 'a+');
        foreach (array_filter($typefaces) as $typeface) {
          fwrite($fp, $scss_helper->fontFace($typeface) . PHP_EOL);
        }
        fclose($fp);
      }
    }

    return TRUE;
  }

  protected function ensureDirectoryExists(string &$path) {
    if (file_exists($path)) {
      return;
    }
    mkdir($path, 0755, TRUE);
    $path = realpath($path);
    $this->output->writeln("Output directory created:");
    $this->output->writeln('  ' . Path::makeRelative($path, getcwd()));
    $this->output->writeln();
  }

  /**
   * Installs a new font family
   * This function maps a font-family name to a font.  It tries to locate the
   * bold, italic, and bold italic versions of the font as well.  Once the
   * files are located, ttf versions of the font are copied to the fonts
   * directory.  Changes to the font lookup table are saved to the cache.
   *
   * @param string $fontname the font-family name
   * @param string $normal the filename of the normal face font subtype
   * @param string $bold the filename of the bold face font subtype
   * @param string $italic the filename of the italic face font subtype
   * @param string $bold_italic the filename of the bold italic face font subtype
   *
   * @throws Exception
   */
  protected function importHelper($fontname, $normal, $bold = NULL, $italic = NULL, $bold_italic = NULL) {
    $fontMetrics = $this->dompdf->getFontMetrics();

    // Check if the base filename is readable
    if (!is_readable($normal)) {
      throw new \Exception("Unable to read '$normal'.");
    }

    $dir = dirname($normal);
    $basename = basename($normal);
    $last_dot = strrpos($basename, '.');
    if ($last_dot !== FALSE) {
      $file = substr($basename, 0, $last_dot);
      $ext = strtolower(substr($basename, $last_dot));
    }
    else {
      $file = $basename;
      $ext = '';
    }

    if (!in_array($ext, array(".ttf", ".otf"))) {
      throw new \Exception("Unable to process fonts of type '$ext'.");
    }

    // Try $file_Bold.$ext etc.
    $path = "$dir/$file";

    $patterns = array(
      "bold" => array("_Bold", "b", "B", "bd", "BD"),
      "italic" => array("_Italic", "i", "I"),
      "bold_italic" => array("_Bold_Italic", "bi", "BI", "ib", "IB"),
    );

    foreach ($patterns as $type => $_patterns) {
      if (!isset($$type) || !is_readable($$type)) {
        foreach ($_patterns as $_pattern) {
          if (is_readable("$path$_pattern$ext")) {
            $$type = "$path$_pattern$ext";
            break;
          }
        }

        if (is_null($$type)) {
          $this->output->writeln("Unable to find $type face file.");
        }
      }
    }

    $fonts = compact("normal", "bold", "italic", "bold_italic");
    $entry = array();

    // Copy the files to the font directory.
    foreach ($fonts as $var => $src) {
      if (is_null($src)) {
        $entry[$var] = $this->dompdf->getOptions()
            ->get('fontDir') . '/' . mb_substr(basename($normal), 0, -4);
        continue;
      }

      // Verify that the fonts exist and are readable
      if (!is_readable($src)) {
        throw new \Exception("Requested font '$src' is not readable");
      }

      $dest = $this->dompdf->getOptions()
          ->get('fontDir') . '/' . basename($src);

      if (!is_writeable(dirname($dest))) {
        throw new \Exception(sprintf('Unable to write to destination "%s".', Path::makeRelative($dest, getcwd())));
      }

      //      $this->output->writeln("Copying %s", $this->unresolve($src));
      //      $this->output->writeln("  to %s", $this->getOutputPath() . '/' . basename($dest));

      if (!copy($src, $dest)) {
        throw new \Exception("Unable to copy '$src' to '$dest'");
      }

      $entry_name = mb_substr($dest, 0, -4);
      $entry_path = "$entry_name.ufm";

      //      $relative_ufm = $this->getOutputPath(FALSE);
      //      $relative_ufm .= '/' . basename($entry_path);
      //      $this->output->writeln("Generating Adobe Font Metrics for %s", $relative_ufm);
      //      $this->output->writeln();

      $font_obj = Font::load($dest);
      $font_obj->saveAdobeFontMetrics($entry_path);
      $font_obj->close();

      $entry[$var] = $entry_name;
    }

    // Store the fonts in the lookup table

    // By converting the path to a basename, this is more portable because it
    // will work in Lando or on production as the path is relative to the fonts
    // directory.
    $entry = array_map(function ($path) {
      return basename($path);
    }, $entry);

    $fontMetrics->setFontFamily($fontname, $entry);

    // Save the changes
    $fontMetrics->saveFontFamilies();
  }
}
