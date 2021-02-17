// TODO: Make headless false when running in dev mode

module.exports = {
  launchOptions: {
    headless: true, // Turn the visualization off
    slowMo: 300, // Keeps this much delay in consequent events
    // devtools: false, // Launch browser with developer tools on - works only for chromium
    exitOnPageError: false, // Ignores the page errors
  },
  contextOptions: {
    ignoreHTTPSErrors: true,
    // screen resolution
    viewport: {
      width: 1024,
      height: 786,
    },
  },
  browsers: ['chromium'],
  devices: [],
};
