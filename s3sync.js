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
const params = {
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
    'public/img/**/*',
  ],
  { nodir: true },
  (error, files) => {
    files.forEach((file) => {
      const fileParams = {
        Bucket: params.Bucket,
        ACL: params.ACL,
        CacheControl: params.CacheControl,
        Key: file.replace(/^public/, ENV.CDN_PATH || 'dashboard'),
        Body: readFileSync(file),
      };

      // Debug filepath while deploying
      // TODO: remove console log before merging to master
      console.log(fileParams.Key);

      const ext = path.extname(file).slice(1);

      if (
        file.endsWith('-entry.js') ||
        file.endsWith('.js.map') ||
        file.endsWith('signup.css') ||
        file.endsWith('signup.js') ||
        ext.startsWith('woff')
      ) {
        fileParams.CacheControl = 'no-store,must-revalidate';
      }

      if (/\.[0-9a-f]+\.(js|css)$/.test(file)) {
        fileParams.CacheControl = 'max-age=31536000';
      }

      const type = ContentType[ext];

      // do brotli compression for dummy TestComponentBrotli only
      if (type && fileParams.Key?.includes('TestComponentBrotli')) {
        const brotilFileParams = { ...fileParams };
        brotilFileParams.ContentType = type;
        brotilFileParams.ContentEncoding = 'br';
        brotilFileParams.Key = `${brotilFileParams.Key}.br`;
        brotilFileParams.Body = zlib.brotliCompressSync(brotilFileParams.Body, {
          params: {
            [zlib.constants.BROTLI_PARAM_QUALITY]: zlib.constants.BROTLI_MAX_QUALITY,
          },
        });
        s3.putObject(brotilFileParams, (err, _data) => {
          if (err) {
            console.error(err);
            // eslint-disable-next-line no-process-exit
            process.exit(1);
          } else {
            console.log(brotilFileParams.Key);
          }
        });
      }

      if (type) {
        fileParams.ContentType = type;
        fileParams.ContentEncoding = 'gzip';
        fileParams.Body = zlib.gzipSync(fileParams.Body, {
          level: zlib.Z_BEST_COMPRESSION,
        });
      }

      s3.putObject(fileParams, (err, _data) => {
        if (err) {
          console.error(err);
          // eslint-disable-next-line no-process-exit
          process.exit(1);
        } else {
          console.log(fileParams.Key);
        }
      });
    });
  },
);
