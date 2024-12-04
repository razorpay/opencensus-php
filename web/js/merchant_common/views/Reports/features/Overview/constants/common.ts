export const overviewConfigFilterOptions = (showRecentsOption = false) => {
  const options = [
    {
      label: 'Report Type',
      value: 'report_type',
    },
  ];

  if (showRecentsOption) {
    options.push({
      label: 'Recents',
      value: 'recents',
    });
  }

  options.push({
    label: 'All Reports',
    value: 'all',
  });

  return options;
};

export const JKBANK_REPORTS = ['Payments', 'Payments Report'];
