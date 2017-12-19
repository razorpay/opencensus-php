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
