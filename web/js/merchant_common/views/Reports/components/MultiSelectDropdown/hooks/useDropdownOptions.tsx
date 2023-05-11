import { useContext, useMemo } from 'react';
import { getFilteredItems } from 'merchant_common/views/Reports/components/MultiSelectDropdown/Utils';
import { AsyncDropdownContext } from 'merchant_common/views/Reports/components/AsyncDropdown';

export const useDropdownOptions = (
  options,
  value,
  labelKey,
  searchFor,
  shouldAllowMultiple,
  shouldShowInput,
) => {
  const { isAsyncDropdown } = useContext(AsyncDropdownContext);

  return useMemo(() => {
    if (isAsyncDropdown) {
      return getFilteredItems(options, value, labelKey, '', shouldAllowMultiple);
    } else {
      return getFilteredItems(options, value, labelKey, searchFor, shouldAllowMultiple);
    }
  }, [value, options, searchFor, shouldShowInput]);
};
