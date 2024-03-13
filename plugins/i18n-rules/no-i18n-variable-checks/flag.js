const getIsFlagNameFound = (_value) => {
  const value = _value.toLowerCase();
  const isFlagVariableFound = value.includes('flag') && !value.includes('feature');
  if (isFlagVariableFound) {
    return `Looks like you are showing a flag using ${_value}, please ensure that content adapts to a global audience. If it already is, please ignore this message.`;
  }

  return false;
};

module.exports = {
  getIsFlagNameFound,
};
