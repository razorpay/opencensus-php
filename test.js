#!/usr/bin/env node
const puppeteer = require('puppeteer');
const http = require('http');
const env = require('process').env;
const port = 8080,
  baseURL = `http://localhost:${port}/test`,
  merchantURL = `${baseURL}/merchant.html#/app`,
  merchantLog = console.log.bind(console.log, '[Merchant Log]:\n'),
  adminURL = `${baseURL}/admin.html#/admin`,
  adminLog = console.log.bind(console.log, '[Admin Log]:\n');

console.log('merchantURL: ' + merchantURL);
console.log('adminURL: ' + adminURL);

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
  page.on('pageerror', merchantLog).on('error', merchantLog);
  await page.goto(merchantURL);

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
  page.on('pageerror', adminLog).on('error', adminLog);
  await page.goto(adminURL);

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
