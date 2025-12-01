![PHP 7 passing](https://img.shields.io/badge/build-passing-brightgreen?style=flat-square&logo=php&logoColor=green&label=PHP%207) ![PHP 8 passing](https://img.shields.io/badge/build-passing-brightgreen?style=flat-square&logo=php&logoColor=green&label=PHP%208)

# JIT Image Manipulation

## General

A simple way to manipulate images "just in time" via the URL or recipes, using PHP's build-in [GD Library](https://www.php.net/manual/en/ref.image.php). Supports caching, image quality settings and loading of offsite images.

### Supported image types

(auto-detected based on GD configuration)

- `gif`
- `jpg`
- `png` (with transparency)
- `bmp` (PHP ≥ 7.2)
- `webp` (supported since PHP 5.4, fully supported from PHP 7.1)
- `avif` (supported since PHP 8.1, fully supported from PHP 8.2)

### PHP compatibility

JIT runs with all PHP versions from __7.1 to 8.x__.

- PHP 7.1: All formats except `bmp`
- PHP 7.2–7.4: Full format support
- PHP 8.x: Full format support (using `instanceof GdImage`)

Backward compatibility between PHP 7 (`is_resource`) and 8 (`instanceof \GdImage`) is fully ensured.

### GD Library on shared hosts

JIT relies entirely on the PHP GD Library.
If certain image formats (e.g. AVIF or WebP) are not supported on your server, JIT will not be able to process these images until GD has been recompiled with the corresponding options.

Many host providers compile the GD library differently. As a result, support for image formats may vary.

To test which image formats are supported on your server, call the function `gd_info()`. It gets information about the version and capabilities of the installed GD library:


```bash
Array
(
    [GD Version] => bundled (2.1.0 compatible)
    [FreeType Support] => 1
    [FreeType Linkage] => with freetype
    [GIF Read Support] => 1
    [GIF Create Support] => 1
    [JPEG Support] => 1
    [PNG Support] => 1
    [WBMP Support] => 1
    [XPM Support] =>
    [XBM Support] => 1
    [WebP Support] => 1
    [BMP Support] => 1
    [AVIF Support] => 1
    [TGA Read Support] => 1
    [JIS-mapped Japanese Font Support] =>
)
```

The image formats marked with a `1` are supported.

## Installation

### Sym8

The extension is now part of [Sym8](https://github.com/sym8-io/sym8). After installation of Sym8, it is automatically installed.

### Manuell installation

1. Upload the folder 'jit_image_manipulation' to your Sym8/Symphony 'extensions' directory
2. Enable it by selecting "Just In Time (JIT) Image Manipulation" in the list, choose "Enable" from the `with-selected` menu, then click "Apply"


## Usage

### URL Parameters

The image manipulation is controlled via the URL, eg.:

    <img src="{$root}/image/2/80/80/5/fff{image/@path}/{image/filename}" />

The extension accepts four numeric settings and one text setting for the manipulation.

1. mode
2. width
3. height
4. reference position (for cropping only)
5. background color (for cropping only)

There are four possible modes:

- `0` none
- `1` resize
- `2` resize and crop (used in the example)
- `3` crop
- `4` resize to fit

If you're using mode `2` or `3` for image cropping you need to specify the reference position:

    +---+---+---+
    | 1 | 2 | 3 |
    +---+---+---+
    | 4 | 5 | 6 |
    +---+---+---+
    | 7 | 8 | 9 |
    +---+---+---+

If you're using mode `2` or `3` for image cropping, there is an optional fifth parameter for background color. This can accept shorthand or full hex colors (without hash).

- _For `.jpg` images, it is advised to use this if the crop size is larger than the original, otherwise the extra canvas will be black._
- _For transparent `.png` or `.gif` images, supplying the background color will fill the image. This is why the setting is optional._

The extra fifth parameter makes the URL look like this:

    <img src="{$root}/image/2/80/80/5/ffffff{image/@path}/{image/filename}" />

- _If you wish to crop and maintain the aspect ratio of an image but only have one fixed dimension (that is, width or height), simply set the other dimension to 0._

### Recipes

Recipes are named rules for the JIT settings which help improve security and are more convenient. They can be edited on the preferences page in the JIT section and are saved in  `/workspace/jit-image-manipulation/recipes.php`. A recipe URL might look like:

    <img src="{$root}/image/thumbnail{image/@path}/{image/filename}" />

When JIT parses a URL like this, it will check the recipes file for a recipe with a handle of `thumbnail` and apply it's rules. You can completely disable dynamic JIT rules and choose to use recipes only which will prevent a malicious user from hammering your server with large or multiple JIT requests.

Recipes can be copied between installations and changes will be reflected by every image using this recipe.

### External sources & Trusted Sites

In order pull images from external sources, you must set up a white-list of trusted sites. To do this, go to "System > Preferences" and add rules to the "JIT Image Manipulation" rules textarea. To match anything use a single asterisk (`*`).

The URL then requires a sixth parameter, external, (where the fourth and fifth parameter may be optional), which is simply `1` or `0`. By default, this parameter is `0`, which means the image is located on the same domain as JIT. Setting it to `1` will allow JIT to process external images provided they are on the Trusted Sites list.

    <img src="{$root}/image/1/80/80/1/{full/path/to/image}" />
                                    ^ External parameter

__Note for recipes:__

For recipes, the checkbox “External Image” is __not an AND option but an OR option__. This means that a recipe can be applied __either to local images or to external images__.
