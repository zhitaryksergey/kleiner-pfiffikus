WebP Express 0.19.0. Conversion triggered with the conversion script (wod/webp-on-demand.php), 2021-02-06 13:28:16

*WebP Convert 2.3.2*  ignited.
- PHP version: 7.3.26
- Server software: Apache

Stack converter ignited

Options:
------------
The following options have been set explicitly. Note: it is the resulting options after merging down the "jpeg" and "png" options and any converter-prefixed options.
- source: [doc-root]/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg
- destination: [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp
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
- source: [doc-root]/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg
- destination: [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp
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
- Executing: cwebp -version 2>&1. Result: *Exec failed* (the cwebp binary was not found at path: cwebp, or it had missing library dependencies)
Nope a plain cwebp call does not work
Discovering binaries using "which -a cwebp" command. (to skip this step, disable the "try-discovering-cwebp" option)
Found 0 binaries
Discovering binaries by peeking in common system paths (to skip this step, disable the "try-common-system-paths" option)
Found 0 binaries
Discovering binaries which are distributed with the webp-convert library (to skip this step, disable the "try-supplied-binary-for-os" option)
Checking if we have a supplied precompiled binary for your OS (Linux)... We do. We in fact have 3
Found 3 binaries: 
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64
Detecting versions of the cwebp binaries found
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64 -version 2>&1. Result: version: *1.1.0*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static -version 2>&1. Result: version: *1.0.3*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64 -version 2>&1. Result: version: *0.6.1*
Binaries ordered by version number.
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64: (version: 1.1.0)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static: (version: 1.0.3)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64: (version: 0.6.1)
Trying the first of these. If that should fail (it should not), the next will be tried and so on.
Creating command line options for version: 1.1.0
Quality: 82. 
Consider setting quality to "auto" instead. It is generally a better idea
The near-lossless option ignored for lossy
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64 -metadata none -q 82 -alpha_q '85' -m 6 -low_memory '[doc-root]/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp.lossy.webp' 2>&1 2>&1

*Output:* 
Saving file '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp.lossy.webp'
File:      [doc-root]/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg
Dimension: 100 x 100
Output:    2404 bytes Y-U-V-All-PSNR 41.70 42.24 41.66   41.78 dB
           (1.92 bpp)
block count:  intra4:         26  (53.06%)
              intra16:        23  (46.94%)
              skipped:        14  (28.57%)
bytes used:  header:            147  (6.1%)
             mode-partition:    171  (7.1%)
 Residuals bytes  |segment 1|segment 2|segment 3|segment 4|  total
  intra4-coeffs:  |    1549 |       0 |       0 |       0 |    1549  (64.4%)
 intra16-coeffs:  |       5 |       0 |       0 |       1 |       6  (0.2%)
  chroma coeffs:  |     496 |       0 |       0 |       4 |     500  (20.8%)
    macroblocks:  |      73%|       2%|       4%|      20%|      49
      quantizer:  |      21 |      14 |      10 |      10 |
   filter level:  |       7 |       3 |       2 |       0 |
------------------+---------+---------+---------+---------+-----------------
 segments total:  |    2050 |       0 |       0 |       5 |    2055  (85.5%)

Success
Reduction: 33% (went from 3571 bytes to 2404 bytes)

Converting to lossless
Looking for cwebp binaries.
Discovering if a plain cwebp call works (to skip this step, disable the "try-cwebp" option)
- Executing: cwebp -version 2>&1. Result: *Exec failed* (the cwebp binary was not found at path: cwebp, or it had missing library dependencies)
Nope a plain cwebp call does not work
Discovering binaries using "which -a cwebp" command. (to skip this step, disable the "try-discovering-cwebp" option)
Found 0 binaries
Discovering binaries by peeking in common system paths (to skip this step, disable the "try-common-system-paths" option)
Found 0 binaries
Discovering binaries which are distributed with the webp-convert library (to skip this step, disable the "try-supplied-binary-for-os" option)
Checking if we have a supplied precompiled binary for your OS (Linux)... We do. We in fact have 3
Found 3 binaries: 
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64
Detecting versions of the cwebp binaries found
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64 -version 2>&1. Result: version: *1.1.0*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static -version 2>&1. Result: version: *1.0.3*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64 -version 2>&1. Result: version: *0.6.1*
Binaries ordered by version number.
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64: (version: 1.1.0)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static: (version: 1.0.3)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64: (version: 0.6.1)
Trying the first of these. If that should fail (it should not), the next will be tried and so on.
Creating command line options for version: 1.1.0
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64 -metadata none -q 82 -alpha_q '85' -near_lossless 60 -m 6 -low_memory '[doc-root]/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp.lossless.webp' 2>&1 2>&1

*Output:* 
Saving file '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp.lossless.webp'
File:      [doc-root]/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg
Dimension: 100 x 100
Output:    7908 bytes (6.33 bpp)
Lossless-ARGB compressed size: 7908 bytes
  * Header size: 753 bytes, image data size: 7130
  * Lossless features used: PREDICTION CROSS-COLOR-TRANSFORM SUBTRACT-GREEN
  * Precision Bits: histogram=2 transform=2 cache=0

Success
Reduction: -121% (went from 3571 bytes to 7908 bytes)


*Warning: filesize(): stat failed for [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp.lossy.webp in [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/ConverterTraits/EncodingAutoTrait.php, line 70, PHP 7.3.26 (Linux)* 


*Warning: filesize(): stat failed for [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp.lossy.webp in [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/ConverterTraits/EncodingAutoTrait.php, line 70, PHP 7.3.26 (Linux)* 

Picking lossy

*Warning: rename([doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp.lossy.webp,[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp): No such file or directory in [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/ConverterTraits/EncodingAutoTrait.php, line 73, PHP 7.3.26 (Linux)* 


*Warning: rename([doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp.lossy.webp,[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/02/58737_Fahrzeuge_Baukasten_im_Koffer_4-100x100.jpg.webp): No such file or directory in [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/ConverterTraits/EncodingAutoTrait.php, line 73, PHP 7.3.26 (Linux)* 

cwebp succeeded :)

Converted image in 227 ms, reducing file size with 33% (went from 3571 bytes to 2404 bytes)
