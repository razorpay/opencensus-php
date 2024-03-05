// Todo: delete this file, it's available in @dashboard/shared-utils
export const acronyms = {
  payment: 'paymt',
  payments: 'paymts',
  method: 'meth',
  methods: 'meths',
  transaction: 'transctn',
  transactions: 'transctns',
  total: 'tot',
  number: 'no',
  platform: 'platfrm',
  platforms: 'platfrms',
  traffic: 'trffc',
  volume: 'vol',
};

export const shortenText = (sentence = '') => {
  const words = sentence.split(/\s+/);

  return words
    .map((word) => {
      return acronyms[word.toLowerCase()] || word;
    })
    .join(' ');
};
