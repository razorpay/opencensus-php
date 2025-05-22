export function validateResellerName(val) {
  if (!val) return 'Please fill out this field';
  if (val.length < 4) {
    return 'Reseller name should be at least of 4 characters';
  }
  return false;
}

export function validateResellerEmail(val) {
  if (!val) return 'Please fill out this field';
  return false;
}

export function validateResellerPhone(val) {
  if (!val || val.length != 10) return 'Please fill out this field';
  return false;
}