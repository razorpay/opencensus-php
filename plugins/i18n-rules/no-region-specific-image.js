module.exports = {
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Encourage localization by checking if images are already region-agnostic.',
    },
  },
  create(context) {
    const { options } = context;
    const exceptions = options[0]?.exceptions || [];

    function reportInfoMessage(node, message) {
      context.report({
        node,
        message,
      });
    }

    function isImageElement(node) {
      return (
        (node.name.type === 'JSXIdentifier' &&
          (node.name.name === 'img' || node.name.name === 'Image')) ||
        (node.name.type === 'JSXMemberExpression' && node.name.object.name === 'Image')
      );
    }

    return {
      JSXOpeningElement(node) {
        if (isImageElement(node) && !exceptions.includes(node.name.name)) {
          // Provide an informational message with context
          const defaultMessage =
            'Make sure that this image is localized. If it already is, please ignore this message.';

          const customMessage = options[0]?.message || defaultMessage;
          reportInfoMessage(node, customMessage);
        }
      },
    };
  },
};
