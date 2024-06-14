import { useState } from 'react';
import {
  All,
  AllFilters,
  BusinessModelFilterDropDownActionType,
  FilterDropDownActionType,
  FilterStateType,
  PricingFilterDropDownActionType,
  SearchBy,
  StatusFilterDropDownActionType,
} from '../types';

type SetFilterStateType = (type: AllFilters, value: FilterDropDownActionType | string) => void;

type UseFilterType = () => [FilterStateType, SetFilterStateType];

const useFilter: UseFilterType = () => {
  const [status, setStatus] = useState<StatusFilterDropDownActionType>(All.ALL);
  const [pricing, setPricing] = useState<PricingFilterDropDownActionType>(All.ALL);
  const [businessModel, setBusinessModel] = useState<BusinessModelFilterDropDownActionType>(
    All.ALL,
  );
  const [searchBy, setSearchBy] = useState<SearchBy>(SearchBy.MID);
  const [searchField, setSearchField] = useState<string>('');

  const filterState: FilterStateType = {
    status,
    pricing,
    businessModel,
    searchBy,
    searchField,
  };

  const setFilterState: SetFilterStateType = (type, value) => {
    switch (type) {
      case AllFilters.STATUS:
        setStatus(value as StatusFilterDropDownActionType);
        break;
      case AllFilters.PRICING:
        setPricing(value as PricingFilterDropDownActionType);
        break;
      case AllFilters.BUSINESS_MODEL:
        setBusinessModel(value as BusinessModelFilterDropDownActionType);
        break;
      case AllFilters.SEARCH_BY:
        setSearchBy(value as SearchBy);
        break;
      case AllFilters.SEARCH_FIELD:
        setSearchField(value);
        break;
      default:
        break;
    }
  };

  return [filterState, setFilterState];
};

export default useFilter;
