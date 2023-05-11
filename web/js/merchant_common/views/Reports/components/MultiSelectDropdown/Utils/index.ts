const applyFilterForMultiSelect = (selected, option, labelKey) => {
  return selected
    ? selected.findIndex((m) =>
        typeof m === 'string' ? m === option : m[labelKey] === option[labelKey],
      ) < 0
    : true;
};

const applyFilterForSingleSelect = () => {
  return true;
};

const applyFilterForQuery = (option, labelKey, searchFor) => {
  if (!searchFor) {
    return true;
  }

  if (typeof option === 'string') {
    return (
      option?.toLowerCase()?.startsWith(searchFor.toLowerCase()) ||
      option?.toLowerCase()?.includes(searchFor.toLowerCase())
    );
  }

  const optionLabel = option?.[labelKey];

  return (
    optionLabel?.toLowerCase()?.startsWith(searchFor.toLowerCase()) ||
    optionLabel?.toLowerCase()?.includes(searchFor.toLowerCase())
  );
};

export const getFilteredItems = (options, selected, labelKey, searchFor, shouldAllowMultiple) => {
  return options.filter((option) => {
    return (
      (shouldAllowMultiple
        ? applyFilterForMultiSelect(selected, option, labelKey)
        : applyFilterForSingleSelect()) && applyFilterForQuery(option, labelKey, searchFor)
    );
  });
};
