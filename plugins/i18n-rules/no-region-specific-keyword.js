/**
 * ESLint rule to discourage the use of specific keywords in JSX code
 *
 * @summary
 * This ESLint rule is a suggestion type, aiming to avoid the use of specific keywords in JSX code
 * within the Razorpay codebase. It checks for forbidden keywords such as 'Razorpay', 'SBI', 'RBI',
 * 'GST', 'CIN', '$', '₹' and issues a warning if found. The goal is to maintain consistency in
 * language and prevent region-specific language usage.
 */

const { FORBIDDEN_KEYWORDS } = require('./constant');

module.exports = {
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Avoid the use of specific keywords in JSX code',
    },
  },
  create(context) {
    const forbiddenKeywords = [...FORBIDDEN_KEYWORDS];

    // Check if a given text contains any forbidden keywords
    function containsForbiddenKeywords(text) {
      return forbiddenKeywords.some((keyword) => text.includes(keyword));
    }

    // Check if a JSXElement node contains any forbidden keywords
    function checkNodeForForbiddenKeywords(node) {
      if (node.type === 'JSXText' && containsForbiddenKeywords(node.value)) {
        const forbiddenKeyword = forbiddenKeywords.find((keyword) => node.value.includes(keyword));

        if (forbiddenKeyword) {
          context.report({
            node,
            message: `Discourage using the restricted keyword "${forbiddenKeyword}" in text to maintain consistency and avoid region-specific language`,
          });
        }
      }
    }

    return {
      JSXElement(node) {
        try {
          // Check JSXElement children for forbidden keywords
          node.children.forEach(checkNodeForForbiddenKeywords);

          // Check JSXElement attributes for forbidden keywords
          if (node.openingElement?.attributes) {
            node.openingElement.attributes.forEach((attribute) => {
              if (attribute.value?.type === 'JSXText') {
                checkNodeForForbiddenKeywords(attribute.value);
              }
            });
          }
        } catch (error) {
          console.log('error in no-region-specific-keyword eslint rule');
        }
      },
    };
  },
};
