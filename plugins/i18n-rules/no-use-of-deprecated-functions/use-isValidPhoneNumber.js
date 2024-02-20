const { deprecatedSpecifiersFixer, ensureImportsFromPackage } = require('./utils');

/**
 * Reports and fixes calls to deprecated phone number validation functions, replacing them with 'isValidPhoneNumber'.
 * Also ensures that 'isValidPhoneNumber' is correctly imported.
 *
 * @param {ASTNode} node - The node representing the call expression to be evaluated.
 * @param {SourceCode} sourceCode - The ESLint SourceCode object, providing access to the code's AST and text.
 * @returns {Object | false} An object containing the node, message, and fix function if a deprecated function is used, otherwise false.
 */
const reportDeprecatedPhoneNumberFunctionCalls = (node, sourceCode) => {
  if (['isPhone', 'isMobile'].includes(node.callee.name)) {
    return {
      node,
      message: `Use 'isValidPhoneNumber' from '@razorpay/i18nify-js/phoneNumber' instead of '${node.callee.name}'.`,
      fix(fixer) {
        // Prepare a fix to replace the deprecated function call with 'isValidPhoneNumber'.
        const fixes = [fixer.replaceText(node.callee, 'isValidPhoneNumber')];

        const importFix = ensureImportsFromPackage(
          fixer,
          sourceCode,
          '@razorpay/i18nify-js/phoneNumber',
          ['isValidPhoneNumber'],
        );

        if (importFix) {
          fixes.push(importFix);
        }

        return fixes; // Return all necessary fixes.
      },
    };
  }

  return false; // Return false if no deprecated function call is found.
};

/**
 * Identifies and fixes imports of deprecated phone number validation functions.
 * It replaces or removes these imports, ensuring the use of 'isValidPhoneNumber' instead.
 *
 * @param {ASTNode} node - The node representing the import declaration to be evaluated.
 * @param {SourceCode} sourceCode - The ESLint SourceCode object, providing access to the code's AST and text.
 * @returns {Object | false} An object containing the node, message, and fix function if deprecated imports are found, otherwise false.
 */
const reportDeprecatedPhoneNumberFunctionImports = (node, sourceCode) => {
  if (node.source.value === 'common/utils/validators') {
    // Identify deprecated specifiers ('isPhone' or 'isMobile') in the import declaration.
    const deprecatedSpecifiers = node.specifiers.filter((specifier) =>
      ['isPhone', 'isMobile'].includes(specifier.imported.name),
    );

    if (deprecatedSpecifiers.length > 0) {
      return {
        node,
        message: `Use 'isValidPhoneNumber' from '@razorpay/i18nify-js/phoneNumber' instead of 'isPhone' or 'isMobile'.`,
        fix(fixer) {
          // Apply a fix to remove deprecated specifiers and adjust the import statement accordingly.
          return deprecatedSpecifiersFixer(node, sourceCode, deprecatedSpecifiers, fixer);
        },
      };
    }
  }

  return false; // Return false if no action is required.
};

module.exports = {
  reportDeprecatedPhoneNumberFunctionCalls,
  reportDeprecatedPhoneNumberFunctionImports,
};
