var fs = require('fs');
var path = require('path');
var s3 = require('s3');
var aws = require('aws-sdk');
var glob = require('glob');
var ENV = process.env;

var s3sdk = new aws.S3({
  signatureVersion: 'v2',
  accessKeyId: ENV.AWS_KEY,
  secretAccessKey: ENV.AWS_SECRET,
  region: ENV.AWS_REGION,
});

var client = s3.createClient({
  s3Client: s3sdk,
  maxAsyncS3: 20, // this is the default
  s3RetryCount: 3, // this is the default
  s3RetryDelay: 1000, // this is the default
  multipartUploadThreshold: 20971520, // this is the default (20 MB)
  multipartUploadSize: 15728640, // this is the default (15 MB)
  s3Options: {
    accessKeyId: ENV.AWS_KEY,
    secretAccessKey: ENV.AWS_SECRET,
    region: ENV.AWS_REGION,
    // any other options are passed to new AWS.S3()
    // See: http://docs.aws.amazon.com/AWSJavaScriptSDK/latest/AWS/Config.html#constructor-property
  },
});

var params = {
  // deleteRemoved: true, // default false, whether to remove s3 objects that have no corresponding local file.
  s3Params: {
    Bucket: ENV.AWS_CDN_BUCKET,
    ACL: 'public-read',
    CacheControl: 'max-age=31536000, public',
    // other options supported by putObject, except Body and ContentLength.
    // See: http://docs.aws.amazon.com/AWSJavaScriptSDK/latest/AWS/S3.html#putObject-property
  },
};

const prefixLen = ENV.TARGET_DIR.length + 1;

glob(ENV.TARGET_DIR + '/**', {}, function(error, files) {
  files.forEach(function(f) {
    if (fs.lstatSync(f).isFile()) {
      var ext = /[^\.]+$/.exec(f);
      ext = ext[0];
      if (ext && ext !== 'map') {
        // ignore mapfiles
        var fileParams = JSON.parse(JSON.stringify(params));

        if (f.endsWith('-entry.js') || f.endsWith('.js.map')) {
          fileParams.s3Params.CacheControl = 'no-store, must-revalidate';
        }

        fileParams.localFile = f;
        if (ext == 'css' || ext == 'js' || ext === 'html') {
          fileParams.s3Params.ContentEncoding = 'gzip';
        }
        fileParams.s3Params.Key = f;
        console.log(fileParams);
        var uploader = client.uploadFile(fileParams);
        uploader.on('error', function(err) {
          console.error('unable to sync:', err.stack);
          throw err;
        });
        uploader.on('end', function(data) {
          console.log(f);
        });
      }
    }
  });
});
