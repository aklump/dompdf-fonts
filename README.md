# How to Add Custom Fonts to DomPDF

## Installation

`composer create-package aklump/dompdf-fonts`

## Get the .ttf font files

1. Download one or more font families, say from <https://fonts.google.com>
2. Copy the _.ttf_ versions to your source directory. In the examples shown below, that directory is _./fonts_.
3. You will need up to four versions of the font: normal, bold, italic and bold italic.

_Example file tree._

```
.
├── dist
│   └── dompdf_fonts
│       ├── Merriweather--bold-italic.ttf
│       ├── Merriweather--bold-italic.ufm
│       ├── Merriweather--bold.ttf
│       ├── Merriweather--bold.ufm
│       ├── Merriweather--italic.ttf
│       ├── Merriweather--italic.ufm
│       ├── Merriweather--normal.ttf
│       ├── Merriweather--normal.ufm
│       ├── _style.scss
│       └── installed-fonts.json
└── fonts
    ├── Merriweather--bold-italic.ttf
    ├── Merriweather--bold.ttf
    ├── Merriweather--italic.ttf
    ├── Merriweather--normal.ttf
    └── dompdf
        ├── config.yml
        └── importer.php
```

## Set the import configuration

1. Update _config.yml_ as appropriate. All paths are relative to _config.yml_'s parent directory.

_File: \_config.yml_

```yaml
source:
  - ../Merriweather*.ttf
output: ../../dist/dompdf_fonts/
```

## Run the importer

1. Run `php importer.php` to process your fonts.
2. Inspect to make sure your output directory contains the necessary files.

## Use with Dompdf instances

1. Set the fonts directory to match `output.path` on every new Dompdf instance in your code. This is an example from a Drupal 9 module.

```php
$options = new \Dompdf\Options();

// Determine the configuration directory by reading the import config.
$font_config_dir = \Drupal::service('extension.list.module')
    ->getPath('my_module') . '/fonts/dompdf/';
$fonts_config = \Symfony\Component\Yaml\Yaml::parseFile($font_config_dir . '/config.yml');
$fonts_dir = realpath($font_config_dir . '/' . $fonts_config['output']['path']);
$options->setFontDir($fonts_dir);

$dompdf = new \Dompdf\Dompdf($options);
```

## Use with HTML markup

1. To see your font in the browser you must import the SCSS partial. When only rendering PDFs, DomPDF does not use _\_style.scss_.

_File: my_module/scss/\_pdf.scss_

```scss
@import "../dist/dompdf_fonts/style";

@mixin pdf_font_serif {
  font-family: Merriweather, Georgia, Times, "Times New Roman", serif;
}

h1 {
  @include pdf_font_serif;
}
```
