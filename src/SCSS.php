<?php

namespace AKlump\Dompdf;

final class SCSS {

  /**
   * @var \AKlump\Dompdf\DomPdfFontLoader
   */
  private $loader;

  public function __construct(DomPdfFontLoader $loader) {
    $this->loader = $loader;
  }

  /**
   * @param $typeface
   *
   * @return string
   *
   * @url https://www.smashingmagazine.com/2013/02/setting-weights-and-styles-at-font-face-declaration/#style-linking
   */
  public function fontFace($typeface) {
    $family = $typeface['family'];
    if (strpos($family, ' ') !== FALSE) {
      $family = '"' . $family . '"';
    }
    $src = $this->loader->getOutputUrl() . '/' . basename($typeface['path']);

    return <<<SCSS
    @font-face {
      font-family: $family;
      src: url('$src') format('{$typeface['format']}');
      font-style: {$typeface['style']};
      font-weight: {$typeface['weight']};
    }

    SCSS;
  }
}
