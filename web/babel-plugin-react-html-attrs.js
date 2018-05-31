var TRANSLATIONS = {
  class: 'className',
  for: 'htmlFor',
};

var nestedVisitor = {
  JSXAttribute: function(node) {
    if (node.node.name.name in TRANSLATIONS) {
      node.node.name.name = TRANSLATIONS[node.node.name.name];
    }
  },
};

module.exports = function() {
  return {
    visitor: {
      JSXElement: function(path, file) {
        if (!/^[a-z]+-/.test(path.node.openingElement.name.name)) {
          path.traverse(nestedVisitor);
        }
      },
    },
  };
};
