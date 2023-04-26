export const overviewConfigFilterOptions = (showRecentsOption = false) => {
  const options = [
    {
      label: 'All Reports',
      value: '',
    },
  ];

  if (showRecentsOption) {
    options.push({
      label: 'Recents',
      value: 'recents',
    });
  }

  options.push({
    label: 'Report Type',
    value: 'report_type',
  });

  return options;
};
