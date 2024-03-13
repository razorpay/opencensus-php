const { createConfigForDeprecatedFunction, findImportDeclaration } = require('./utils');
const deprecatedFunctionsConfigs = require('./configs');

// This will take the configs and create the fixers based on config;
const DEPRECATED_FUNCTIONS_CONFIG_WITH_FIXERS_LIST = deprecatedFunctionsConfigs.map(
  createConfigForDeprecatedFunction,
);

/**
 * ESLint rule definition to discourage the use of deprecated functions suggest the alternatives.
 */
module.exports = {
  meta: {
    type: 'suggestion',
    docs: {
      description:
        'Recommended to not use the deprecated functions. Replace them with their modern equivalents.',
      category: 'Best Practices',
      recommended: true,
    },
    fixable: 'code', // This rule provides automatic fixes for some of the reported issues.
    schema: [], // This rule does not require configuration options.
  },
  create(context) {
    // Extract the source code of the file being linted, to be used in the helper functions.
    const sourceCode = context.getSourceCode();

    return {
      CallExpression(node) {
        DEPRECATED_FUNCTIONS_CONFIG_WITH_FIXERS_LIST.forEach(
          ({ message, deprecatedSpecifiers, specifierToImport, deprecatedSpecifiersFrom }) => {
            // Reports only for the deprecatedSpecifiers is imported from deprecatedSpecifiersFrom and deprecatedSpecifiers is getting called
            if (deprecatedSpecifiers.includes(node.callee.name)) {
              if (findImportDeclaration(sourceCode, deprecatedSpecifiersFrom)) {
                context.report({
                  node,
                  message,
                  // Remove the deprecated function calls and replace them with "specifierToImport"
                  // Temporarily disabling the fixer, due to newly added eslint auto fix feature in github action
                  // fix: (fixer) => {
                  //   return fixer.replaceText(node.callee, specifierToImport);
                  // },
                });
              }
            }
          },
        );
      },
      ImportDeclaration(node) {
        DEPRECATED_FUNCTIONS_CONFIG_WITH_FIXERS_LIST.forEach(
          ({ message, deprecatedSpecifiersFrom, deprecatedSpecifiers, processImports }) => {
            // Reports only if the deprecatedSpecifiers imported from deprecatedSpecifiersFrom and
            if (node.source.value === deprecatedSpecifiersFrom) {
              const deprecatedSpecifiersInAst = node.specifiers.filter((specifier) => {
                return deprecatedSpecifiers.includes((specifier.local || specifier.imported).name);
              });
              if (deprecatedSpecifiersInAst.length > 0) {
                context.report({
                  node,
                  message,
                  // Updates the code to import the "specifierToImport" from "importPackageFrom"
                  // Remove the all of deprecated function calls from import statements.
                  // Temporarily disabling the fixer, due to newly added eslint auto fix feature in github action
                  // fix: (fixer) => {
                  //   const fixes = processImports(node, sourceCode, fixer);
                  //   return fixes;
                  // },
                });
              }
            }
          },
        );
      },
    };
  },
};
