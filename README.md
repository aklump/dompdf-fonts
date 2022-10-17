# DOMPDF Fonts

I wrote this project to simplify using [custom fonts](https://github.com/dompdf/dompdf/wiki/About-Fonts-and-Character-Encoding) with [DOMPDF](https://dompdf.github.io/).

## Installation

1. `composer require aklump/dompdf-fonts`
2. Create a directory in your project to hold the source fonts and the configuration file.
3. Copy the config file found in _vendor/aklump/dompdf-fonts/dompdf-fonts.config.dist.yml_ to the font folder as _dompdf-fonts.config.yml_.

_For example:_

```shell
composer require aklump/dompdf-fonts
mkdir fonts
cp vendor/aklump/dompdf-fonts/dompdf-fonts.config.dist.yml fonts/dompdf-fonts.config.yml
```

## Get the .ttf font files

1. Download one or more font families, say from <https://fonts.google.com>
2. For use with DomPDF you only need the _.ttf_ versions. Copy those to your source directory. In the examples shown below, that directory is _./fonts_.
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
    ├── dompdf-fonts.config.yml
    ├── Merriweather--bold-italic.ttf
    ├── Merriweather--bold.ttf
    ├── Merriweather--italic.ttf
    ├── Merriweather--normal.ttf
    └── dompdf
        └── import.php
```

## Set the import configuration

1. _dompdf-fonts.config.yml_ should have been copied from _dompdf-fonts.config.dist.yml_ when you installed this, if not you must manually do so now.
2. Update _dompdf-fonts.config.yml_ as appropriate. All paths are relative to _dompdf-fonts.config.yml_'s parent directory.

_File: \_dompdf-fonts.config.yml_

```yaml
source:
  - ../Merriweather*.ttf
output: ../../dist/dompdf_fonts/
```

## Run the importer

1. Run `php vendor/bin/import.php path/to/dompdf-fonts.config.yml` to process your fonts.
2. Inspect to make sure your output directory contains the necessary files.

## Use with Dompdf instances

1. Set the fonts directory to match `output.path` on every new DOMPDF instance in your code. This is an example from a Drupal 9 module.

```php
$options = new \Dompdf\Options();

// Determine the configuration directory by reading the import config.
$fonts_base_path = \Drupal::service('extension.list.module')
    ->getPath('my_module') . '/fonts';
$fonts_config = \Symfony\Component\Yaml\Yaml::parseFile($fonts_base_path . '/dompdf-fonts.config.yml');
$fonts_dir = realpath($fonts_base_path . '/' . $fonts_config['output']['path']);
$options->setFontDir($fonts_dir);

$dompdf = new \Dompdf\Dompdf($options);
```

## Use with HTML markup

1. To see your font in the browser you must import the SCSS partial. When only rendering PDFs, DOMPDF does not use _\_style.scss_.

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
