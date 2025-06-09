import React, { Dispatch } from 'react';
import { SSO_CUSTOMER_FILTER } from 'merchant/views/MagicCheckout/SSODashboard/types';

import { Box, SearchInput, Button, SearchIcon } from '@razorpay/blade/components';
import FilterList from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/Filters/List';

interface CustomerFiltersProps {
  selectedFilter: SSO_CUSTOMER_FILTER;
  isFetching: boolean;
  onSearchClick: (search?: string) => void;
  setSearch: Dispatch<React.SetStateAction<string>>;
  onFilterChange: (filter: SSO_CUSTOMER_FILTER) => void;
}

const CustomerFilters = ({
  selectedFilter,
  isFetching,
  onSearchClick,
  setSearch,
  onFilterChange,
}: CustomerFiltersProps) => {
  const onSearchChange = ({ value = '' }) => {
    setSearch(value);
  };
  return (
    <Box display="flex" marginBottom="spacing.5">
      <FilterList selectedFilter={selectedFilter} onFilterChange={onFilterChange} />
      <Box display="flex" alignItems="center" marginLeft="auto" gap="spacing.3" maxWidth="230px">
        <SearchInput
          placeholder="Search Number"
          isDisabled={isFetching}
          showSearchIcon={true}
          size="medium"
          label=""
          onChange={onSearchChange}
          onClearButtonClick={() => onSearchClick('')}
        />
        <Box flexShrink={0}>
          <Button
            variant="tertiary"
            color="primary"
            isLoading={isFetching}
            icon={SearchIcon}
            onClick={() => onSearchClick()}
          />
        </Box>
      </Box>
    </Box>
  );
};

export default CustomerFilters;
