const path = require('path');
const webpack = require('webpack');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const WorkbboxWebpackPlugin = require('workbox-webpack-plugin');
// const ImageminWebpWebpackPlugin = require('imagemin-webp-webpack-plugin');
const NodePolyfillPlugin = require('node-polyfill-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const ReactRefreshWebpackPlugin = require('@pmmmwh/react-refresh-webpack-plugin');

const isProd = process.env.STAGE !== 'development';
const project = process.env.PROJECT;

const fontsToProjectMap = {
  pokedex: 'merchant',
};
const PROJECTS_USING_WORKBOX = ['merchant', 'merchantLA'];
const PLUGINS_TO_BE_REMOVED = ['CompressionPlugin', 'LoadablePlugin'];
const PRELOAD_ASSETS_FOR = ['merchant', 'merchantLA'];
const RZP_CDN_URL = 'https://cdn.razorpay.com/dashboard';

const isRedirector = process.env.REDIRECTOR === 'true';
const publicPath = '/public/dist/';
const rootPublicFolderPath = path.resolve(__dirname, `../public/dist`);

module.exports = {
  browserConfig: ({ config, isStoryBook = false }) => {
    // *** config.entry *** //
    config.entry = {
      [project]: `./js/${project}/index.js`, // merchant to be replaced by [project]
    };

    if (isProd || isStoryBook) {
      config.cache = false;
      config.output.pathinfo = false;
    } else {
      config.cache.cacheDirectory = path.resolve(
        __dirname,
        `node_modules/.cache/webpack/${project}`,
      );
      config.devServer.devMiddleware = {
        writeToDisk: !isRedirector,
        publicPath: isRedirector ? publicPath : rootPublicFolderPath,
      };

      if (isRedirector) {
        config.devServer.historyApiFallback = {
          rewrites: [
            {
              from: /^\/offline.html/,
              to: `${publicPath}/offline.html`,
            },
            {
              from: /./,
              to: publicPath,
            },
          ],
        };

        config.devServer.allowedHosts = 'all';
        config.devServer.https = true;
        config.devServer.client.webSocketURL.hostname = 'localhost';
        config.devServer.client.webSocketURL.port = '8080';
      }
    }

    config.experiments.backCompat = false;
    config.parallelism = 500;

    // *** config.output *** //
    config.output = {
      ...config.output,
      path: isRedirector ? path.resolve(__dirname, `./public/dist`) : rootPublicFolderPath,
      publicPath: isRedirector ? publicPath : '/dist/',
      filename: isProd ? `js/${project}/[name].[chunkhash:8].js` : `js/${project}/[name].js`,
      chunkFilename: isProd ? `js/${project}/[name].[chunkhash:8].js` : `js/${project}/[name].js`,
      hashFunction: 'xxhash64',
    };

    // *** config.resolve *** //
    config.resolve.modules.push(path.resolve(__dirname, 'js'));
    config.resolve.alias = {
      v2: path.resolve(__dirname, './v2'),
      react: path.resolve(__dirname, './node_modules/react'),
      assets: path.resolve(__dirname, './css/assets'),
    };

    // *** config.module *** //
    config.module.rules[0].exclude = new RegExp(
      '/node_modules/(?!(@commander|@razorpay|@universe)/).*/',
    );
    // from native config of dashboard
    config.module.rules.push(
      {
        test: /\.styl$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
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
              fileName: isProd ? 'css/[fontname].[hash].[ext]' : 'css/[fontname].[ext]',
              htmlDest: `css/[fontname].html`,
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

    if (isRedirector) {
      config.module.rules.push({
        test: /\.[jt]sx?$/,
        exclude: /node_modules/,
        use: [
          {
            loader: require.resolve('babel-loader'),
            options: {
              plugins: [require.resolve('react-refresh/babel')].filter(Boolean),
            },
          },
        ],
      });
      config.plugins.push(
        new ReactRefreshWebpackPlugin({
          overlay: false,
        }),
      );
    }

    // *** config.plugins *** //
    if (project === 'merchant') {
      // run this only once for merchant as it is common folder
      config.plugins.push(
        new CopyWebpackPlugin({
          patterns: [
            {
              from: './css/assets',
              to: './css/assets',
            },
          ],
        }),
      );
    }

    // adds new plugins as per dashboard
    config.plugins.push(
      new HtmlWebpackPlugin({
        filename: `${project}-entry.js`,
        inject: false,
        cache: false,
        chunks: [project],
        version: JSON.stringify(process.env.VERSION),
        templateContent: ({ htmlWebpackPlugin }) => {
          return `(function(){
            ${isRedirector ? "window.cdnDashboardUrl = 'https://localhost:8080';" : ''}
            var websiteAssets = {
              js : ${JSON.stringify(htmlWebpackPlugin.files.js)},
              css : ${JSON.stringify(htmlWebpackPlugin.files.css)}
            };
            window.__VERSION__ = ${htmlWebpackPlugin.options.version};
            ${require(`./entry/${project}-entry`)()}})()`;
        },
      }),
      new webpack.ProvidePlugin({
        React: 'react',
        moment: 'moment',
        Chart: 'chart',
        axios: 'axios',
        PropTypes: 'prop-types',
      }),
      new MiniCssExtractPlugin({
        filename: !isProd ? 'css/[name].css' : 'css/[name].[contenthash].css',
        chunkFilename: !isProd ? 'css/[id].css' : 'css/[id].[contenthash].css',
      }),
      new webpack.IgnorePlugin({
        resourceRegExp: /^\.\/locale$/,
        contextRegExp: /moment$/,
      }),
      new webpack.DefinePlugin({
        'process.env.PROJECT': JSON.stringify(project),
        'process.env.PUBLIC_ENV': JSON.stringify(process.env.STAGE),
        'process.env.REDIRECTOR': JSON.stringify(isRedirector),
      }),
      new NodePolyfillPlugin({}),
    );

    if (PROJECTS_USING_WORKBOX.indexOf(project) > -1) {
      config.plugins.push(
        new WorkbboxWebpackPlugin.InjectManifest({
          modifyURLPrefix: {
            '/dist/': `${RZP_CDN_URL}/dist/`,
          },
          include: [/\.(js|css)?$/, /\.(woff|woff2)?$/],
          exclude: [/(merchant-entry|merchantLA-entry).js$/],
          swSrc: './sw/workbox.js',
          swDest: `sw-utils/sw-${project}.js`,
        }),
      );
    }

    // TODO: need to be added back once optimisation is fixed
    if (project === 'merchant') {
      // config.plugins.push(
      //   new ImageminWebpWebpackPlugin({
      //     config: [
      //       {
      //         test: /\.(jpe?g|png)/,
      //         options: {
      //           quality: 75,
      //         },
      //       },
      //     ],
      //     strict: isProd,
      //   })
      // );
    }

    if (PRELOAD_ASSETS_FOR.includes(project) && isProd) {
      config.plugins.push(
        new HtmlWebpackPlugin({
          filename: `${project}-preload.blade.php`,
          inject: false,
          cache: false,
          chunks: [project],
          version: JSON.stringify(process.env.VERSION),
          templateContent: ({ htmlWebpackPlugin, compilation: { assets } }) => {
            return `
              ${htmlWebpackPlugin.files.css
                .map(
                  (css) => `<link rel="preload" href='{{$cdnDashboardUrl}}${css}' as="style" />\n`,
                )
                .join('')}
              ${htmlWebpackPlugin.files.js
                .map(
                  (js) => `<link rel="preload" href='{{$cdnDashboardUrl}}${js}' as="script" />\n`,
                )
                .join('')}
              ${Object.keys(assets)
                .reduce((accumulator, asset) => {
                  if (/\.(woff|woff2)?$/.test(asset)) {
                    const type = asset.match(/\.(woff|woff2)?$/);
                    accumulator.push(
                      `<link rel="preload" href='{{$cdnDashboardUrl}}/dist/${asset}' as="font" type="font/${type[1]}" crossorigin >\n`,
                    );
                  }
                  return accumulator;
                }, [])
                .join('')}`;
          },
        }),
      );
    }

    // *** config.plugins *** //
    // remove plugins not needed as per dashboard
    config.plugins = config.plugins.filter((plugin) => {
      return PLUGINS_TO_BE_REMOVED.indexOf(plugin?.constructor?.name) === -1;
    });
    config.optimization.minimizer = config.optimization.minimizer.filter(
      (minimizer) => minimizer.constructor.name !== 'ImageMinimizerPlugin',
    );

    // Configure the build analysis folder for each project
    config.plugins.forEach((plugin) => {
      if (plugin?.constructor?.name === 'BundleAnalyzerPlugin') {
        plugin.opts.reportFilename = `${project}-bundle-analysis.html`;
      }
    });

    config.module.rules.forEach((rule) => {
      if (rule?.type === 'asset/resource') {
        rule.generator.filename = 'images/[name].[hash][ext]';
        rule.generator.emit = true;
      }
    });

    // *** config.plugins *** //
    // update plugins needed as per dashboard
    if (process.env.DANGER_ENV) {
      // replace default plugin config
      config.plugins = config.plugins.filter((plugin) => {
        return plugin?.constructor?.name !== 'BundleAnalyzerPlugin';
      });
      config.plugins.push(
        new BundleAnalyzerPlugin({
          analyzerMode: 'json',
          openAnalyzer: false,
          reportFilename: `${project}/${project}-stats.json`,
          defaultSizes: 'gzip',
        }),
      );
    }

    config.output.pathinfo = !isProd;

    // use --warn agrs to see warn logs
    // Eg: `npm run serve --warn`
    if (!isProd && !process.argv.includes('--warn')) {
      config.infrastructureLogging = {
        level: 'error',
      };
    }

    return config;
  },
};
