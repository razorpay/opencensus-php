const { getIsI18nNameCheckFound } = require('./branding');
const { getIsFlagNameFound } = require('./flag');

// List of functions to perform various identifier checks
const identifiersChecksList = [getIsI18nNameCheckFound, getIsFlagNameFound];

module.exports = {
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Disallow i18n related checks',
      category: 'Best Practices',
      recommended: false,
    },
    schema: [],
  },
  create(context) {
    return {
      Identifier(node) {
        // Check all variable names in the code
        identifiersChecksList.forEach((reportFunction) => {
          const message = reportFunction(node.name);
          if (message) {
            // Report a warning if the variable name should trigger a warning
            context.report({
              node,
              message,
            });
          }
        });
      },
    };
  },
};
