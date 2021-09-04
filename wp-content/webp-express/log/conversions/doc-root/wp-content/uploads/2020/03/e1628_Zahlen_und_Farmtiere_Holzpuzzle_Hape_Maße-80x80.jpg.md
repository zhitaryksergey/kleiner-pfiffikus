WebP Express 0.17.5. Conversion triggered using bulk conversion, 2020-09-29 12:52:59

*WebP Convert 2.3.2*  ignited.
- PHP version: 7.3.22
- Server software: Apache

Stack converter ignited

Options:
------------
The following options have been set explicitly. Note: it is the resulting options after merging down the "jpeg" and "png" options and any converter-prefixed options.
- source: [doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Maße-80x80.jpg
- destination: [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Maße-80x80.jpg.webp
- log-call-arguments: true
- converters: (array of 9 items)

The following options have not been explicitly set, so using the following defaults:
- converter-options: (empty array)
- shuffle: false
- preferred-converters: (empty array)
- extra-converters: (empty array)

The following options were supplied and are passed on to the converters in the stack:
- encoding: "auto"
- metadata: "none"
- near-lossless: 60
- quality: 82
------------


*Trying: cwebp* 

Options:
------------
The following options have been set explicitly. Note: it is the resulting options after merging down the "jpeg" and "png" options and any converter-prefixed options.
- source: [doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Maße-80x80.jpg
- destination: [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Maße-80x80.jpg.webp
- encoding: "auto"
- low-memory: true
- log-call-arguments: true
- metadata: "none"
- method: 6
- near-lossless: 60
- quality: 82
- use-nice: true
- command-line-options: ""
- try-common-system-paths: true
- try-supplied-binary-for-os: true

The following options have not been explicitly set, so using the following defaults:
- alpha-quality: 85
- auto-filter: false
- default-quality: 75
- max-quality: 85
- preset: "none"
- size-in-percentage: null (not set)
- skip: false
- rel-path-to-precompiled-binaries: *****
- try-cwebp: true
- try-discovering-cwebp: true
------------

Encoding is set to auto - converting to both lossless and lossy and selecting the smallest file

Converting to lossy
Looking for cwebp binaries.
Discovering if a plain cwebp call works (to skip this step, disable the "try-cwebp" option)
- Executing: cwebp -version 2>&1. Result: *Exec failed* (the cwebp binary was not found at path: cwebp)
Nope a plain cwebp call does not work
Discovering binaries using "which -a cwebp" command. (to skip this step, disable the "try-discovering-cwebp" option)
Found 0 binaries
Discovering binaries by peeking in common system paths (to skip this step, disable the "try-common-system-paths" option)
Found 0 binaries
Discovering binaries which are distributed with the webp-convert library (to skip this step, disable the "try-supplied-binary-for-os" option)
Checking if we have a supplied precompiled binary for your OS (Linux)... We do. We in fact have 3
Found 3 binaries: 
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64
Detecting versions of the cwebp binaries found
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64 -version 2>&1. Result: version: *1.0.3*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static -version 2>&1. Result: version: *1.0.3*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64 -version 2>&1. Result: version: *0.6.1*
Binaries ordered by version number.
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64: (version: 1.0.3)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static: (version: 1.0.3)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64: (version: 0.6.1)
Trying the first of these. If that should fail (it should not), the next will be tried and so on.
Creating command line options for version: 1.0.3
Quality: 82. 
Consider setting quality to "auto" instead. It is generally a better idea
The near-lossless option ignored for lossy
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64 -metadata none -q 82 -alpha_q '85' -m 6 -low_memory '[doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg.webp.lossy.webp' 2>&1 2>&1

*Output:* 
cannot open input file '[doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg'
Error! Could not process file [doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg
Error! Cannot read input picture file '[doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg'

Exec failed (return code: 255)
Creating command line options for version: 1.0.3
The near-lossless option ignored for lossy
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static -metadata none -q 82 -alpha_q '85' -m 6 -low_memory '[doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg.webp.lossy.webp' 2>&1 2>&1

*Output:* 
cannot open input file '[doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg'
Error! Could not process file [doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg
Error! Cannot read input picture file '[doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg'

Exec failed (return code: 255)
Creating command line options for version: 0.6.1
The near-lossless option ignored for lossy
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64 -metadata none -q 82 -alpha_q '85' -m 6 -low_memory '[doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg.webp.lossy.webp' 2>&1 2>&1

*Output:* 
cannot open input file '[doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg'
Error! Could not process file [doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg
Error! Cannot read input picture file '[doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Mae-80x80.jpg'

Exec failed (return code: 255)

**Error: ** **Failed converting. Check the conversion log for details.** 
Failed converting. Check the conversion log for details.
cwebp failed in 101 ms

*Trying: vips* 

**Error: ** **Required Vips extension is not available.** 
Required Vips extension is not available.
vips failed in 1 ms

*Trying: imagemagick* 

**Error: ** **imagemagick is not installed (cannot execute: "convert")** 
imagemagick is not installed (cannot execute: "convert")
imagemagick failed in 4 ms

*Trying: graphicsmagick* 

**Error: ** **gmagick is not installed** 
gmagick is not installed
graphicsmagick failed in 4 ms

*Trying: wpc* 

**Error: ** **Missing URL. You must install Webp Convert Cloud Service on a server, or the WebP Express plugin for Wordpress - and supply the url.** 
Missing URL. You must install Webp Convert Cloud Service on a server, or the WebP Express plugin for Wordpress - and supply the url.
wpc failed in 1 ms

*Trying: ewww* 

**Error: ** **Missing API key.** 
Missing API key.
ewww failed in 1 ms

*Trying: imagick* 

**Error: ** **iMagick was compiled without WebP support.** 
iMagick was compiled without WebP support.
imagick failed in 2 ms

*Trying: gmagick* 

**Error: ** **Required Gmagick extension is not available.** 
Required Gmagick extension is not available.
gmagick failed in 1 ms

*Trying: gd* 

Options:
------------
The following options have been set explicitly. Note: it is the resulting options after merging down the "jpeg" and "png" options and any converter-prefixed options.
- source: [doc-root]/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Maße-80x80.jpg
- destination: [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2020/03/e1628_Zahlen_und_Farmtiere_Holzpuzzle_Hape_Maße-80x80.jpg.webp
- log-call-arguments: true
- quality: 82

The following options have not been explicitly set, so using the following defaults:
- default-quality: 75
- max-quality: 85
- skip: false

The following options were supplied but are ignored because they are not supported by this converter:
- encoding
- metadata
- near-lossless
- skip-pngs
------------

GD Version: bundled (2.1.0 compatible)
image is true color
Quality: 82. 
Consider setting quality to "auto" instead. It is generally a better idea
gd succeeded :)

Converted image in 118 ms, reducing file size with 49% (went from 1716 bytes to 868 bytes)
