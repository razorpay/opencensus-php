export const getCompactNumber = (number: number, locale?: string | undefined) => {
  return new Intl.NumberFormat(locale, { notation: 'compact' }).format(number);
};

export const getFormattedNumber = (number: number, locale?: string | undefined) => {
  return new Intl.NumberFormat(locale, {}).format(number);
};
