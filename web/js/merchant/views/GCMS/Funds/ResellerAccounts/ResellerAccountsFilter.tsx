import React, { useState, useEffect } from 'react';
import { Box, TextInput, SearchIcon, Button } from '@razorpay/blade/components';
import qs from 'query-string';
import { useSearchParams } from 'react-router-dom';

interface ResellerAccountsFilterProps {
  onSearch: ({ merchantName }: { merchantName: string }) => void;
}

const ResellerAccountsFilter = ({ onSearch }: ResellerAccountsFilterProps) => {
  const [resellerNameQuery, setResellerNameQuery] = useState('');

  const [, setSearchParams] = useSearchParams();

  const handleResellerNameSearchInputOnChange = (e) => {
    setResellerNameQuery(e.value);
  };

  useEffect(() => {
    // To pick name param from query params on mount
    const queryParams = qs.parse(location.search);
    if (typeof queryParams.name === 'string') {
      setResellerNameQuery(queryParams.name);
    }
  }, []);

  const handleSearchOnClick = () => {
    onSearch({ merchantName: resellerNameQuery });
    setSearchParams({ name: resellerNameQuery });
  };

  return (
    <Box
      paddingX="spacing.5"
      paddingY="spacing.4"
      backgroundColor="surface.background.level2.lowContrast"
      display="flex"
      flexDirection="row"
      gap="spacing.5"
    >
      <TextInput
        label=""
        placeholder="Search reseller name"
        icon={SearchIcon}
        name="resellerName"
        type="search"
        keyboardReturnKeyType="search"
        value={resellerNameQuery}
        onChange={handleResellerNameSearchInputOnChange}
        testID="reseller_name_search_input"
      />
      <Button onClick={handleSearchOnClick}>Search</Button>
    </Box>
  );
};

export default ResellerAccountsFilter;
