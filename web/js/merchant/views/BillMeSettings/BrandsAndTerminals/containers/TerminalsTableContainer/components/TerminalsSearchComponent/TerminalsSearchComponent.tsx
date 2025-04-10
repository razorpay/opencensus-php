import React from 'react';
import {
  Box,
  Button,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  AutoComplete,
  TextInput,
  SearchIcon,
} from '@razorpay/blade/components';

import {
  TERMINAL_STATUS_OPTIONS,
  TERMINALS_SEARCH_BY_FIELDS,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/constants';

import type {
  TerminalStatusOptionsType,
  TerminalSearchColumnType,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/types';

type TerminalsSearchComponentProps = {
  statusProps: {
    selectedTerminalsStatus: TerminalStatusOptionsType;
    setTerminalsFilterStatus: (status: TerminalStatusOptionsType) => void;
  };
  searchColumnProps: {
    selectedSearchByColumn: TerminalSearchColumnType;
    setTerminalsSearchByColumn: (searchTerm: TerminalSearchColumnType) => void;
  };
  searchProps: {
    searchTerm: string | undefined;
    setTerminalsFilterSearch: (searchTerm: string | undefined) => void;
  };
  paginationProps: {
    offset: number;
    setTerminalsFilterOffset: (offset: number) => void;
  };
  fetchFilteredTerminalsData: () => void;
};

const TerminalsSearchComponent = ({
  statusProps: { selectedTerminalsStatus, setTerminalsFilterStatus },
  searchColumnProps: { selectedSearchByColumn, setTerminalsSearchByColumn },
  searchProps: { searchTerm, setTerminalsFilterSearch },
  paginationProps: { offset, setTerminalsFilterOffset },
  fetchFilteredTerminalsData,
}: TerminalsSearchComponentProps) => {
  return (
    <Box
      display="flex"
      flexWrap="wrap"
      gap={{ base: 'spacing.3', l: 'spacing.5' }}
      justifyContent={{ l: 'space-between' }}
      alignItems="flex-end"
      paddingX="spacing.4"
      paddingY="spacing.5"
    >
      <Box width="400px">
        <Dropdown selectionType="single">
          <AutoComplete
            label="Status"
            placeholder="Select Terminal Status"
            name="action"
            value={selectedTerminalsStatus}
            onChange={({ values }) => {
              // if offset is not 0, reset the offset to fetch the filtered results from the first page
              if (offset > 0) {
                setTerminalsFilterOffset(0);
              }
              setTerminalsFilterStatus(values[0] as TerminalStatusOptionsType);
            }}
          />
          <DropdownOverlay>
            <ActionList>
              {Object.values(TERMINAL_STATUS_OPTIONS).map(({ label, value }) => (
                <ActionListItem key={label} title={label} value={value} />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </Box>
      <Box display="flex" gap="spacing.3" alignItems="flex-end">
        <Box width={{ base: '150px', l: '200px' }}>
          <Dropdown selectionType="single">
            <AutoComplete
              label="Search By"
              placeholder="Select Search By field"
              name="action"
              value={selectedSearchByColumn}
              onChange={({ values }) =>
                setTerminalsSearchByColumn(values[0] as TerminalSearchColumnType)
              }
            />
            <DropdownOverlay>
              <ActionList>
                {Object.values(TERMINALS_SEARCH_BY_FIELDS).map(({ label, value }) => (
                  <ActionListItem key={label} title={label} value={value} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
        <Box width={{ base: '125px', l: '250px' }}>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              fetchFilteredTerminalsData();
            }}
            data-testid="search-field-form"
          >
            <TextInput
              name="searchfield"
              placeholder="Search"
              accessibilityLabel="Terminal search field"
              value={searchTerm}
              onChange={({ value }) => setTerminalsFilterSearch(value)}
            />
          </form>
        </Box>
        <Button
          icon={SearchIcon}
          onClick={fetchFilteredTerminalsData}
          testID="terminals-search-button"
          data-analytics-name="terminals-search-button"
        />
      </Box>
    </Box>
  );
};

export default TerminalsSearchComponent;
