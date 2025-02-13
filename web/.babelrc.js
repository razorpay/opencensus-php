module.exports = {
  presets: ['@razorpay/universe-cli/babel.react.typescript.legacy'],
  plugins: [
    process.env.PROJECT === 'razorx' && ['@babel/plugin-proposal-decorators', { legacy: true }],
    ['@babel/plugin-proposal-private-methods', { loose: true }],
    [
      '@babel/plugin-proposal-class-properties',
      {
        loose: true,
      },
    ],
    [
      '@babel/plugin-transform-spread',
      {
        loose: true,
      },
    ],
    ['babel-plugin-graphql-tag'],
    'react-require',
  ].filter(Boolean),
};
