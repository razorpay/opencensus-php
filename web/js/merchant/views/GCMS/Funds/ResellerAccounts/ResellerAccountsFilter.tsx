import React, { useState, useEffect } from 'react';
import { Box, Button, TextInput } from '@razorpay/blade/components';
import qs from 'query-string';
import { useSearchParams } from 'react-router-dom';

import {
  trackResellerFilterCleared,
  trackResellerFilterClicked,
  // eslint-disable-next-line import/namespace
} from 'merchant/views/GCMS/Funds/events';

import { StyledFilterDiv } from './StyledFilterDiv';

interface ResellerAccountsFilterProps {
  onSearch: ({ merchantName }: { merchantName: string }) => void;
}

const ResellerAccountsFilter = ({ onSearch }: ResellerAccountsFilterProps): JSX.Element => {
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
    trackResellerFilterClicked({ resellerName: resellerNameQuery });
  };

  const handleClearOnClick = () => {
    onSearch({ merchantName: '' });
    setSearchParams({ name: '' });
    setResellerNameQuery('');
    trackResellerFilterCleared();
  };

  return (
    <StyledFilterDiv>
      <div className="gcms-resellers-filter-group">
        <Box paddingY="spacing.4" display="flex" backgroundColor="surface.background.gray.intense">
          <div className="form-group gcms-list-filter-item">
            <TextInput
              label="Reseller Name"
              labelPosition="top"
              name="resellerName"
              onChange={(e) => {
                handleResellerNameSearchInputOnChange(e);
              }}
              type="url"
              validationState="none"
              testID="reseller_name_search_input"
              placeholder="Search reseller name"
              value={resellerNameQuery}
            />
          </div>

          <div className="list-filter-item btn-toolbar">
            <Button
              color="primary"
              onClick={handleSearchOnClick}
              size="medium"
              type="button"
              variant="primary"
            >
              Search
            </Button>
            <Button
              color="primary"
              onClick={handleClearOnClick}
              size="medium"
              type="button"
              variant="tertiary"
              marginLeft="spacing.3"
            >
              Clear
            </Button>
          </div>
        </Box>
      </div>
    </StyledFilterDiv>
  );
};

export default ResellerAccountsFilter;
