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
export const rzpConfigOrder = [
  'combined_report',
  'payments',
  'settlements_recon',
  'settlements',
  'orders',
  'refunds',
  'transfers',
  'reversals',
  'monthly_invoice',
  'scheduled',
];
