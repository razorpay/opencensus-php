/* converts each character (first or after space) to uppercase */
export const toTitleCase = string =>
  string
    .replace(
      /(\b\w|\s\w)/g,
      matched => ` ${matched[matched.length - 1].toUpperCase()}`
    )
    .trim();

/* converts strings like bank_transfer to Bank Transfer */
export const fromCamelToTitleCase = string =>
  toTitleCase(string.replace(/_/g, ' '));
