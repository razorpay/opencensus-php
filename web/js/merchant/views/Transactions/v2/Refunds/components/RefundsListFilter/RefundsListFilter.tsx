import React, { useRef, useState } from 'react';
import { Box, Button, SearchIcon, TextInput } from '@razorpay/blade/components';
import moment from 'moment';
import { withRouter } from 'react-router-dom';

import Dropdown from 'common/components/Dropdown';
import { Option } from 'common/components/Dropdown/types';
import { useMobile } from 'common/hooks/useMobile';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import {
  CUSTOM,
  DESKTOP_CALENDAR_NUMBER_OF_MONTHS,
  LAST_7_DAYS,
  MOBILE_CALENDAR_NUMBER_OF_MONTHS,
  mobileBreakoints,
} from 'merchant/views/Transactions/v2/common/constants';
import {
  StyledDateRangePicker,
  StyledListFilter,
  StyledSearchByFilter,
  StyledSubListFilter,
} from 'merchant/views/Transactions/v2/common/styled';
import {
  trackDurationFilter,
  trackSearchButton,
  trackSearchByFilter,
  trackStatusFilter,
} from 'merchant/views/Transactions/v2/common/tracking';
import { DurationOption } from 'merchant/views/Transactions/v2/common/types';
import { endOfDay, getFromTime, getValue } from 'merchant/views/Transactions/v2/common/utils';

import {
  refundsDurationSectionName,
  statusSectionName,
  searchBySectionName,
  searchByOptionsMap,
} from './constants';
import { Duration, RefundsListFilterProps } from './types';
import { getDefaultValuesAndOptions, getOptions } from './utils';

const DateRangePicker = lazy(
  () => import(/* webpackChunkName: 'DateRangePicker' */ 'common/ui/Forms/DateRangePickerField'),
);

const RefundsListFilter = ({
  onSubmit,
  loading,
  location: { pathname },
}: RefundsListFilterProps): JSX.Element => {
  const {
    defaultRefundsDuration,
    defaultDate,
    defaultStatusValue,
    defaultStatusOption,
    defaultSearchByOption,
    defaultSearchByValue,
  } = getDefaultValuesAndOptions();
  const [date, setDate] = useState<Duration>(defaultDate);
  const [shouldShowDateRangePicker, setShowDateRangePicker] = useState(
    defaultRefundsDuration.value === CUSTOM,
  );
  const [status, setStatus] = useState<string>(defaultStatusValue);
  const [searchBy, setSearchBy] = useState(defaultSearchByOption.value);
  const [searchByValue, setSearchByValue] = useState(defaultSearchByValue);
  const isMobile = useMobile();
  const isMediumDesktopAndMobile = useMobile(mobileBreakoints);
  const defaultFocusedInput = useRef<'startDate' | null>(null);
  const { refundsDurationOptions, statusOptions, searchByOptions } = getOptions(isMobile);
  const numberOfMonths = isMediumDesktopAndMobile
    ? MOBILE_CALENDAR_NUMBER_OF_MONTHS
    : DESKTOP_CALENDAR_NUMBER_OF_MONTHS;

  const handleSearch = (newSearchParams = {}) => {
    const searchParams = {
      ...date,
      public_status: status,
      [searchBy]: searchByValue,
      ...newSearchParams,
    };
    onSubmit(searchParams);
  };

  const onDatesChange = ({ from, to }) => {
    setDate({
      from: moment.unix(from).startOf('day').unix(),
      to: moment.unix(to).endOf('day').unix(),
    });
    handleSearch({ from, to });
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
    const from: number = getFromTime(value as DurationOption['value']).unix();
    const to: number = endOfDay.unix();
    setDate({
      from,
      to,
    });
    handleSearch({ from, to });
  };

  const onStatusChange = (selectedStatuses: Option[]) => {
    const status = getValue(selectedStatuses);
    setStatus(status);
    handleSearch({ public_status: status });
    trackStatusFilter({
      status: selectedStatuses[0].title,
      pathname,
    });
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
    <StyledListFilter>
      <StyledSubListFilter className="scrollable-tab-header">
        <StyledDateRangePicker>
          <Dropdown
            onChange={onDurationChange}
            options={refundsDurationOptions}
            defaultOptions={[defaultRefundsDuration]}
            isDisabled={loading}
            bottomSheetTitle={refundsDurationSectionName}
          />
          {shouldShowDateRangePicker && date.from && date.to ? (
            <div className="rzp-daterange-picker">
              <div className="daterange-container">
                <SuspenseWithLoader>
                  <DateRangePicker
                    onDatesChange={onDatesChange}
                    startDate={moment.unix(date.from)}
                    endDate={moment.unix(date.to)}
                    disabled={loading}
                    numberOfMonths={numberOfMonths}
                    withPortal={isMobile}
                    defaultFocusedInput={defaultFocusedInput.current}
                  />
                </SuspenseWithLoader>
              </div>
            </div>
          ) : null}
        </StyledDateRangePicker>
        <Dropdown
          onChange={onStatusChange}
          options={statusOptions}
          defaultOptions={[defaultStatusOption]}
          prefixTitle="Status: "
          isDisabled={loading}
          bottomSheetTitle={statusSectionName}
        />
      </StyledSubListFilter>
      <StyledSearchByFilter>
        <Box display="flex" columnGap="spacing.1" marginLeft="auto">
          <Dropdown
            onChange={onSearchByOptionChange}
            options={searchByOptions}
            defaultOptions={[defaultSearchByOption]}
            isDisabled={loading}
            isSelectInput
            bottomSheetTitle={searchBySectionName}
          />
          <TextInput
            showClearButton
            label=""
            defaultValue={defaultSearchByValue}
            placeholder="Search"
            onChange={onSearchByValueChange}
            onClearButtonClick={() => onSearchByValueChange({})}
          />
          <Button accessibilityLabel="Search" icon={SearchIcon} size="medium" onClick={onSearch} />
        </Box>
      </StyledSearchByFilter>
    </StyledListFilter>
  );
};

export default withRouter(RefundsListFilter);
