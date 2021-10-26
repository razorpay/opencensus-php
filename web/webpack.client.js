/* eslint-disable no-param-reassign */
const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const devMode = process.env.STAGE !== 'production';
const isProd = process.env.STAGE !== 'development';
const HtmlWebpackPlugin = require('html-webpack-plugin');
// const projectConfigJs = require('./config')[process.env.STAGE];
const CopyWebpackPlugin = require('copy-webpack-plugin');

const fontsToProjectMap = {
  pokedex: 'merchant',
};

module.exports = ({ config, project }) => {
  config.entry = {
    [project]: `./js/${project}/index.js`,
  };

  if (isProd) {
    config.devtool = 'hidden-source-map';
  } else {
    config.devtool = 'source-map';
  }

  config.output = {
    path: path.resolve(__dirname, `../public/dist`),
    publicPath: `/dist/`,
    filename: isProd ? `js/${project}/[name].[chunkhash:8].js` : `js/${project}/[name].js`,
    sourceMapFilename: isProd
      ? `js/${project}/[name].[chunkhash:8].js.map`
      : `js/${project}/[name].js.map`,
    chunkFilename: isProd ? `js/${project}/[name].[chunkhash:8].js` : `js/${project}/[name].js`,
  };
  config.resolve.modules.push('js');
  config.resolve.extensions.push('.ts', '.tsx');
  config.resolve.alias = {
    v2: path.resolve(__dirname, './v2'),
    react: path.resolve(__dirname, './node_modules/react'),
  };
  config.module.rules[0].test = /(\.ts(x?)|\.m?js)$/; //babel loader to support typescript

  //should be removed once commnader and blade pulish their pacakge with babel
  config.module.rules[0].exclude = new RegExp(
    '/node_modules/(?!(@commander|@razorpay|@universe)/).*/',
  );

  //add limit to svg loader
  config.module.rules[2].use[0].options = {
    limit: 1024,
    outputPath: 'images',
    name: isProd ? '[name].[hash:8].[ext]' : '[name].[ext]',
  };

  //css
  config.module.rules.push(
    {
      test: /\.styl$/,
      use: [
        {
          loader: MiniCssExtractPlugin.loader,
          // options: {
          //   hmr: process.env.STAGE === 'development',
          // },
        },
        {
          loader: 'css-loader',
          options: {
            url: false,
          },
        },
        {
          loader: 'stylus-loader',
          options: {
            stylusOptions: {
              include: [path.join(__dirname, 'node_modules/bootstrap-styl')],
              resolveURL: false,
            },
          },
        },
      ],
    },
    {
      test: /\.font\.js/,
      use: [
        MiniCssExtractPlugin.loader,
        {
          loader: 'css-loader',
          options: {
            url: false,
          },
        },
        {
          loader: 'webfonts-loader',
          options: {
            publicPath: '../',
            classPrefix: 'i-',
            files: [`icons/${fontsToProjectMap[project] || project}/*.svg`],
            fontName: `${project}-icons`,
            fileName: 'css/[fontname].[ext]',
            htmlFileName: `css/[fontname].html`,
            html: true,
          },
        },
      ],
    },
    {
      test: /\.(graphql|gql)$/,
      exclude: /node_modules/,
      loader: 'graphql-tag/loader',
    },
  );

  //we are using react and others mentioned below as global variables in our codebase.
  config.plugins.push(
    new webpack.ProvidePlugin({
      React: 'react',
      moment: 'moment',
      Chart: 'chart',
      d3: 'd3',
      axios: 'axios',
      PropTypes: 'prop-types',
    }),
    new MiniCssExtractPlugin({
      filename: devMode ? 'css/[name].css' : 'css/[name].[contenthash].css',
      chunkFilename: devMode ? 'css/[id].css' : 'css/[id].[contenthash].css',
    }),
  );

  //remove unused CopyWebpackPlugin, LoadablePlugin, HtmlWebpackPlugin from default config
  config.plugins.splice(
    2,
    3,
    new CopyWebpackPlugin({
      patterns: [
        { from: './css/assets', to: './css/assets' },
        { from: './entry/raven-entry.js', to: './' },
      ],
    }),
    new HtmlWebpackPlugin({
      filename: `${project}-entry.js`,
      inject: false,
      cache: false,
      chunks: [project],
      version: JSON.stringify(process.env.VERSION),
      templateContent: ({ htmlWebpackPlugin }) => {
        return `(function(){
          ${
            process.env.REDIRECTOR === 'true'
              ? "window.cdnDashboardUrl = 'http://localhost:8000';"
              : ''
          }
          var websiteAssets = {
            js : ${JSON.stringify(htmlWebpackPlugin.files.js)},
            css : ${JSON.stringify(htmlWebpackPlugin.files.css)}
          };
          var __VERSION__ = ${htmlWebpackPlugin.options.version};
          ${require(`./entry/${project}-entry`)()}})()`;
      },
    }),
    new webpack.DefinePlugin({
      'process.env.PROJECT': JSON.stringify(project),
    }),
  );
  config.plugins.shift(); //removed cleanup plugin as outputpath is common for each build

  if (isProd) {
    config.plugins.splice(4, 1); //removing compress plugin as we have files othe than dist folder
  }

  return config;
};
