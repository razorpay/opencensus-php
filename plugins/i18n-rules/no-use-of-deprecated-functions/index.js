// Import specific functions designed to report deprecated function usage and imports.
const {
  reportDeprecatedPhoneNumberFunctionImports,
  reportDeprecatedPhoneNumberFunctionCalls,
} = require('./use-isValidPhoneNumber');

// Arrays of functions to handle reporting of deprecated function calls and imports.
const reportDeprecatedFunctionCalls = [reportDeprecatedPhoneNumberFunctionCalls];
const reportDeprecatedFunctionImports = [reportDeprecatedPhoneNumberFunctionImports];

/**
 * ESLint rule definition to discourage the use of deprecated functions suggest modern alternatives.
 *
 * This rule is designed to be extendable, allowing additional checks for deprecated function calls
 * and imports to be easily added by including them in the `reportDeprecatedFunctionCalls` and
 * `reportDeprecatedFunctionImports` arrays.
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

  /**
   * The create function is called by ESLint for each file that is being linted.
   * It returns an object specifying methods that ESLint will call at specific points
   * in the traversal of the AST (Abstract Syntax Tree) of the file.
   *
   * @param {RuleContext} context - The ESLint rule context object, providing methods to interact with ESLint.
   * @returns {Object} Handlers for specific AST node types to check for deprecated usage.
   */
  create(context) {
    // Extract the source code of the file being linted, to be used in the helper functions.
    const sourceCode = context.getSourceCode();

    return {
      /**
       * Handles CallExpression nodes in the AST. Used to detect and report usage
       * of deprecated function calls within the code.
       *
       * @param {ASTNode} node - The node representing a function call in the code.
       */
      CallExpression(node) {
        reportDeprecatedFunctionCalls.forEach((reportFunction) => {
          // Execute each reporting function on the node.
          const report = reportFunction(node, sourceCode);
          if (report) {
            // If a deprecated function call is found, report it.
            context.report(report);
          }
        });
      },

      /**
       * Handles ImportDeclaration nodes in the AST. Used to detect and report imports
       * of deprecated utility functions or libraries.
       *
       * @param {ASTNode} node - The node representing an import declaration in the code.
       */
      ImportDeclaration(node) {
        reportDeprecatedFunctionImports.forEach((reportFunction) => {
          // Execute each reporting function on the node.
          const report = reportFunction(node, sourceCode);
          if (report) {
            // If a deprecated import is found, report it.
            context.report(report);
          }
        });
      },
    };
  },
};
