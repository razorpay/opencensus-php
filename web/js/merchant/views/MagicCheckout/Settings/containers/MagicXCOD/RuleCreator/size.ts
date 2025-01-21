export const byteLength = (str: string): number => {
  const encoder = new TextEncoder();
  const encodedString = encoder.encode(str);
  const lengthInBytes = encodedString.byteLength;

  return lengthInBytes;
};

export const getUsagePercentage = (byteLength: number, limitInKilobytes: number): number => {
  const limitValue = limitInKilobytes;
  const lengthInKB = Math.round(byteLength / 1024);
  return Math.round((lengthInKB / limitValue) * 100);
};
