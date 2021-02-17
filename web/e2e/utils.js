const fs = require('fs');
const HTTPServer = require('http-server');
const constants = require('./const');
const merchantEntryJS = fs.readFileSync('../public/dist/merchant-entry.js', 'utf8');
const blockedIPs = constants.blockedIPs;

function generateRandomChars(length) {
  let result = '';
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  const charactersLength = characters.length;
  for (let i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }
  return result;
}

function startServer() {
  const htttpServer = HTTPServer.createServer({
    root: `${__dirname}/../../public`,
    cors: true,
  });

  const HOSTNAME = 'localhost';
  const PORT = constants.STATIC_PORT;

  htttpServer.listen(PORT, HOSTNAME, () => {
    console.log(`Static server running at http://${HOSTNAME}:${PORT}/`);
  });

  return htttpServer;
}

function generateRandomNumber(length) {
  if (length <= 0) {
    throw new Error('invalid argument passed');
  }

  return Math.random()
    .toFixed(length - 2)
    .toString();
}

function createFakeEmail() {
  const randomPrefix = generateRandomChars(5);
  const randomPostfix = generateRandomNumber(5);
  return `test_e2e_${randomPrefix}_${randomPostfix}@rzp.com`;
}

function delay(time) {
  return new Promise((resolve) => {
    setTimeout(resolve, time);
  });
}

function generateNewCreds() {
  return {
    email: createFakeEmail(),
    password: generateRandomNumber(8),
  };
}

function interceptor(route) {
  const url = route.request().url();
  const origin = route.request().headers() && route.request().headers().origin;
  if (url.match(constants.merchantEntryPattern)) {
    route.fulfill({
      status: 200,
      contentType: 'application/javascript',
      body: merchantEntryJS,
    });
  } else if (origin && blockedIPs.indexOf(origin) !== -1) {
    route.abort();
  } else {
    route.continue();
  }
}

function pageErrorHandler() {
  // console.log('Page Error Message:', error.message);
  // console.log('Page Error Stack:', error.stack);
}

module.exports = {
  createFakeEmail,
  delay,
  generateNewCreds,
  interceptor,
  pageErrorHandler,
  startServer,
};
