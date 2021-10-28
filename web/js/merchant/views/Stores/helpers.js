export const storeNameValidator = (val) => {
  if (val && val.length < 3) {
    return 'The store name must be at least 3 characters';
  }
  if (val && val.length > 40) {
    return 'The store may not be greater than 40 characters.';
  }
  return '';
};

export const slugValidator = (val) => {
  if (val.length === 0) {
    return 'This is a required field';
  }
  if (val && val.length < 4) {
    return 'The slug must be at least 4 characters.';
  }
  if (val && val.length > 30) {
    return 'The slug may not be greater than 30 characters.';
  }
  return '';
};
