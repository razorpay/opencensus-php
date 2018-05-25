const customConfigsMap = {
  monthlyInvoice: 'Monthly Invoice',
  dsp_report: 'DSP Transaction Report',
  broking: 'Broking Report',
  rpp_report: 'e-Mitra Report',
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

/*
  * reports v2 tab order of rzp owned configs
*/
//TODO: get suitable data for sort
export const rzpConfigOrder = [
  'Combined Report',
  'Payments',
  'Settlement Recon',
  'Settlements',
  'Orders',
  'Transactions',
  'Refunds',
  'Transfers',
  'Reversals',
  'Monthly Invoice',
  'Scheduled',
];
