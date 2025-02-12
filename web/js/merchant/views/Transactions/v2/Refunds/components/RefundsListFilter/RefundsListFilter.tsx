import React, { useRef, useState } from 'react';
import { Box, Button, SearchIcon, TextInput } from '@razorpay/blade/components';
import { compose } from '@reduxjs/toolkit';
import moment from 'moment';
import { connect } from 'react-redux';

import Dropdown from 'common/components/Dropdown';
import { Option } from 'common/components/Dropdown/types';
import { withRouter } from 'common/deprecated/withRouter';
import { useMobile } from 'common/hooks/useMobile';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ProviderSelector from 'merchant/components/ProviderSelector';
import lazy from 'merchant/routes/LazyLoader';
import { isOmniChannelMerchant as _isOmniChannelMerchant } from 'merchant/utils/omniUtils';
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
  trackProviderFilter,
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
  channelSectionName,
} from './constants';
import { Duration, RefundsListFilterProps } from './types';
import { getDefaultValuesAndOptions, getOptions } from './utils';

const DateRangePicker = lazy(
  () => import(/* webpackChunkName: 'DateRangePicker' */ 'common/ui/Forms/DateRangePickerField'),
);

const RefundsListFilter = ({
  onSubmit,
  loading,
  user,
  location: { pathname },
  terminalProviders,
}: RefundsListFilterProps): JSX.Element => {
  const {
    defaultRefundsDuration,
    defaultDate,
    defaultStatusValue: status,
    defaultStatusOption,
    defaultSearchByOption,
    defaultSearchByValue,
    defaultChannelValue: channel,
    defaultChannelOption,
  } = getDefaultValuesAndOptions();
  const [date, setDate] = useState<Duration>(defaultDate);
  const [shouldShowDateRangePicker, setShowDateRangePicker] = useState(
    defaultRefundsDuration.value === CUSTOM,
  );
  const [searchBy, setSearchBy] = useState(defaultSearchByOption.value);
  const [terminalId, setTerminalId] = useState<string>();
  const [searchByValue, setSearchByValue] = useState(defaultSearchByValue);
  const isMobile = useMobile();
  const isMediumDesktopAndMobile = useMobile(mobileBreakoints);
  const defaultFocusedInput = useRef<'startDate' | null>(null);
  const { paymentChannelOptions, refundsDurationOptions, statusOptions, searchByOptions } =
    getOptions(isMobile);

  const numberOfMonths = isMediumDesktopAndMobile
    ? MOBILE_CALENDAR_NUMBER_OF_MONTHS
    : DESKTOP_CALENDAR_NUMBER_OF_MONTHS;
  const isOmniChannelMerchant = _isOmniChannelMerchant(user);

  const handleSearch = (newSearchParams = {}) => {
    const searchParams = {
      ...date,
      public_status: status,
      [terminalId === 'Razorpay' ? 'settled_by' : 'terminal_id']: terminalId,
      source_channel: channel,
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
    handleSearch({ public_status: status });
    trackStatusFilter({
      status: selectedStatuses[0].title,
      pathname,
    });
  };

  const onTerminalProviderChange = (terminalProviders: Option[]) => {
    const terminal_id = getValue(terminalProviders);
    handleSearch({
      [terminal_id === 'Razorpay' ? 'settled_by' : 'terminal_id']: terminal_id,
      [terminal_id === 'Razorpay' ? 'terminal_id' : 'settled_by']: '',
    });
    setTerminalId(terminal_id);
    trackProviderFilter({
      terminalProviderSelected: terminalProviders[0].title,
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
        {user.isSingleReconEnabled &&
          user.isOptimizerEnabled &&
          terminalProviders &&
          terminalProviders.length > 0 && (
            <ProviderSelector
              providers={terminalProviders}
              onChange={onTerminalProviderChange}
              isLoading={loading}
            />
          )}
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
