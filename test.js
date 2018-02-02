#!/usr/bin/env node
const puppeteer = require('puppeteer');


'use strict';

const http = require('http');

const ecstatic = require('ecstatic')({
  root: `${__dirname}/public`,
  showDir: true,
  autoIndex: true,
});

http.createServer(ecstatic).listen(8080);

console.log('Listening on :8080');

async function merchant(browser) {
  const page = await browser.newPage();

  let err = false;
  const timeout = setTimeout(async _ => {
    err = 'MERCHANT FAILED...ERROR: timeout';
  }, 5000);

  await page.goto('http://localhost:8080/test/merchant.html#/app');

  if (err) {
    throw err;
  }

  return page.waitForSelector('.layout.rzp').then(async ()=> {
    clearTimeout(timeout);
    return 'SUCCESS...merchant';
  }).catch(e => `MERCHANT FAILED...ERROR: ${e}`);
}

async function admin(browser) {
  const page = await browser.newPage();

  let err = false;
  const timeout = setTimeout(async _ => {
    err = 'ADMIN FAILED...ERROR: timeout';
  }, 5000);

  await page.goto('http://localhost:8080/test/admin.html#/admin');

  if (err) {
    throw err;
  }

  return page.waitForSelector('#app-container').then(async ()=> {
    clearTimeout(timeout);
    return 'SUCCESS...admin';
  }).catch(e => `ADMIN FAILED...ERROR: ${e}`);
}

let browser;
async function test()   {
  browser = await puppeteer.launch();
  console.log('Browser testing started...');

  console.log(await merchant(browser));
  console.log(await admin(browser));

  return;
};



test().then(_=> {
  browser.close();
  console.log('Puppeteer successful');
  process.exit(0);
}).catch(er=>{
  browser.close();
  console.log(er);
  console.log('Puppeteer failed');
  process.exit(1);
});
