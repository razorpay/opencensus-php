export const toTitleCase = (inputString: string, separator = ' '): string =>
  inputString
    .toLowerCase()
    .split(separator)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
