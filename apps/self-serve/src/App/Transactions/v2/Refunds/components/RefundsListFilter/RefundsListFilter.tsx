// TODO: Fix imports, currently its out of scope from phase 1;
// @ts-nocheck
import React, { useRef, useState } from 'react';
import { Box, Button, SearchIcon, TextInput } from '@razorpay/blade/components';
import moment from 'moment';
import { withRouter } from 'shell/deprecated/withRouter';

import Dropdown from '@dashboard/shared-ui/components/Dropdown';
import { Option } from '@dashboard/shared-ui/components/Dropdown/types';
import { useMobile } from '@dashboard/shared-ui/hooks';
import { SuspenseWithLoader } from '@dashboard/shared-ui/components';
import lazy from '@dashboard/shared-utils/routes/LazyLoader';
import {
  CUSTOM,
  DESKTOP_CALENDAR_NUMBER_OF_MONTHS,
  LAST_7_DAYS,
  MOBILE_CALENDAR_NUMBER_OF_MONTHS,
  mobileBreakoints,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import {
  StyledDateRangePicker,
  StyledListFilter,
  StyledSearchByFilter,
  StyledSubListFilter,
} from 'apps/self-serve/src/App/Transactions/v2/common/styled';
import {
  trackDurationFilter,
  trackSearchButton,
  trackSearchByFilter,
  trackStatusFilter,
} from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { DurationOption } from 'apps/self-serve/src/App/Transactions/v2/common/types';
import {
  endOfDay,
  getFromTime,
  getValue,
} from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { getDefaultValuesAndOptions, getOptions } from './utils';
import { Duration, RefundsListFilterProps } from './types';
import {
  refundsDurationSectionName,
  statusSectionName,
  searchBySectionName,
  searchByOptionsMap,
  channelSectionName,
} from './constants';

// eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
const DateRangePicker = lazy(
  // eslint-disable-next-line import/no-unresolved
  () => import(/* webpackChunkName: 'DateRangePicker' */ 'common/ui/Forms/DateRangePickerField'),
);

const RefundsListFilter = ({
  onSubmit,
  loading,
  user,
  location: { pathname },
}: RefundsListFilterProps): JSX.Element => {
  const {
    defaultRefundsDuration,
    defaultDate,
    defaultStatusValue: status,
    defaultStatusOption,
    defaultSearchByOption,
    defaultSearchByValue,
    defaultChannelOption,
    defaultChannelValue: channel,
  } = getDefaultValuesAndOptions();
  const [date, setDate] = useState<Duration>(defaultDate);
  const [shouldShowDateRangePicker, setShowDateRangePicker] = useState(
    defaultRefundsDuration.value === CUSTOM,
  );

  const [searchBy, setSearchBy] = useState(defaultSearchByOption.value);
  const [searchByValue, setSearchByValue] = useState(defaultSearchByValue);
  const isMobile = useMobile();
  const isMediumDesktopAndMobile = useMobile(mobileBreakoints);
  const defaultFocusedInput = useRef<'startDate' | null>(null);
  const { paymentChannelOptions, refundsDurationOptions, statusOptions, searchByOptions } =
    getOptions(isMobile);

  const numberOfMonths = isMediumDesktopAndMobile
    ? MOBILE_CALENDAR_NUMBER_OF_MONTHS
    : DESKTOP_CALENDAR_NUMBER_OF_MONTHS;

  const isOmniChannelMerchant =
    user.isOmniEnabledMerchant || (!!user?.pos_activation_status && user?.isOmniChannelMerchant);

  const handleSearch = (newSearchParams = {}) => {
    const searchParams = {
      ...date,
      public_status: status,
      source_channel: channel,
      [searchBy]: searchByValue,
      ...newSearchParams,
    };
    onSubmit(searchParams);
  };

  const onDatesChange = ({ from, to }: { from: number; to: number }) => {
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
    handleSearch({ public_status: status });
    trackStatusFilter({
      status: selectedStatuses[0].title,
      pathname,
    });
  };

  const onChannelChange = (selectedChannel: Option[]): void => {
    const channel = getValue(selectedChannel);
    handleSearch({ source_channel: channel });
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
      searchBy: searchByOptionsMap[searchBy as keyof typeof searchByOptionsMap],
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
        {isOmniChannelMerchant ? (
          <Dropdown
            onChange={onChannelChange}
            options={paymentChannelOptions}
            defaultOptions={[defaultChannelOption]}
            prefixTitle="Channel: "
            isDisabled={loading}
            bottomSheetTitle={channelSectionName}
          />
        ) : null}
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
            testID="search-by-dropdown"
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
const mapStateToProps = (state) => ({
  user: state.session.user,
});
export default withRouter<any>(compose(connect(mapStateToProps, null)(RefundsListFilter)));
