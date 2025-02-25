const CopyWebpackPlugin = require('copy-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const { withDashboardCore, DASHBOARD_FEDERATED_MODULES } = require('@libs/shared-core');
const { DefinePlugin } = require('webpack');
const { name: projectName } = require('./package.json');
const path = require('path');
// const WorkbboxWebpackPlugin = require('workbox-webpack-plugin');
// const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

module.exports = withDashboardCore({
  browserBundlerOptions: {
    moduleName: DASHBOARD_FEDERATED_MODULES.LA_DASHBOARD,
    sentryConfig: {
      dsn: process.env.WEB_NEXUS_SENTRY_DSN,
      project: process.env.WEB_NEXUS_SENTRY_PROJECT,
    },
    moduleFederationConfig: {
      exposedDir: null,
      remotes: [DASHBOARD_FEDERATED_MODULES.SHELL, DASHBOARD_FEDERATED_MODULES.PAYMENTS_DASHBOARD],
    },
  },
  extendBrowserWebpackConfig: (config, { isDev, externalDeps, sentryAppVersion }) => {
    const MiniCssExtractPlugin = require(externalDeps['mini-css-extract-plugin']);
    const UNIVERSE_PUBLIC_ASSETS_URL = process.env.UNIVERSE_PUBLIC_ASSETS_URL;

    config.entry = {
      [projectName]: `./index.js`,
    };

    config.plugins = [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: '../../css/assets',
            to: './css/assets',
          },
        ],
      }),
      new HtmlWebpackPlugin({
        filename: `${projectName}.entry.js`,
        inject: false,
        cache: false,
        chunks: [projectName],
        version: JSON.stringify(process.env.VERSION),
        templateContent: ({ htmlWebpackPlugin }) => {
          return `(function(){
            var websiteAssets = {
              js : ${JSON.stringify(htmlWebpackPlugin.files.js)},
              css : ${JSON.stringify(htmlWebpackPlugin.files.css)}
            };
            window.__VERSION__ = ${htmlWebpackPlugin.options.version};
            ${require(`./${projectName}.entry`)()}})()`;
        },
      }),
      !isDev &&
        new HtmlWebpackPlugin({
          filename: `${projectName}.preload.json`, // Generates a JSON file
          inject: false,
          cache: false,
          chunks: [projectName],
          version: JSON.stringify(process.env.VERSION),
          templateContent: ({ htmlWebpackPlugin, compilation: { assets } }) => {
            // Constructing JSON content with CSS, JS, and fonts
            const preloadAssets = {
              tag: 'link',
              rel: 'preload',
              css: htmlWebpackPlugin.files.css.map((css) => ({
                url: css,
                as: 'style',
              })),
              js: htmlWebpackPlugin.files.js.map((js) => ({
                url: js,
                as: 'script',
              })),
              fonts: Object.keys(assets).reduce((accumulator, asset) => {
                if (/\.(woff|woff2)?$/.test(asset)) {
                  accumulator.push({
                    url: `${UNIVERSE_PUBLIC_ASSETS_URL}${asset}`,
                    type: `font/${asset.match(/\.(woff|woff2)?$/)[1]}`,
                    as: 'font',
                    crossOrigin: true,
                  });
                }
                return accumulator;
              }, []),
              version: process.env.VERSION || '',
            };

            return JSON.stringify(preloadAssets, null, 2); // Pretty-print JSON
          },
        }),
      new DefinePlugin({
        'process.env.PROJECT': JSON.stringify(projectName),
        'process.env.PUBLIC_ENV': JSON.stringify(process.env.STAGE),
        'process.env.UNIVERSE_PUBLIC_ENV': JSON.stringify(process.env.STAGE),
        __WEB_NEXUS_SENTRY_VERSION__: JSON.stringify(sentryAppVersion),
        __WEB_NEXUS_SENTRY_DSN__: JSON.stringify(process.env.WEB_NEXUS_SENTRY_DSN),
      }),
      // projectConfig.isWorkboxEnabled &&
      //   !isDev &&
      //   new WorkbboxWebpackPlugin.InjectManifest({
      //     modifyURLPrefix: {
      //       '/dist/': `${RZP_CDN_URL}/dist/`,
      //     },
      //     include: [/\.(js|css)?$/, /\.(woff|woff2)?$/],
      //     exclude: [/(merchant-entry|merchantLA-entry).js$/],
      //     // Added No-Op service worker for Micro frontend release
      //     swSrc: './sw/no_op.js',
      //     swDest: `sw-utils/sw.${PROJECT}.js`,
      //   }),
      // process.env.DANGER_ENV &&
      //   !isDev &&
      //   new BundleAnalyzerPlugin({
      //     analyzerMode: 'json',
      //     openAnalyzer: false,
      //     reportFilename: `${PROJECT}/${PROJECT}.stats.json`,
      //     defaultSizes: 'gzip',
      //   }),
    ].filter(Boolean);

    config.module = {
      rules: [
        {
          test: /\.font\.js/,
          use: [
            !isDev ? MiniCssExtractPlugin.loader : externalDeps['style-loader'],
            {
              loader: externalDeps['css-loader'],
              options: {
                url: false,
              },
            },
            {
              loader: externalDeps['webfonts-loader'],
              options: {
                publicPath: UNIVERSE_PUBLIC_ASSETS_URL,
                classPrefix: 'i-',
                files: [path.resolve(__dirname, `../../icons/merchantLA/*.svg`)],
                fontName: `${projectName}-icons`,
                fileName: !isDev ? 'css/[fontname].[hash].[ext]' : 'css/[fontname].[ext]',
                htmlDest: `css/[fontname].html`,
                html: true,
              },
            },
          ].filter(Boolean),
        },
      ].filter(Boolean),
    };

    return config;
  },
});
