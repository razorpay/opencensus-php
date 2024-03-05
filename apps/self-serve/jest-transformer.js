const babelOptions = require('./.babelrc.legacy');

module.exports = require('babel-jest').createTransformer(babelOptions);
