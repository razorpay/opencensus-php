/**
 * ESLint rule to detect and report hardcoded i18n types (e.g., dial codes, currency symbols, dates) within the source code.
 * Targets `Literal`, `TemplateLiteral`, and `JSXText` nodes in the AST to cover a broad range of hardcoded i18n value scenarios.
 *
 * Broad Applicability:
 * - Direct string literals and template literals in JavaScript and JSX attributes.
 * - Text content of JSX elements (`JSXText` nodes).
 * - Concatenated or constructed strings in various expressions and contexts.
 *
 * How it works:
 * - Targets `Literal`, `TemplateLiteral`, and `JSXText` nodes for comprehensive detection.
 * - Utilizes helper functions to identify and report hardcoded i18n-related data.
 * - Ensures broad and efficient rule applicability across code structures and patterns.
 *
 * Examples:
 * Dial Codes:
 *   Violations:
 *     - `const dialCode = '+1';` // Violation
 *     - `const fullDialCode = '+' + '44';` // Violation
 *     - `<span>Dial code: +33</span>` // Violation
 *     - `<input type="text" defaultValue="+1" />` // Violation
 *   Compliant:
 *     - `const dialCode = getDialCodeForCountry('US');` // Passes
 *     - `<span>Dial code: {dialCode}</span>` // Passes
 *     - `<input type="text" defaultValue={dialCode} />` // Passes
 *
 * Dates:
 *   Violations:
 *     - `const eventDate = '2023-01-01';` // Violation
 *     - ``const message = `Event date: 2023-01-01`;`` // Violation
 *   Compliant:
 *     - `const eventDate = formatDate(new Date());` // Passes
 *     - ``const message = `Event date: ${eventDate}`;`` // Passes
 */

const isFlagImageFound = require('./flag');
const isDateHardCoded = require('./date');
const isDialCodeHardCoded = require('./dialCode');
const isBrandDetailsHardCoded = require('./branding');
const isRegionNameOrCodeHardCoded = require('./region');
const isZipCodeHardCoded = require('./zipcode');
const isTimeZoneHardCoded = require('./timezone');
const isRegionCentricFeaturesFound = require('./features');

const hardCodedCheckFunctionsList = [
  isDateHardCoded,
  isDialCodeHardCoded,
  isRegionNameOrCodeHardCoded,
  isBrandDetailsHardCoded,
  isFlagImageFound,
  isZipCodeHardCoded,
  isTimeZoneHardCoded,
  isRegionCentricFeaturesFound,
];

module.exports = {
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Avoid hard codings i18n related values',
      category: 'Best Practices',
      recommended: true,
    },
    schema: [], // Indicates that this rule does not require any configuration options.
  },

  /**
   * Creates the rule to check for hardcoded i18n values.
   *
   * @param {RuleContext} context - The ESLint rule context.
   * @return {Object} Object containing AST node types to be visited and their corresponding functions.
   */
  create(context) {
    /**
     * Checks a node for hardcoded i18n data (dates, dial codes) and reports them if found.
     *
     * @param {ASTNode} node - The node to check for hardcoded values.
     * @param {string} value - The string value to check.
     */
    const checkNodeForHardcodedData = (node, value) => {
      if (typeof value !== 'string') return;

      hardCodedCheckFunctionsList.forEach((reportFunction) => {
        const message = reportFunction(value, node);
        if (message) {
          context.report({ node, message });
        }
      });
    };

    return {
      /**
       * Visits Literal nodes and checks them for hardcoded i18n values.
       *
       * @param {ASTNode} node - The Literal node being evaluated.
       */
      Literal(node) {
        checkNodeForHardcodedData(node, node.value);
      },

      /**
       * Visits TemplateLiteral nodes, combines their quasi values, and checks the result for hardcoded i18n values.
       *
       * @param {ASTNode} node - The TemplateLiteral node being evaluated.
       */
      TemplateLiteral(node) {
        const value = node.quasis.map((quasi) => quasi.value.raw).join('');
        checkNodeForHardcodedData(node, value);
      },

      /**
       * Visits JSXText nodes and checks them for hardcoded i18n values.
       *
       * @param {ASTNode} node - The JSXText node being evaluated.
       */
      JSXText(node) {
        checkNodeForHardcodedData(node, node.value);
      },
    };
  },
};
