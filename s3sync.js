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
  ['public/dist/**/*', 'public/css/fonts/*', 'public/img/**/*'],
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
        ext.startsWith('woff') ||
        file.includes('remoteEntry.js')
      ) {
        fileParams.CacheControl = 'no-store,must-revalidate';
      }

      if (/\.[0-9a-f]+\.(js|css|svg|png|jpg|jpeg)$/.test(file)) {
        fileParams.CacheControl = 'max-age=31536000';
      }

      if (/\.[0-9a-f]+\.(woff|woff2)$/.test(file)) {
        fileParams.CacheControl = 'max-age=1296000';
      }

      const type = ContentType[ext];

      if (type) {
        fileParams.ContentType = type;

        // gzip compress for (js/css/html/svg) files
        const gzipFileParams = { ...fileParams };
        gzipFileParams.ContentEncoding = 'gzip';
        gzipFileParams.Key = `${fileParams.Key}.gz`;
        gzipFileParams.Body = zlib.gzipSync(fileParams.Body, {
          level: zlib.Z_BEST_COMPRESSION,
        });
        s3.putObject(gzipFileParams, (err, _data) => {
          if (err) {
            console.error(err);
            // eslint-disable-next-line no-process-exit
            process.exit(1);
          } else {
            console.log(gzipFileParams.Key);
          }
        });
      }

      // uncompressed version of all files
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
