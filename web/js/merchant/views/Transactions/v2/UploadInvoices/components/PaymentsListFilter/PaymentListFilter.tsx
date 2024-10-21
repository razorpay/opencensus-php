import React, { useState, useRef } from 'react';
import { Box, Button, SearchIcon, TextInput, DatePicker } from '@razorpay/blade/components';
import moment from 'moment';

import Dropdown from 'common/components/Dropdown';
import { Option } from 'common/components/Dropdown/types';
import { withRouter } from 'common/deprecated/withRouter';
import { CUSTOM, LAST_7_DAYS } from 'merchant/views/Transactions/v2/common/constants';
import { StyledSearchByFilter } from 'merchant/views/Transactions/v2/common/styled';
import {
  trackDurationFilter,
  trackSearchButton,
  trackSearchByFilter,
} from 'merchant/views/Transactions/v2/common/tracking';
import { Duration, DurationOption } from 'merchant/views/Transactions/v2/common/types';
import { endOfDay, getFromTime } from 'merchant/views/Transactions/v2/common/utils';

import { paymentDurationSectionName, searchBySectionName, searchByOptionsMap } from './constants';
import { PaymentsListFilterProps } from './types';
import { getDefaultValuesAndOptions, getOptions } from './utils';

const PaymentsListFilter = ({
  onSubmit,
  loading,
  location: { pathname },
}: PaymentsListFilterProps): JSX.Element => {
  const { defaultPaymentDuration, defaultDate, defaultSearchByOption, defaultSearchByValue } =
    getDefaultValuesAndOptions();
  const [date, setDate] = useState<Duration>(defaultDate);
  const [shouldShowDateRangePicker, setShowDateRangePicker] = useState(
    defaultPaymentDuration.value === CUSTOM,
  );
  const [searchBy, setSearchBy] = useState(defaultSearchByOption.value);
  const [searchByValue, setSearchByValue] = useState(defaultSearchByValue);
  const defaultFocusedInput = useRef<'startDate' | null>(null);
  const { paymentDurationOptions, searchByOptions } = getOptions();

  const handleSearch = (newSearchParams = {}) => {
    const searchParams = {
      ...date,
      status,
      ...newSearchParams,
    };
    if (searchByValue) {
      searchParams[searchBy] = searchByValue;
    }
    onSubmit(searchParams);
  };

  const onDatesChange = (range) => {
    if (range?.[0] && range?.[1]) {
      const from = new Date(range?.[0]).getTime() / 1000;
      const to = new Date(range?.[1]).getTime() / 1000;
      setDate({
        from: moment.unix(from).startOf('day').unix(),
        to: moment.unix(to).endOf('day').unix(),
      });
      handleSearch({ from, to });
    }
  };

  const onDurationChange = ([{ title, value }]: Option[]) => {
    trackDurationFilter({ dateRange: title, pathname });
    if (value === CUSTOM) {
      defaultFocusedInput.current = 'startDate';
      setDate({
        from: getFromTime(LAST_7_DAYS).unix(),
        to: endOfDay.unix(),
      });
      setShowDateRangePicker(true);
      return;
    }
    setShowDateRangePicker(false);
    const from = getFromTime(value as DurationOption['value']).unix();
    const to = endOfDay.unix();
    setDate({
      from,
      to,
    });
    handleSearch({ from, to });
  };

  const onSearchByOptionChange = ([{ title, value }]: Option[]) => {
    setSearchBy(value);
    trackSearchByFilter({
      searchBy: title,
      pathname,
    });
  };

  const onSearchByValueChange = ({ value = '' }: { value?: string }) => {
    setSearchByValue(value);
  };

  const onSearch = () => {
    handleSearch();
    trackSearchButton({
      searchBy: searchByOptionsMap[searchBy],
      searchByValue,
      pathname,
    });
  };

  return (
    <Box
      padding="spacing.4"
      display="flex"
      flexDirection={{ base: 'column', m: 'row' }}
      justifyContent="space-between"
      alignItems="center"
      backgroundColor="surface.background.gray.intense"
    >
      <Box display="flex" flexDirection={{ base: 'column', m: 'row' }} testID="payments-filter">
        <StyledSearchByFilter>
          <Box display="flex" columnGap="spacing.1" marginLeft="auto">
            <Dropdown
              onChange={onSearchByOptionChange}
              options={searchByOptions}
              defaultOptions={[defaultSearchByOption]}
              isDisabled={loading}
              bottomSheetTitle={searchBySectionName}
              testID="search-by-dropdown"
            />
            <Box>
              <TextInput
                showClearButton
                label=""
                defaultValue={defaultSearchByValue}
                placeholder="Search"
                onChange={onSearchByValueChange}
                onClearButtonClick={() => onSearchByValueChange({})}
              />
            </Box>
            <Box
              paddingLeft={{ base: 'spacing.1', m: 'spacing.0' }}
              paddingRight={{ base: 'spacing.1', m: 'spacing.0' }}
            >
              <Button
                accessibilityLabel="Search"
                icon={SearchIcon}
                size="medium"
                onClick={onSearch}
              />
            </Box>
          </Box>
        </StyledSearchByFilter>
        <Box
          display="flex"
          flexDirection={{ base: 'column', m: 'row' }}
          marginLeft={{ base: 'spacing.0', m: 'spacing.4' }}
          marginTop={{ base: 'spacing.2', m: 'spacing.0' }}
        >
          <Dropdown
            onChange={onDurationChange}
            options={paymentDurationOptions}
            defaultOptions={[defaultPaymentDuration]}
            isDisabled={loading}
            bottomSheetTitle={paymentDurationSectionName}
            marginBottom={{ base: 'spacing.2', m: 'spacing.0' }}
          />
          {shouldShowDateRangePicker && date.from && date.to ? (
            <DatePicker
              defaultValue={[new Date(date.from * 1000), new Date(date.to * 1000)]}
              zIndex="10000"
              onChange={onDatesChange}
              // eslint-disable-next-line @typescript-eslint/ban-ts-comment
              // @ts-ignore
              selectionType="range"
            />
          ) : null}
        </Box>
      </Box>
    </Box>
  );
};

export default withRouter<any>(PaymentsListFilter);
