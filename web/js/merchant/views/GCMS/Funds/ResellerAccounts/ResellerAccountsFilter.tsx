import React, { useState, useEffect } from 'react';
import { Box } from '@razorpay/blade/components';
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
    setResellerNameQuery(e.target.value);
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
          <div className="form-group list-filter-item">
            <label>Reseller Name</label>
            <input
              name="resellerName"
              placeholder="Search reseller name"
              className="form-control input-sm"
              data-testid="reseller_name_search_input"
              value={resellerNameQuery}
              onChange={handleResellerNameSearchInputOnChange}
            />
          </div>

          <div className="list-filter-item btn-toolbar">
            <button className="btn btn-primary btn-sm" onClick={handleSearchOnClick}>
              Search
            </button>
            <button className="btn btn-sm btn-text" onClick={handleClearOnClick}>
              Clear
            </button>
          </div>
        </Box>
      </div>
    </StyledFilterDiv>
  );
};

export default ResellerAccountsFilter;
