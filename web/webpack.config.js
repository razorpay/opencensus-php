const CopyWebpackPlugin = require('copy-webpack-plugin');
const ExternalTemplateRemotesPlugin = require('external-remotes-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const NodePolyfillPlugin = require('node-polyfill-webpack-plugin');
const path = require('path');
const webpack = require('webpack');
const ModuleFederationPlugin = require('webpack/lib/container/ModuleFederationPlugin');
const WorkbboxWebpackPlugin = require('workbox-webpack-plugin');
// const ImageminWebpWebpackPlugin = require('imagemin-webp-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const ReactRefreshWebpackPlugin = require('@pmmmwh/react-refresh-webpack-plugin');

const deps = require('./package.json').dependencies;

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
const publicPath = `${isRedirector ? 'https://localhost:8080' : ''}/public/dist/`;
const rootPublicFolderPath = path.resolve(__dirname, `../public/dist`);

module.exports = {
  browserConfig: ({ config, isStoryBook = false }) => {
    // *** config.entry *** //
    config.entry = {
      [project]: `./js/${project}/index.js`, // merchant to be replaced by [project]
      shell: './public-path',
    };

    // TODO: is this needed? copied from sme shell
    // config.resolve.extensions = [...config.resolve.extensions, '.ts', '.tsx'];

    // TODO: no SentryWebpackPlugin?

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
        config.devServer.server = 'https';
        config.devServer.client.webSocketURL.hostname = 'localhost';
        config.devServer.client.webSocketURL.port = '8080';
      }
    }

    config.experiments.backCompat = false;
    config.parallelism = 500;

    config.output.module = false;
    config.experiments.outputModule = false;

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
    // TODO: do we need to add './node_modules', '../node_modules'?
    config.resolve.modules.push(path.resolve(__dirname, 'js'));
    // config.resolve.modules = [
    //   path.resolve(__dirname, 'src'),
    //   path.resolve(__dirname, 'static'),
    //   './node_modules',
    //   '../node_modules',
    // ];

    config.resolve.alias = {
      v2: path.resolve(__dirname, './v2'),
      react: path.resolve(__dirname, './node_modules/react'),
      assets: path.resolve(__dirname, './css/assets'),
    };

    config.optimization.runtimeChunk = false;
    // config.optimization.splitChunks = false;

    config.optimization.splitChunks = {
      cacheGroups: {
        blade: {
          test: /[\\/]node_modules[\\/](@razorpay[\\/]blade|@razorpay[\\/]blade-old)[\\/]/,
          name: 'blade',
          chunks: 'all',
          enforce: true,
          reuseExistingChunk: true,
        },
        sentry: {
          test: /[\\/]node_modules[\\/]@sentry[\\/]/,
          name: 'sentry',
          chunks: 'all',
          enforce: true,
          reuseExistingChunk: true,
        },
        highlight: {
          test: /[\\/]node_modules[\\/](highlight.js)[\\/]/,
          name: 'highlight',
          chunks: 'all',
          enforce: true,
          reuseExistingChunk: true,
        },
        refractor: {
          test: /[\\/]node_modules[\\/](refractor)[\\/]/,
          name: 'refractor',
          chunks: 'all',
          enforce: true,
          reuseExistingChunk: true,
        },
        utility: {
          test: /[\\/]node_modules[\\/](core-js-pure|lodash|rc-trigger)[\\/]/,
          name: 'utility',
          chunks: 'all',
          enforce: true,
          reuseExistingChunk: true,
        },
      },
      chunks: (module) => {
        // Check if the module is in node_modules and is not one of the specified modules
        if (
          module.resource &&
          module.resource.includes('node_modules') &&
          /(@razorpay[\\/]blade|@razorpay[\\/]blade-old|@sentry|highlight\.js|refractor|core-js-pure|lodash|rc-trigger)/.test(
            module.resource,
          )
        ) {
          return true;
        }
        return false;
      },
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
    // run this only once for merchant as it is common folder
    if (project === 'merchant') {
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
            window.isRedirector = ${isRedirector};
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
        'process.env.UNIVERSE_PUBLIC_ENV': JSON.stringify(process.env.STAGE),
      }),
      new NodePolyfillPlugin({}),
    );

    config.plugins.push(
      new ModuleFederationPlugin({
        name: 'shell',
        filename: `${project}-shell.remoteEntry.js`,
        // library: {
        //   name: 'shell',
        //   type: 'window'
        // },
        exposes: {
          './commonStore': './js/merchant/commonStore/index', // store with common data
          // ShowWhen uses Splitz and i18 context. Every micro-app will need
          // their own wrapper for these two providers if we move it to libs.
          './components/ShowWhen': './js/merchant_common/components/SharedShowWhen',

          // Exposing deprecated component only to make movement of legacy code easier.
          // New micro-apps are NOT supposed to use this import.
          './deprecated/withRouter': './js/common/deprecated/withRouter',

          // TODO: remove once Settlements is part of a micro-app
          './SettlementCycle': './js/merchant/views/Settlements/components/SettlementScheduleV2',
          // TODO: remove once Navigator is part of a micro-app
          './Navigator/constants': './js/merchant/views/Navigator/constants',
          // TODO: remove once Transactions v1 is part of a micro-app
          './Transactions/v1/DownloadSwiftCopy':
            './js/merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/DownloadSwiftCopy',
          './SpiltzServiceContext': './js/common/splitz/context/SplitzContextProvider',
          './I18Context': './js/common/i18/I18ServiceProvider',
          './public-path': './public-path',
        },
        shared: [
          {
            ...Object.keys(deps).reduce((dependencies, key) => {
              if (key === 'react-router-dom') {
                // TODO: also check for other projects
                if (project !== 'newAuth') {
                  dependencies['react-router-dom'] = {
                    requiredVersion: deps['react-router-dom'],
                    singleton: true,
                    // eager: true,
                  };
                }
                return dependencies;
              }
              dependencies[key] = deps[key];
              return dependencies;
            }, {}),
            xlsx: {
              singleton: true,
              version: '0.19.3',
            },
            react: {
              requiredVersion: deps.react,
              singleton: true,
              // eager: true,
            },
            'react-dom': {
              requiredVersion: deps['react-dom'],
              singleton: true,
              // eager: true,
            },
            'react-query': {
              requiredVersion: deps['react-query'],
              singleton: true,
              // eager: true,
            },
            'styled-components': {
              requiredVersion: deps['styled-components'],
              singleton: true,
              // eager: true,
            },
            zustand: {
              requiredVersion: deps.zustand,
              singleton: true,
              // eager: true,
            },
            '@razorpay/blade': {
              requiredVersion: deps['@razorpay/blade'],
              singleton: true,
              // eager: true,
            },
            '@razorpay/blade-old': {
              requiredVersion: deps['@razorpay/blade-old'],
              singleton: true,
              // eager: true,
            },
            // TODO: optimize this solution in future
            'common/splitz/utils': { singleton: true },
          },
        ],
        remotes: {
          shell: `shell@[window.cdnDashboardUrl]${
            isRedirector ? '/public' : ''
          }/dist/${project}-shell.remoteEntry.js`,
        },
      }),
      new ExternalTemplateRemotesPlugin(),
    );

    if (PROJECTS_USING_WORKBOX.indexOf(project) > -1) {
      config.plugins.push(
        new WorkbboxWebpackPlugin.InjectManifest({
          modifyURLPrefix: {
            '/dist/': `${RZP_CDN_URL}/dist/`,
          },
          include: [/\.(js|css)?$/, /\.(woff|woff2)?$/],
          exclude: [/(merchant-entry|merchantLA-entry).js$/],
          // Added No-Op service worker for Micro frontend release
          swSrc: './sw/no_op.js',
          swDest: `sw-utils/sw-${project}.js`,
        }),
      );
    }

    // TODO: need to be added back once optimisation is fixed
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
