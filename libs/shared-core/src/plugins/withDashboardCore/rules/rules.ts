import { dashboardBabelConfig } from '../transpilers/babel/babel.transpiler';
import {
  externalDeps,
  resolveFromRootNodeModules,
} from '@src/plugins/withDashboardCore/utils/external-deps';
import { dashboardSwcConfig } from '../transpilers/swc';

export const mainFilesToTranspile = /\.(m?js|jsx|ts|tsx)?$/;

export const getCommonRules = ({
  isDev,
  isNodeApp,
  isInAppDir,
  isShell,
  isWebpackWithSwc,
  moduleName,
}: {
  isNodeApp: boolean;
  isDev: boolean;
  isInAppDir: boolean;
  isShell: boolean;
  isWebpackWithSwc: boolean;
  moduleName: any;
}) => {
  const MiniCssExtractPlugin = require(externalDeps['mini-css-extract-plugin']);
  return [
    {
      // This will also process *.font.js, need to check this
      test: mainFilesToTranspile,
      exclude: new RegExp(
        isInAppDir
          ? '/node_modules/(?!' +
            '(?:.pnpm/)?' + // Match the .pnpm/ prefix if it exists
            '(' +
            'react-intl|' +
            'intl-messageformat|' +
            '@formatjs/icu-messageformat-parser|' +
            '@razorpay|' +
            '@universe' +
            ')/' +
            ').*/'
          : // TODO: need to check why this works for web nexus but fails for above
            '/node_modules/(?!(?:.pnpm/)?(@razorpay|@universe)).*/',
      ),
      use: [
        !isWebpackWithSwc && {
          loader: externalDeps['babel-loader'],
          options: {
            babelrc: false,
            cacheDirectory: isDev,
            ...dashboardBabelConfig({
              isDev,
              isNodeApp,
              isShell,
              moduleName,
            }),
          },
        },

        isWebpackWithSwc && {
          loader: 'swc-loader',
          options: dashboardSwcConfig({
            isDev,
            isNodeApp,
            isShell,
          }),
        },
      ].filter(Boolean),
      resolve: { fullySpecified: false },
    },
    {
      test: /\.(svg|gif|avif|png|jpe?g|ico|webp|woff(2)?|ttf|otf|eot|pdf)$/,
      type: 'asset/resource',
      generator: {
        // We don't want to use [hash] since we have two different builds for legacy and modern browsers
        filename: 'static/[name].[hash][ext]',
        emit: !isNodeApp,
      },
    },
    // https://github.com/remix-run/react-router/issues/8353#issuecomment-973057552
    {
      test: /\.mjs$/,
      include: /node_modules/,
      type: 'javascript/auto',
    },
    {
      test: /\.(graphql|gql)$/,
      exclude: /node_modules/,
      loader: externalDeps['graphql-tag/loader'],
    },

    ...(!isNodeApp
      ? [
          {
            test: /\.css$/i,
            use: [
              !isDev ? MiniCssExtractPlugin.loader : externalDeps['style-loader'],
              externalDeps['css-loader'],
            ].filter(Boolean),
          },
          // For error-overlay-webpack-plugin@1.1.0, It has `.mjs` imports in commonjs export
          // More information - https://github.com/gregberge/error-overlay-webpack-plugin/pull/93
          {
            test: /\.mjs$/,
            include: /error-overlay-webpack-plugin/,
            type: 'javascript/auto',
          },
          {
            test: /\.styl$/,
            use: [
              !isDev ? MiniCssExtractPlugin.loader : externalDeps['style-loader'],
              {
                loader: externalDeps['css-loader'],
                options: {
                  url: false,
                },
              },
              {
                loader: externalDeps['stylus-loader'],
                options: {
                  stylusOptions: {
                    include: [
                      resolveFromRootNodeModules('bootstrap-styl', {
                        resolutionType: 'path',
                      }),
                    ],
                    resolveURL: false,
                  },
                },
              },
            ].filter(Boolean),
          },
        ]
      : [
          {
            test: /\.css$/i,
            type: 'asset/source',
          },
          {
            test: /\.node$/,
            loader: externalDeps['node-loader'],
          },
        ]),
  ];
};
