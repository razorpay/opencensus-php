var fs = require('fs');
var path = require('path');
var zlib = require('zlib');
var glob = require('multi-glob').glob;
var { execSync } = require('child_process');

execSync('mkdir -p public/s3/dist');
execSync('mkdir -p public/s3/js/generated');
execSync('mkdir -p public/s3/css/generated');
execSync('mkdir -p public/s3/css/fonts');

glob(
  [
    'public/dist/*',
    'public/js/generated/*',
    'public/css/generated/*',
    'public/css/fonts/*',
  ],
  compress
);

function compress(er, files) {
  files.forEach(function(f) {
    var outpath = f.replace(/^public/, 'public/s3');
    var ext = /[^\.]+$/.exec(f);
    ext = ext[0];

    if (ext === 'css' || ext === 'js') {
      var gzip = zlib.createGzip({
        level: zlib.Z_BEST_COMPRESSION,
      });

      var input = fs.createReadStream(f);
      var output = fs.createWriteStream(outpath);
      console.log('picking ' + outpath);
      output.on('finish', function() {
        console.log('zipped ' + outpath);
      });
      input.pipe(gzip).pipe(output);
    } else {
      execSync(`cp ${f} ${outpath}`);
    }
  });
}
