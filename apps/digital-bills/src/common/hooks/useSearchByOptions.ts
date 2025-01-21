import React from 'react';

import { BillSearchByOptions } from '@apps/digital-bills/src/utils/constants';

import type { BillUserSearchInput } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type ValidationState = 'none' | 'success' | 'error';

const useSearchByOptions = <T>(
  searchInputs: T,
  selectedSearchOption: string,
  setSelectedSearchOption: (option: string) => void,
  setFilterSearch: (option: string, value: string | undefined) => void,
  setBillsFilterSearchByUser?: (
    option: keyof BillUserSearchInput,
    value: string | undefined,
  ) => void,
) => {
  const [searchValidationState, setSearchValidationState] = React.useState<ValidationState>('none');
  const [searchErrorMsg, setSearchErrorMsg] = React.useState('');

  const searchByOptionHandler = (option: string) => {
    setSearchValidationState('none');
    setSearchErrorMsg('');
    setSelectedSearchOption(option);
  };

  const searchHandler = (option: string, value: string | undefined) => {
    if (selectedSearchOption === '') {
      setSearchValidationState('error');
      setSearchErrorMsg('Please select search by option');
      return;
    }
    if (
      option === BillSearchByOptions?.Email?.Value ||
      option === BillSearchByOptions?.PhoneNumber?.Value
    ) {
      setBillsFilterSearchByUser &&
        setBillsFilterSearchByUser(option as keyof BillUserSearchInput, value);
    } else {
      setFilterSearch(option, value);
    }
  };

  const getSearchInputValue = (option: string) => {
    return searchInputs[option as keyof T] ?? '';
  };

  return {
    searchValidationState,
    searchErrorMsg,
    searchByOptionHandler,
    searchHandler,
    getSearchInputValue,
  };
};

export default useSearchByOptions;
