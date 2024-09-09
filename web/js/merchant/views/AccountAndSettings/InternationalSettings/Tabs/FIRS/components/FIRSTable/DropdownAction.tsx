import React from 'react';
import {
  Box,
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';
import { COMMON_Z_INDEX } from 'common/constant';
import { FIRS_START_YEAR } from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import useFirsContext from 'merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext';
import { getListOfYears } from 'merchant/views/AccountAndSettings/InternationalSettings/utils';

const DropdownAction = (): React.ReactElement => {
  const { listYear, setListYear, getFirsData } = useFirsContext();

  const onYearChange = (event) => {
    const year = parseInt(event.values[0], 10);
    getFirsData(year, undefined, () => setListYear(year));
  };

  return (
    <Box
      width={{ base: '100%', m: 'fit-content' }}
      display="flex"
      flexDirection={{ base: 'row' }}
      alignItems={{ base: 'flex-end' }}
    >
      <Box
        width={{ base: '100%', m: '200px' }}
        marginRight="spacing.0"
        padding={{ base: '0px 20px', m: '0px' }}
      >
        <Dropdown>
          <SelectInput
            label="Year"
            name="year"
            placeholder="Select Year"
            value={listYear.toString()}
            onChange={onYearChange}
          />
          <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
            <ActionList>
              {getListOfYears(FIRS_START_YEAR).map(({ title, value }) => (
                <ActionListItem key={title} title={title} value={value} />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </Box>
    </Box>
  );
};

export default DropdownAction;
