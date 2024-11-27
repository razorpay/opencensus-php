type KB = 'kb';

export const byteLength = (str: string): number => {
  const encoder = new TextEncoder();
  const encodedString = encoder.encode(str);
  const lengthInBytes = encodedString.byteLength;

  return lengthInBytes;
};

type KBLimit = `${number}${Uppercase<KB> | KB}`;

export const getUsagePercentage = (byteLength: number, limitInKilobytes: KBLimit): number => {
  const limitValue = parseFloat(limitInKilobytes.toLowerCase().replace('kb', ''));
  const lengthInKB = Math.round(byteLength / 1024);
  return Math.round((lengthInKB / limitValue) * 100);
};
