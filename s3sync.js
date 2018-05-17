#!/usr/bin/env node

const { readFileSync } = require('fs');
const path = require('path');
const glob = require('multi-glob').glob;
const zlib = require('zlib');

const ENV = process.env;
const AWS = require('aws-sdk');

AWS.config.update({
  accessKeyId: ENV.AWS_KEY || ENV.AWS_ACCESS_KEY,
  secretAccessKey: ENV.AWS_SECRET || ENV.AWS_ACCESS_SECRET,
  region: ENV.AWS_REGION,
});

const s3 = new AWS.S3();

// http://docs.aws.amazon.com/AWSJavaScriptSDK/latest/AWS/S3.html
var params = {
  Bucket: ENV.AWS_CDN_BUCKET || ENV.AWS_BUCKET || ENV.AWS_S3_BUCKET,
  ACL: 'public-read',
  CacheControl: 'max-age=2700, must-revalidate',
};

// textual file types
const ContentType = {
  js: 'application/javascript',
  html: 'text/html',
  css: 'text/css',
  svg: 'image/svg+xml',
};

glob(
  [
    'public/dist/**/*',
    'public/js/generated/*',
    'public/css/generated/*',
    'public/css/fonts/*',
  ],
  { nodir: true },
  (error, files) => {
    files.forEach(file => {
      var fileParams = {
        Bucket: params.Bucket,
        ACL: params.ACL,
        CacheControl: params.CacheControl,
        Key: file.replace(/^public/, 'dashboard'),
        Body: readFileSync(file),
      };
      var ext = path.extname(file).slice(1);

      if (
        file.endsWith('-entry.js') ||
        file.endsWith('.js.map') ||
        ext.startsWith('woff')
      ) {
        fileParams.CacheControl = 'no-store,must-revalidate';
      }

      var type = ContentType[ext];
      if (type) {
        fileParams.ContentType = type;
        fileParams.ContentEncoding = 'gzip';
        fileParams.Body = zlib.gzipSync(fileParams.Body, {
          level: zlib.Z_BEST_COMPRESSION,
        });
      }

      s3.putObject(fileParams, err => console.log(err || fileParams.Key));
    });
  }
);
