const { CURRENCIES } = require('./constant');

module.exports = {
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Warn if currency prop value is hardcoded in specific components',
    },
  },
  create(context) {
    // Function to check if currency attribute is hardcoded
    function checkCurrencyAttributeForHardcoding(attribute, componentName, node) {
      const currencyValue = attribute.value.value;

      // If the attribute value is a literal and is a hardcoded currency, report it
      if (attribute.value.type === 'Literal' && isHardcodedCurrency(currencyValue)) {
        context.report({
          node,
          message: `Prevent hardcoding the currency value "${currencyValue}" in the 'currency' prop of the ${componentName} component. Fetch currency from User props(user.merchant.currency) if required`,
        });
      }
    }

    // Function to check if a value is in the list of hardcoded currencies
    function isHardcodedCurrency(value) {
      const currencies = [...CURRENCIES];
      return currencies.includes(value);
    }

    // Function to check if the component should be checked for the currency prop
    function isComponentToCheck(componentName) {
      const COMPONENT_NAMES_TO_CHECK = ['Amount', 'AmountTooltip', 'AmountCard'];
      return COMPONENT_NAMES_TO_CHECK.includes(componentName);
    }

    return {
      // JSXOpeningElement handler to check currency prop
      JSXOpeningElement(node) {
        try {
          // Get the name of the JSX component
          const componentName = node.name.name;

          // Check if the component should be checked for the currency prop
          if (isComponentToCheck(componentName)) {
            // Find the 'currency' attribute in the JSX opening element
            const currencyAttribute = node.attributes.find(
              (attr) => attr.type === 'JSXAttribute' && attr.name.name === 'currency',
            );

            // If 'currency' attribute is found, check for hardcoding
            if (currencyAttribute) {
              checkCurrencyAttributeForHardcoding(currencyAttribute, componentName, node);
            }
          }
        } catch (error) {
          // Log an error if any exception occurs
          console.log('error in no-currency-hardcoding eslint rule');
        }
      },
    };
  },
};
