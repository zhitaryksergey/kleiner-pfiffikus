WebP Express 0.17.2. Conversion triggered using bulk conversion, 2019-12-22 21:28:28

*WebP Convert 2.3.0*  ignited.
- PHP version: 7.0.22-0ubuntu0.16.04.1
- Server software: Apache

Stack converter ignited

Options:
------------
The following options have been set explicitly. Note: it is the resulting options after merging down the "jpeg" and "png" options and any converter-prefixed options.
- source: [doc-root]/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg
- destination: [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg.webp
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
- source: [doc-root]/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg
- destination: [doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg.webp
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
- Executing: cwebp -version. Result: *Exec failed* (the cwebp binary was not found at path: cwebp)
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
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64 -version. Result: version: *1.0.3*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static -version. Result: version: *1.0.3*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64 -version. Result: version: *0.6.1*
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
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64 -metadata none -q 82 -alpha_q '85' -m 6 -low_memory '[doc-root]/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg.webp.lossy.webp' 2>&1

*Output:* 
Saving file '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg.webp.lossy.webp'
File:      [doc-root]/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg
Dimension: 600 x 600
Output:    32060 bytes Y-U-V-All-PSNR 40.46 44.22 45.66   41.50 dB
           (0.71 bpp)
block count:  intra4:        882  (61.08%)
              intra16:       562  (38.92%)
              skipped:        71  (4.92%)
bytes used:  header:            222  (0.7%)
             mode-partition:   3842  (12.0%)
 Residuals bytes  |segment 1|segment 2|segment 3|segment 4|  total
  intra4-coeffs:  |   15676 |     625 |     758 |     689 |   17748  (55.4%)
 intra16-coeffs:  |    2293 |     649 |     831 |     999 |    4772  (14.9%)
  chroma coeffs:  |    4279 |     262 |     402 |     505 |    5448  (17.0%)
    macroblocks:  |      49%|       9%|      14%|      28%|    1444
      quantizer:  |      23 |      17 |      13 |      10 |
   filter level:  |      10 |      12 |       6 |       7 |
------------------+---------+---------+---------+---------+-----------------
 segments total:  |   22248 |    1536 |    1991 |    2193 |   27968  (87.2%)

Success
Reduction: 86% (went from 223 kb to 31 kb)

Converting to lossless
Looking for cwebp binaries.
Discovering if a plain cwebp call works (to skip this step, disable the "try-cwebp" option)
- Executing: cwebp -version. Result: *Exec failed* (the cwebp binary was not found at path: cwebp)
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
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64 -version. Result: version: *1.0.3*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static -version. Result: version: *1.0.3*
- Executing: [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64 -version. Result: version: *0.6.1*
Binaries ordered by version number.
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64: (version: 1.0.3)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static: (version: 1.0.3)
- [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-061-linux-x86-64: (version: 0.6.1)
Trying the first of these. If that should fail (it should not), the next will be tried and so on.
Creating command line options for version: 1.0.3
Trying to convert by executing the following command:
nice [doc-root]/wp-content/plugins/webp-express/vendor/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64 -metadata none -q 82 -alpha_q '85' -near_lossless 60 -m 6 -low_memory '[doc-root]/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg' -o '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg.webp.lossless.webp' 2>&1

*Output:* 
Saving file '[doc-root]/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg.webp.lossless.webp'
File:      [doc-root]/wp-content/uploads/2017/06/w_rfelpuzzle_tierkinder_goki_ente.jpg
Dimension: 600 x 600
Output:    232970 bytes (5.18 bpp)
Lossless-ARGB compressed size: 232970 bytes
  * Header size: 3206 bytes, image data size: 229739
  * Lossless features used: PREDICTION CROSS-COLOR-TRANSFORM SUBTRACT-GREEN
  * Precision Bits: histogram=4 transform=4 cache=10

Success
Reduction: -2% (went from 223 kb to 228 kb)

Picking lossy
cwebp succeeded :)

Converted image in 1688 ms, reducing file size with 86% (went from 223 kb to 31 kb)
