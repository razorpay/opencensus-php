export const overviewConfigFilterOptions = (
  showRecentsOption = false,
  showCustomReports = false,
) => {
  if (showCustomReports) {
    const options = [
      {
        label: 'Standard Reports',
        value: 'standard_reports',
      },
      { label: 'Custom Reports', value: 'custom_reports' },
      { label: 'All Reports', value: 'show_all_reports' },
    ];
    return options;
  } else {
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
  }
};

export const JKBANK_REPORTS = ['Payments', 'Payments Report'];
