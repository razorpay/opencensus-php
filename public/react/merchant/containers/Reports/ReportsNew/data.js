const customConfigsMap = {
  monthlyInvoice: 'Monthly Invoice',
  dsp: 'DSP Transaction Report',
  broking: 'Broking Report',
  emitra: 'e-Mitra Report',
};

export const getCustomConfig = key => {
  if (!customConfigsMap[key]) {
    return null;
  }

  return {
    label: customConfigsMap[key],
    value: key,
    type: 'custom',
  };
};

export const marketplaceConfigTypes = {
  transactions: '',
  payments: '',
  refunds: '',
  settlements: '',
};
