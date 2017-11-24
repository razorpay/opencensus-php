#!/usr/bin/env node

const { readFileSync } = require('fs');
const path = require('path');
const glob = require('glob');
const zlib = require('zlib');
const AWS = require('./aws');
const s3 = new AWS.S3();
const ENV = process.env;
const AWS_DIR = ENV.AWS_DIR || ENV.TARGET_DIR;

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

// to remove it from begining of file path before uploading to s3
const prefixLen = AWS_DIR.length + 1;

glob(AWS_DIR + '/**', { nodir: true }, (error, files) => {
  files.forEach(file => {
    var fileParams = {
      ...params,
      Key: file.slice(prefixLen),
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
});
