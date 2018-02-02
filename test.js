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

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();

  page.on('console', msg => console.log(msg.text()));

  const timeout = setTimeout(async () => {
    await page.evaluate(() => console.log('FAILED'));
    await browser.close();
    process.exit(1);
  }, 5000);

  page.waitForSelector('.layout.rzp').then(async ()=> {
    await page.evaluate(() => console.log('SUCCESS'));
    await browser.close();

    clearTimeout(timeout);
    process.exit(0);
  });

  await page.goto('http://localhost:8080/test/merchant.html#/app');
})();
