module.exports = {
  meta: {
    type: 'problem',
    docs: {
      description: "Disallow the use of '/dist/css/assets' in code",
      category: 'Best Practices',
      recommended: true,
    },
    messages: {
      avoidDistAssets:
        "The use of '/dist/css/assets' is prohibited. Import image using ES6 syntax eg. `import BankImage from 'assets/bank.svg';`",
    },
    schema: [],
  },
  create(context) {
    const checkNodeForDistAsset = (node, value) => {
      if (typeof value !== 'string') return;
      if (value.includes('/dist/css/assets')) {
        context.report({
          node,
          messageId: 'avoidDistAssets',
        });
      }
    };
    return {
      Literal(node) {
        checkNodeForDistAsset(node, node.value);
      },
      TemplateLiteral(node) {
        const value = node.quasis.map((quasi) => quasi.value.raw).join('');
        checkNodeForDistAsset(node, value);
      },
      JSXText(node) {
        checkNodeForDistAsset(node, node.value);
      },
    };
  },
};
