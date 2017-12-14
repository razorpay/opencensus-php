const suffixes = ['k', 'L', 'Cr'];

export const humanReadableIndian = num => {
  if (num < 1000) return num.toString();
  else num /= 1000;

  const stdDivisor = 100;
  const powerRaised = Math.min(
    2,
    Math.floor(Math.log(num) / Math.log(stdDivisor))
  );
  return `${Math.round(num / Math.pow(stdDivisor, powerRaised))}${suffixes[
    powerRaised
  ]}`;
};
