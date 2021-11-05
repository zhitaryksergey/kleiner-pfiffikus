WebP Express 0.20.1. Conversion triggered using bulk conversion, 2021-11-05 09:53:11

*WebP Convert 2.6.0*  ignited.
- PHP version: 7.3.27
- Server software: Apache

Stack converter ignited

Options:
------------
The following options have been set explicitly. Note: it is the resulting options after merging down the "jpeg" and "png" options and any converter-prefixed options.
- source: [doc-root]/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png
- destination: [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png.webp
- log-call-arguments: true
- converters: (array of 10 items)

The following options have not been explicitly set, so using the following defaults:
- auto-limit: true
- converter-options: (empty array)
- shuffle: false
- preferred-converters: (empty array)
- extra-converters: (empty array)

The following options were supplied and are passed on to the converters in the stack:
- alpha-quality: 85
- encoding: "auto"
- metadata: "none"
- near-lossless: 60
- quality: 85
------------


*Trying: cwebp* 

Options:
------------
The following options have been set explicitly. Note: it is the resulting options after merging down the "jpeg" and "png" options and any converter-prefixed options.
- source: [doc-root]/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png
- destination: [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png.webp
- alpha-quality: 85
- encoding: "auto"
- low-memory: true
- log-call-arguments: true
- metadata: "none"
- method: 6
- near-lossless: 60
- quality: 85
- use-nice: true
- try-common-system-paths: true
- try-supplied-binary-for-os: true
- command-line-options: ""

The following options have not been explicitly set, so using the following defaults:
- auto-limit: true
- auto-filter: false
- default-quality: 85
- max-quality: 85
- preset: "none"
- size-in-percentage: null (not set)
- sharp-yuv: true
- skip: false
- try-cwebp: true
- try-discovering-cwebp: true
- rel-path-to-precompiled-binaries: *****
- skip-these-precompiled-binaries: ""
------------

Encoding is set to auto - converting to both lossless and lossy and selecting the smallest file

Converting to lossy
Looking for cwebp binaries.
Discovering if a plain cwebp call works (to skip this step, disable the "try-cwebp" option)
- Executing: cwebp -version 2>&1. Result: *Exec failed* (the cwebp binary was not found at path: cwebp, or it had missing library dependencies)
Nope a plain cwebp call does not work (spent 3 ms)
Discovering binaries using "which -a cwebp" command. (to skip this step, disable the "try-discovering-cwebp" option)
Found 0 binaries (spent 10 ms)
Discovering binaries by peeking in common system paths (to skip this step, disable the "try-common-system-paths" option)
Found 0 binaries (spent 20 ms)
Discovering binaries which are distributed with the webp-convert library (to skip this step, disable the "try-supplied-binary-for-os" option)
Checking if we have a supplied precompiled binary for your OS (Linux)... We do. We in fact have 4
Found 4 binaries (spent 0 ms)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64
Discovering cwebp binaries took: 34 ms

Binaries ordered by version number.
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64: (version: 1.2.0)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64: (version: 1.1.0)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static: (version: 1.0.3)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64: (version: 0.6.1)
Starting conversion, using the first of these. If that should fail, the next will be tried and so on.
Checking checksum for supplied binary: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64
Checksum test took: 20 ms
Creating command line options for version: 1.2.0
Bypassing auto-limit (it is only active for jpegs)
Quality: 85. 
The near-lossless option ignored for lossy
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64 -metadata none -q 85 -alpha_q '85' -sharp_yuv -m 6 -low_memory '[doc-root]/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png.webp.lossy.webp' 2>&1

*Output:* 
[doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64: /lib/x86_64-linux-gnu/libm.so.6: version `GLIBC_2.29' not found (required by [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64)

Executing cwebp binary took: 4 ms

Exec failed (return code: 1)
Note: You can prevent trying this precompiled binary, by setting the "skip-these-precompiled-binaries" option to "cwebp-120-linux-x86-64"
Checking checksum for supplied binary: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64
Checksum test took: 19 ms
Creating command line options for version: 1.1.0
The near-lossless option ignored for lossy
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64 -metadata none -q 85 -alpha_q '85' -sharp_yuv -m 6 -low_memory '[doc-root]/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png.webp.lossy.webp' 2>&1

*Output:* 
Saving file '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png.webp.lossy.webp'
File:      [doc-root]/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png
Dimension: 150 x 150 (with alpha)
Output:    6992 bytes Y-U-V-All-PSNR 43.13 40.46 40.43   42.04 dB
           (2.49 bpp)
block count:  intra4:         72  (72.00%)
              intra16:        28  (28.00%)
              skipped:        21  (21.00%)
bytes used:  header:            211  (3.0%)
             mode-partition:    412  (5.9%)
             transparency:     1507 (68.6 dB)
 Residuals bytes  |segment 1|segment 2|segment 3|segment 4|  total
  intra4-coeffs:  |    2818 |       0 |       0 |       0 |    2818  (40.3%)
 intra16-coeffs:  |      38 |       0 |       0 |       0 |      38  (0.5%)
  chroma coeffs:  |    1946 |       0 |       0 |       0 |    1946  (27.8%)
    macroblocks:  |      93%|       0%|       0%|       7%|     100
      quantizer:  |      15 |      10 |       8 |       8 |
   filter level:  |      63 |       2 |       2 |       0 |
------------------+---------+---------+---------+---------+-----------------
 segments total:  |    4802 |       0 |       0 |       0 |    4802  (68.7%)
Lossless-alpha compressed size: 1506 bytes
  * Header size: 77 bytes, image data size: 1429
  * Precision Bits: histogram=3 transform=3 cache=0
  * Palette size:   136

Executing cwebp binary took: 26 ms

Success
Reduction: 79% (went from 33 kb to 7 kb)

Converting to lossless
Looking for cwebp binaries.
Discovering if a plain cwebp call works (to skip this step, disable the "try-cwebp" option)
- Executing: cwebp -version 2>&1. Result: *Exec failed* (the cwebp binary was not found at path: cwebp, or it had missing library dependencies)
Nope a plain cwebp call does not work (spent 3 ms)
Discovering binaries using "which -a cwebp" command. (to skip this step, disable the "try-discovering-cwebp" option)
Found 0 binaries (spent 9 ms)
Discovering binaries by peeking in common system paths (to skip this step, disable the "try-common-system-paths" option)
Found 0 binaries (spent 20 ms)
Discovering binaries which are distributed with the webp-convert library (to skip this step, disable the "try-supplied-binary-for-os" option)
Checking if we have a supplied precompiled binary for your OS (Linux)... We do. We in fact have 4
Found 4 binaries (spent 0 ms)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64
Discovering cwebp binaries took: 32 ms

Binaries ordered by version number.
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64: (version: 1.2.0)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64: (version: 1.1.0)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static: (version: 1.0.3)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64: (version: 0.6.1)
Starting conversion, using the first of these. If that should fail, the next will be tried and so on.
Checking checksum for supplied binary: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64
Checksum test took: 21 ms
Creating command line options for version: 1.2.0
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64 -metadata none -q 85 -alpha_q '85' -near_lossless 60 -sharp_yuv -m 6 -low_memory '[doc-root]/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png.webp.lossless.webp' 2>&1

*Output:* 
[doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64: /lib/x86_64-linux-gnu/libm.so.6: version `GLIBC_2.29' not found (required by [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-120-linux-x86-64)

Executing cwebp binary took: 4 ms

Exec failed (return code: 1)
Note: You can prevent trying this precompiled binary, by setting the "skip-these-precompiled-binaries" option to "cwebp-120-linux-x86-64"
Checking checksum for supplied binary: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64
Checksum test took: 20 ms
Creating command line options for version: 1.1.0
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-110-linux-x86-64 -metadata none -q 85 -alpha_q '85' -near_lossless 60 -sharp_yuv -m 6 -low_memory '[doc-root]/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png.webp.lossless.webp' 2>&1

*Output:* 
Saving file '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png.webp.lossless.webp'
File:      [doc-root]/wp-content/uploads/2021/11/61067_selecta_carusello_motorikbrett_4060848610676_-150x150.png
Dimension: 150 x 150
Output:    18136 bytes (6.45 bpp)
Lossless-ARGB compressed size: 18136 bytes
  * Header size: 1573 bytes, image data size: 16537
  * Lossless features used: PREDICTION CROSS-COLOR-TRANSFORM SUBTRACT-GREEN
  * Precision Bits: histogram=2 transform=2 cache=9

Executing cwebp binary took: 116 ms

Success
Reduction: 46% (went from 33 kb to 18 kb)

Picking lossy
cwebp succeeded :)

Converted image in 305 ms, reducing file size with 79% (went from 33 kb to 7 kb)
