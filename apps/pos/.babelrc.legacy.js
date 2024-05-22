// This file is responsible for CJS builds for legacy browsers.
// Modify it when you want to update the build for legacy browsers.
module.exports = {
  presets: ['@razorpay/universe-cli/babel.react.typescript.legacy'], // use this for CSR TS app
  plugins: [
    ['@babel/plugin-proposal-decorators', { legacy: true }], // needed for @connect decorator in @dashboard/shared-ui/Clipboard/Custom
    '@babel/plugin-proposal-function-bind', // needed for :: bind operators in @dashboard/shared-ui/Clipboard/Custom
  ], // add any additional plugins here if required or if present in the .babelrc.js config
};
