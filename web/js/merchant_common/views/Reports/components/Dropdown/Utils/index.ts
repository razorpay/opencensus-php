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
  return searchFor
    ? typeof option === 'string'
      ? option?.toLowerCase()?.startsWith(searchFor.toLowerCase()) ||
        option?.toLowerCase()?.includes(searchFor.toLowerCase())
      : option[labelKey]?.toLowerCase()?.startsWith(searchFor.toLowerCase()) ||
        option[labelKey]?.toLowerCase()?.includes(searchFor.toLowerCase())
    : true;
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
