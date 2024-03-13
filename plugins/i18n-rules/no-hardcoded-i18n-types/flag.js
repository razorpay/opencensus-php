// TODO: After i18nify flags module are added to Blade, encourage dev's to use the Blade Flag component.
function isFlagImageFound(value, node) {
  // Only checking for Literal types
  if (node.type === 'Literal' && node.value.match(/.*flag.*\.(png|jpg|jpeg|svg|gif)$/)) {
    return `Since you are showing the flag, please ensure that content adapts to a global audience. If it already is, please ignore this message.`;
  }

  return false;
}

module.exports = isFlagImageFound;
