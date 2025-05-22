import React from 'react';
import {
  Box,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Button,
  ChevronLeftIcon,
  ChevronRightIcon,
  Text,
} from '@razorpay/blade/components';

const Pagination = ({ next, prev, listData, skip, count, changePageSize, totalCount }) => {
  const totalPages = Math.ceil(totalCount / (count * 1.0));
  const currentPage = skip / count + 1;
  return (
    <Box
      display="flex"
      flexDirection="row"
      justifyContent="space-between"
      alignItems="center"
      marginTop="spacing.3"
    >
      <Box>
        <Text size="small" color="surface.text.gray.muted">
          Showing {currentPage} of {totalPages} pages
        </Text>
      </Box>
      <Box display="flex" flexDirection="row" alignItems="center">
        <Dropdown selectionType="single" marginRight="spacing.4">
          <SelectInput
            accessibilityLabel="Select page size"
            onChange={({ values }) => changePageSize(values[0])}
            value={`${count}`}
          />
          <DropdownOverlay>
            <ActionList>
              <ActionListItem title="10" value="10" />
              <ActionListItem title="15" value="15" />
              <ActionListItem title="20" value="20" />
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
        <Text marginRight="spacing.3">rows / page</Text>
        <Box display="flex" flexDirection="row" gap="spacing.3">
          <Button icon={ChevronLeftIcon} onClick={prev} isDisabled={skip === 0}></Button>
          <Button
            icon={ChevronRightIcon}
            onClick={next}
            isDisabled={currentPage === totalPages}
          ></Button>
        </Box>
      </Box>
    </Box>
  );
};

export default Pagination;
