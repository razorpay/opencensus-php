import React, { useState, useRef } from 'react';
import { Box, Button, SearchIcon, TextInput } from '@razorpay/blade/components';
import moment from 'moment';
import { withRouter } from 'common/deprecated/withRouter';

import { CountryCodeInput } from 'common/components/CountryCodeInput';
import Dropdown from 'common/components/Dropdown';
import { Option } from 'common/components/Dropdown/types';
import { useMobile } from 'common/hooks/useMobile';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import {
  TransactionsEntityRoute,
  CUSTOM,
  SearchQueryParam,
  mobileBreakoints,
  MOBILE_CALENDAR_NUMBER_OF_MONTHS,
  DESKTOP_CALENDAR_NUMBER_OF_MONTHS,
  LAST_7_DAYS,
} from 'merchant/views/Transactions/v2/common/constants';
import {
  StyledDateRangePicker,
  StyledListFilter,
  StyledSearchByFilter,
  StyledSubListFilter,
} from 'merchant/views/Transactions/v2/common/styled';
import {
  trackDurationFilter,
  trackMethodFilter,
  trackSearchButton,
  trackSearchByFilter,
  trackStatusFilter,
} from 'merchant/views/Transactions/v2/common/tracking';
import { Duration, DurationOption } from 'merchant/views/Transactions/v2/common/types';
import { endOfDay, getFromTime, getValue } from 'merchant/views/Transactions/v2/common/utils';

import {
  paymentDurationSectionName,
  paymentMethodSectionName,
  statusSectionName,
  searchBySectionName,
  searchByOptionsMap,
} from './constants';
import { PaymentsListFilterProps } from './types';
import { getDefaultValuesAndOptions, getOptions } from './utils';

const { FAILED_PAYMENTS } = TransactionsEntityRoute;

const DateRangePicker = lazy(
  () => import(/* webpackChunkName: 'DateRangePicker' */ 'common/ui/Forms/DateRangePickerField'),
);

const PaymentsListFilter = ({
  onSubmit,
  loading,
  location: { pathname },
}: PaymentsListFilterProps): JSX.Element => {
  const {
    defaultPaymentDuration,
    defaultDate,
    defaultMethodValue,
    defaultMethodOption,
    defaultStatusValue,
    defaultStatusOption,
    defaultSearchByOption,
    defaultSearchByValue,
    defaultCountryCodeValue,
  } = getDefaultValuesAndOptions();
  const [date, setDate] = useState<Duration>(defaultDate);
  const [shouldShowDateRangePicker, setShowDateRangePicker] = useState(
    defaultPaymentDuration.value === CUSTOM,
  );
  const [status, setStatus] = useState(defaultStatusValue);
  const [method, setMethod] = useState(defaultMethodValue);
  const [searchBy, setSearchBy] = useState(defaultSearchByOption.value);
  const [searchByValue, setSearchByValue] = useState(defaultSearchByValue);
  const [countryCode, setCountryCode] = useState(defaultCountryCodeValue);
  const isContactSearch = searchBy === SearchQueryParam.CONTACT;
  const isMobile = useMobile();
  const isMediumDesktopAndMobile = useMobile(mobileBreakoints);
  const defaultFocusedInput = useRef<'startDate' | null>(null);
  const { paymentDurationOptions, paymentMethodOptions, statusOptions, searchByOptions } =
    getOptions(isMobile);
  const numberOfMonths = isMediumDesktopAndMobile
    ? MOBILE_CALENDAR_NUMBER_OF_MONTHS
    : DESKTOP_CALENDAR_NUMBER_OF_MONTHS;
  const shouldShowStatus = pathname !== FAILED_PAYMENTS;

  const handleSearch = (newSearchParams = {}) => {
    const newCountryCode = isContactSearch ? countryCode : '';
    const searchParams = {
      ...date,
      status,
      method,
      country_code: newCountryCode,
      ...newSearchParams,
    };
    if (searchByValue) {
      searchParams[searchBy] = searchByValue;
    }
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
    const from = getFromTime(value as DurationOption['value']).unix();
    const to = endOfDay.unix();
    setDate({
      from,
      to,
    });
    handleSearch({ from, to });
  };

  const onPaymentMethodChange = (selectedPaymentMethods: Option[]) => {
    const method = getValue(selectedPaymentMethods);
    setMethod(method);
    handleSearch({ method });
    trackMethodFilter({
      paymentMethodSelected: selectedPaymentMethods[0].title,
      pathname,
    });
  };

  const onStatusChange = (selectedStatuses: Option[]) => {
    const status = getValue(selectedStatuses);
    setStatus(status);
    handleSearch({ status });
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

  const onCountryCodeChange = ({ dialCode }: { dialCode: string }) => {
    setCountryCode(dialCode);
  };

  const onSearch = () => {
    handleSearch();
    const _countryCode = isContactSearch ? countryCode : undefined;
    trackSearchButton({
      searchBy: searchByOptionsMap[searchBy],
      searchByValue,
      pathname,
      countryCode: _countryCode,
    });
  };

  return (
    <StyledListFilter data-testid="payments-filter">
      <StyledSubListFilter className="scrollable-tab-header">
        <StyledDateRangePicker>
          <Dropdown
            onChange={onDurationChange}
            options={paymentDurationOptions}
            defaultOptions={[defaultPaymentDuration]}
            isDisabled={loading}
            bottomSheetTitle={paymentDurationSectionName}
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
        {shouldShowStatus ? (
          <Dropdown
            onChange={onStatusChange}
            options={statusOptions}
            defaultOptions={[defaultStatusOption]}
            prefixTitle="Status: "
            isDisabled={loading}
            bottomSheetTitle={statusSectionName}
          />
        ) : null}
        <Dropdown
          onChange={onPaymentMethodChange}
          options={paymentMethodOptions}
          defaultOptions={[defaultMethodOption]}
          prefixTitle="Payment method: "
          isDisabled={loading}
          bottomSheetTitle={paymentMethodSectionName}
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
          {isContactSearch ? (
            <CountryCodeInput
              onChange={onCountryCodeChange}
              dialCode={defaultCountryCodeValue}
              showContactInput={false}
            />
          ) : null}
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

export default withRouter(PaymentsListFilter);
