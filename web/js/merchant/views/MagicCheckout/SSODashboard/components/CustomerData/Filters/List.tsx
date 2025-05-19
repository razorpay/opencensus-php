import React from 'react';
import { QuickFilter, QuickFilterGroup } from '@razorpay/blade/components';
import type { SSO_CUSTOMER_FILTER } from 'merchant/views/MagicCheckout/SSODashboard/types';
import { FILTERS } from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/Filters/constants';

interface FilterListProps {
  selectedFilter: SSO_CUSTOMER_FILTER;
  onFilterChange: (filter: SSO_CUSTOMER_FILTER) => void;
}

const FilterList = ({ selectedFilter, onFilterChange }: FilterListProps) => {
  const handleFilterChange = ({ values }) => {
    const [value] = values;
    onFilterChange(value);
  };
  return (
    <QuickFilterGroup selectionType="single" onChange={handleFilterChange} value={selectedFilter}>
      {FILTERS.map((filter, index) => (
        <QuickFilter title={filter.title} value={filter.value} key={index} />
      ))}
    </QuickFilterGroup>
  );
};

export default FilterList;
