module.exports = {
  presets: ['@razorpay/universe-cli/babel.react.typescript'],
  plugins: [
    ['@babel/plugin-proposal-decorators', { legacy: true }], // needed for @connect decorator in @dashboard/shared-ui/Clipboard/Custom
    ['babel-plugin-graphql-tag'],
  ],
};
