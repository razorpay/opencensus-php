import React, { useState } from 'react';
import { merge } from 'lodash';
import {
  Box,
  TextInput,
  Button,
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionListItem,
  ActionList,
  SearchIcon,
} from '@razorpay/blade/components';

import { StyledFilterDiv } from 'merchant/views/GCMS/shared/StyledDiv';
import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';

import { trackResellersFiltersCleared, trackResellersFiltersClicked } from '../events';

interface ResellersFilterProps {
  clearButton: boolean;
  onSearch: ({ resellerName, status }: { resellerName: string; status: string }) => {};
  disabled: boolean;
  filterOptions: object;
}
const FILTER_OPTIONS = {
  statusVisible: true,
};

const ResellersFilter = ({
  clearButton = false,
  onSearch,
  disabled,
  filterOptions,
}: ResellersFilterProps) => {
  const options = merge({}, FILTER_OPTIONS, filterOptions);
  const [resellerName, setResellerName] = useState('');
  const [status, setResellerStatus] = useState('all');
  const handleClear = () => {
    trackResellersFiltersCleared();
    setResellerStatus('all');
    setResellerName('');
    onSearch({ status: 'all', resellerName: '' });
  };

  function handleSearch() {
    trackResellersFiltersClicked({ resellerName, status });
    onSearch({ resellerName, status });
  }

  return (
    <Box
      display="flex"
      flexDirection="row"
      alignItems="flex-end"
      gap="spacing.3"
      marginBottom="spacing.5"
    >
      <TextInput
        accessibilityLabel="Reseller Name"
        labelPosition="top"
        name="merchant_name"
        placeholder="Search by Name, Brand"
        leadingIcon={SearchIcon}
        onChange={(e) => {
          setResellerName(e.value);
        }}
        showClearButton
        type="url"
        value={resellerName}
        validationState="none"
        testID="merchant_name"
      />
      {options.statusVisible && (
        <Box width="100px">
          <Dropdown isFull>
            <SelectInput
              label="Status"
              labelPosition="top"
              name="status"
              onChange={(e) => setResellerStatus(e.values?.[0])}
              placeholder="Select Option"
              validationState="none"
              isRequired={true}
              testID="status"
              value={status}
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
        </Box>
      )}

      <Button
        color="primary"
        onClick={handleSearch}
        size="medium"
        type="button"
        variant="primary"
        icon={SearchIcon}
        isDisabled={disabled}
      ></Button>
      {clearButton && (
        <Button
          color="primary"
          onClick={handleClear}
          size="medium"
          type="button"
          variant="tertiary"
          marginLeft="spacing.3"
        >
          Clear
        </Button>
      )}
    </Box>
  );
};

export default ResellersFilter;
