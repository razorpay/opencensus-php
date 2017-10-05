const dot = require('dot');
dot.templateSettings = require('dot-settings');

// preserves significant whitespace required by blade later on
dot.templateSettings.strip = false;

module.exports = function(content) {
  return 'module.exports = ' + dot.template(content);
};
