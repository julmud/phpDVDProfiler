These are SVG files for the supported country flags in DVD Profiler.
The source for these files is the [country-flags project][country flags repo] and Wikimedia Commons for the two flags not found there (Quebec and Former Soviet Union).

Here're the commands I ran to generate PNG files from these SVG sources.
":17" actually generates images with an height of 17 pixels.

```bash
cd gfx/countries/svg
for file in *.svg; do svgexport $file "`basename $file svg`png" pad :17 ; done
imagemin *.png --out-dir=../
rm *.png
```

`svgexport` and `imagemin` are actually Node.js applications and can be installed via:

```bash
npm install -g svgexport imagemin
```

[country flags repo]: https://github.com/hjnilsson/country-flags
