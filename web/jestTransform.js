const babelOptions = require('./.babelrc.json');

module.exports = require('babel-jest').createTransformer(babelOptions);
