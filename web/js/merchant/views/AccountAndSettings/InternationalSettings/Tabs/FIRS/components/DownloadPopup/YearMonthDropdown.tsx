import React from 'react';
import {
  Box,
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import {
  FIRS_START_YEAR,
  monthList,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import useFirsContext from 'merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext';
import {
  getListOfYears,
  isMonthValid,
} from 'merchant/views/AccountAndSettings/InternationalSettings/utils';
import { COMMON_Z_INDEX } from 'common/constant';
const YearMonthDropdown = (): React.ReactElement => {
  const { popupData, setPopupData, getFirsData } = useFirsContext();
  const { month, year } = popupData;

  const onYearChange = (event) => {
    const year = parseInt(event.values[0], 10);
    let callBack: () => void;
    if (!isMonthValid(month, year)) {
      callBack = () => setPopupData((prev) => ({ ...prev, year, month: monthList[0] }));
    } else {
      callBack = () => setPopupData((prev) => ({ ...prev, year }));
    }
    getFirsData(year, undefined, callBack);
  };

  const onMonthChange = (event) => {
    const month = event.values[0];
    setPopupData((prev) => ({ ...prev, month }));
  };

  return (
    <Box display="flex" flexDirection={{ base: 'row' }}>
      <Box width="172px" marginRight="spacing.5">
        <Dropdown>
          <SelectInput
            label="Year"
            name="year"
            placeholder="Select Year"
            value={year.toString()}
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

      <Box width="179px">
        <Dropdown>
          <SelectInput
            label="Month"
            name="month"
            placeholder="Select Month"
            value={month}
            onChange={onMonthChange}
          />
          <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
            <ActionList>
              {monthList
                .slice()
                .reverse()
                .map((month) => {
                  if (!isMonthValid(month, year)) return null;
                  return <ActionListItem key={month} title={month} value={month} />;
                })}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </Box>
    </Box>
  );
};

export default YearMonthDropdown;
