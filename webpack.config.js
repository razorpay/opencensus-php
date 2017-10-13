const webpack = require('webpack');
const isProd = require('process').env.NODE_ENV === 'production';
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

// generated bladefiles
const htmlPlugins = require('./web/webpack/html')(
  {
    template: 'admin.jst',
    filename: 'adminIndex',
  },
  {
    // template locals
    cdnUrl: isProd ? 'https://cdn.razorpay.com/dashboard' : '/dist',
    filename: (_, name) =>
      _.webpackConfig.output.filename.replace('[name]', name),
  }
);

let plugins = [htmlPlugins];

if (isProd) {
  plugins = plugins.concat(
    new webpack.LoaderOptionsPlugin({
      minimize: true,
      debug: false,
    }),
    new UglifyJSPlugin({
      uglifyOptions: {
        beautify: false,
        ecma: 6,
        compress: true,
        comments: false,
      },
    })
  );
}

module.exports = {
  externals: [].reduce.call(
    (process.env.externals || '').split(' '),
    (prev, next, index, arr) => {
      if (index % 2) {
        prev[next.split('/')[0]] = arr[index - 1];
      }
      return prev;
    },
    {}
  ),

  entry: {
    admin: './web/admin.js',
  },

  output: {
    path: __dirname + '/public/dist/admin',
    filename: '[name].js',
  },

  resolve: {
    modules: ['node_modules', 'web/js'],
  },

  resolveLoader: {
    alias: {
      'dot-loader': __dirname + '/web/webpack/dot-loader.js',
    },
  },

  stats: {
    assets: false,
    children: false,
    version: false,
    hash: false,
    timings: false,
    chunks: false,
    chunkModules: false,
  },

  plugins,

  module: {
    rules: [
      {
        test: /\.jst$/,
        loader: 'dot-loader',
      },
      {
        test: /\.js$/,
        exclude: /^node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['env', 'react', 'stage-0'],
            plugins: ['transform-decorators-legacy', 'react-html-attrs'],
          },
        },
      },
    ],
  },
};
