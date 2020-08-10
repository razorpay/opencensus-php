const path = require('path');

module.exports = {
  types: ['woff', 'woff2'],
  cssTemplate: path.resolve(__dirname, './templates/icons.hbs'),
  html: true,
};
