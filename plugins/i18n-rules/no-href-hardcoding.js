/**
 * ESLint rule to identify and prevent hardcoding of specific domain names in href attributes
 *
 * @summary
 * This ESLint rule is a suggestion type, aiming to avoid hardcoding domain names in href attributes
 * of anchor tags or custom components within the codebase. It checks for forbidden domains
 * such as 'razorpay.com' and 'curlec.com' and issues a warning if found.
 **/
module.exports = {
  meta: {
    type: 'suggestion',
    docs: {
      description:
        'Avoid hardcoding domain name in href attributes of anchor tags or custom components',
    },
  },
  create(context) {
    // Check if a given href contains the forbidden domains
    function containsForbiddenDomains(hrefValue) {
      const forbiddenDomains = ['razorpay.com', 'curlec.com'];
      return forbiddenDomains.some((domain) => hrefValue.includes(domain));
    }

    // Check if a JSXElement node contains a forbidden href attribute
    function checkNodeForForbiddenHref(node) {
      const hrefAttribute = (node.attributes || []).find(
        (attr) => attr.type === 'JSXAttribute' && attr.name.name === 'href',
      );

      if (hrefAttribute?.value?.type === 'Literal') {
        const hrefValue = hrefAttribute.value.value;

        if (containsForbiddenDomains(hrefValue)) {
          context.report({
            node: hrefAttribute,
            message: `Avoid hardcoding region-specific links in anchors or custom components to maintain a consistent user experience for our global audience.`,
          });
        }
      }
    }

    return {
      JSXOpeningElement(node) {
        try {
          checkNodeForForbiddenHref(node);
        } catch (error) {
          console.log('error in no-href-hardcoding eslint rule');
        }
      },
    };
  },
};
