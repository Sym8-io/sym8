# Subresource Integrity

A simple way to compute base64 encoded sha (256, 384, 512) of assets files used in `link` and `script` tags.

- You specify the files in a xml file (`manifest/sri.xml`)
- The results are in the provided [data source](#data-source)

See <https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity> for more details on SRI.

## Requirements

- Symphony CMS version 2.6.0 and up (as of the day of the last release of this extension)

## Installation

- `git clone` / download and unpack the tarball file
- Put into the extension directory
- Enable/install just like any other extension

## How to use

1. Create the file `manifest/sri.xml`
2. [Fill it up](#srixml-file)
3. Add the SRI data sources on pages that needs it.
4. [Set the `integrity` attribute accordingly](#integrity-attribute)

### `sri.xml` file

This file must contains a list of all files where the integrity hash needs to be computed. The file must follow this schema:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<files hash="sha512">
    <file>symphony/assets/css/symphony.min.css</file>
    <file hash="sha256">symphony/assets/js/symphony.min.js</file>
</files>
```

File path are relative to the `DOCROOT` and must never start with a trailing slash. Hash algorithm can be specified globally by setting the `hash` attribute of the root level tag. Hash algorithm can also be specified on a a per file basis, using the same `hash` attribute.

### `integrity` attribute

Using the provided data source, you can output the right value with this piece of either one of:

```xslt
<!-- Using the filename attribute -->
<script src="/path/to/file.ext" integrity="{/data/sri/file[@filename='file.ext']/@integrity}"></script>
<!-- Using the complete path -->
<link href="/path/to/file.ext" integrity="{/data/sri/file[.='path/to/file.ext']/@integrity}" />
```

### Data Source

The data source outputs some useful information. Also, any exception thrown in the data source execution process are logged into Symphony's logs.

```xml
<sri>
    <file filename="symphony.min.css" hash="sha512" integrity="sha512-0UfXWfRg5GzU/l6VXUKRMl3TFmz0FijSoJMt3vmfjwTkYztMDWqpvFZ4F4eMY9c5C+/n49cuFya8A0vN95deug==" cache="miss-saved">symphony/assets/css/symphony.min.css</file>
    <file filename="symphony.min.js" hash="sha256" integrity="sha256-8jb0A0Ei0W+is2NHkiAeUdWDrXPhYQeoFGF6ljIKCKs=" cache="hit">symphony/assets/js/symphony.min.js</file>
</sri>
```

- `filename` contains the name of the file
- `hash` contains the hash algorithm used
- `integrity` contains the value set in the `integrity` attribute
- `cache` contains info about the cache. Possible values are
    + `miss`: Not found in cache nor was the cache updated
    + `saved-miss`: Not found in cache but saved for future use
    + `hit`: Integrity value found in cache
    + `disabled`: Cache is disabled

### Caching

This extension uses Symphony's database driven cache provider in order to prevent reading each file and computing the hash on each request. The cache ttl is 30 days, but the data source checks the file modified time before using any value from the cache. If the file changed, the hash is updated.

## License

[MIT](http://deuxhuithuit.mit-license.org)
