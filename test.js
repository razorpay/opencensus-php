#!/usr/bin/env node
const puppeteer = require('puppeteer');
const http = require('http');
const env = require('process').env;
const port = 8080;

const ecstatic = require('ecstatic')({
  root: `${__dirname}/public`,
  showDir: true,
  autoIndex: true,
});

http.createServer(ecstatic).listen(port);

console.log(`Listening on :${port}`);

async function merchant(browser) {
  let timeout = setTimeout(_ => {
    throw 'Merchant Test Timed out';
  }, 5000);
  const page = await browser.newPage();
  await page.goto(`http://localhost:${port}/test/merchant.html#/app`);

  page.on('error', console.log).on('error', console.log);

  return page.waitForSelector('.layout.rzp').then(_ => {
    clearTimeout(timeout);
    console.log('Merchant Successful');
  });
}

async function admin(browser) {
  let timeout = setTimeout(_ => {
    throw 'Admin Test Timed out';
  }, 5000);
  const page = await browser.newPage();
  await page.goto('http://localhost:${port}/test/admin.html#/admin');

  return page.waitForSelector('#app-container').then(_ => {
    clearTimeout(timeout);
    console.log('Admin Successful');
  });
}

let browser;
async function test() {
  browser = await puppeteer.launch({
    executablePath: env.CHROME_BIN || '/usr/bin/chromium',
    args: ['--no-sandbox', '--user-data-dir=/tmp'],
  });
  console.log('Browser testing started...');

  await Promise.all([merchant(browser), admin(browser)]);
}

test()
  .then(_ => {
    console.log('Puppeteer successful');
    process.exit();
  })
  .catch(er => {
    console.log(er);
    console.log('Puppeteer failed');
    process.exit(1);
  });
