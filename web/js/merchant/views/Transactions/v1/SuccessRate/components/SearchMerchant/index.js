import React, { useState } from 'react';
import { TextInput, Button, Box } from '@razorpay/blade/components';

const SearchMerchant = ({ onSearch, onReset, isLoading }) => {
  const [query, setQuery] = useState('');

  const handleInputChange = ({ value }) => setQuery(value);

  const handleReset = () => {
    setQuery('');
    onReset();
  };

  return (
    <Box display="flex" testID="admin-search">
      <Box>
        <TextInput
          placeholder="Search Merchant ID"
          value={query}
          onChange={handleInputChange}
          marginRight="spacing.3"
          testID="merchant-search-input"
          isLoading={query && isLoading}
        />
      </Box>
      <Box display="flex" alignItems="center">
        <Button
          marginRight="spacing.3"
          marginY="spacing.4"
          size="small"
          type="button"
          variant="primary"
          onClick={() => onSearch(query)}
          testID="admin-search-set-btn"
        >
          Set Merchant
        </Button>
        <Button
          marginY="spacing.4"
          size="small"
          type="reset"
          variant="secondary"
          onClick={handleReset}
          testID="admin-search-reset-btn"
        >
          Reset
        </Button>
      </Box>
    </Box>
  );
};

export default SearchMerchant;
