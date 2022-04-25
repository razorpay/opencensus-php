export const minLength = (length, message = '') => {
  message = message || `Enter min ${length} characters`;

  return (value = '') => {
    const updatedValue = value.trim().replace(/\s+/g, ' ');
    return updatedValue.length < length ? message : '';
  };
};
