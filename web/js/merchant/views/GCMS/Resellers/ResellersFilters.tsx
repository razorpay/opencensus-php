import React, { useState } from 'react';
import {
  Box,
  TextInput,
  Button,
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionListItem,
  ActionList,
} from '@razorpay/blade/components';

import { StyledFilterDiv } from 'merchant/views/GCMS/shared/StyledDiv';
import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';

import { trackResellersFiltersCleared, trackResellersFiltersClicked } from './events';

interface ResellersFilterProps {
  onSearch: ({ status, resellerName }) => void;
}

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
const ResellersFilter = ({ onSearch }: ResellersFilterProps) => {
  const [status, setStatus] = useState('all');
  const [resellerName, setResellerName] = useState('');

  const onClear = () => {
    trackResellersFiltersCleared();
    setStatus('all');
    setResellerName('');
    onSearch({ status: 'all', resellerName: '' });
  };

  const handleStatusChange = (value) => {
    setStatus(value);
  };

  const handleResellerNameChange = (value) => {
    setResellerName(value);
  };

  const handleSearch = () => {
    trackResellersFiltersClicked({ resellerName, status });
    onSearch({ resellerName, status });
  };

  return (
    <StyledFilterDiv>
      <div className={`gcms-filter-group ${'all-time-filter-selected'}`}>
        <Box paddingY="spacing.4" display="flex">
          <div className="form-group gcms-list-filter-item">
            <TextInput
              label="Reseller Name"
              labelPosition="top"
              name="merchant_name"
              onChange={(e) => {
                handleResellerNameChange(e.value);
              }}
              showClearButton
              type="url"
              validationState="none"
              testID="merchant_name"
            />
          </div>
          <div className="form-group list-filter-item">
            <Dropdown>
              <SelectInput
                label="Status"
                labelPosition="top"
                name="status"
                onChange={(e) => handleStatusChange(e.values?.[0])}
                placeholder="Select Option"
                validationState="none"
                isRequired={true}
                testID="status"
                defaultValue={status}
              />
              <DropdownOverlay>
                <ActionList>
                  {Object.values(RESELLERS_STATUS).map((status) => (
                    <ActionListItem
                      key={status.value}
                      title={status.label}
                      value={status.value}
                      testID={`option-${status.value}`}
                    />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          </div>

          <div className="form-group list-filter-item btn-toolbar">
            <Button
              color="primary"
              onClick={handleSearch}
              size="medium"
              type="button"
              variant="primary"
            >
              Search
            </Button>
            <Button
              color="primary"
              onClick={onClear}
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

export default ResellersFilter;
