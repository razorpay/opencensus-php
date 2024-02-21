import React, { useState, useRef } from 'react';
import {
  Box,
  Button,
  Divider,
  FilterIcon,
  SearchIcon,
  Tag,
  Text,
  TextInput,
} from '@razorpay/blade/components';
import moment from 'moment';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { openModal } from 'merchant_common/reducers/modals';
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

const ExtraFiltersModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'ExtraFiltersModal' */ 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/ExtraFiltersModal'
    ),
);

const PaymentsListFilter = ({
  onSubmit,
  loading,
  user,
  location: { pathname },
  openModal,
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
    defaultChannelOption,
    defaultChannelValue,
  } = getDefaultValuesAndOptions();
  const [date, setDate] = useState<Duration>(defaultDate);
  const [shouldShowDateRangePicker, setShowDateRangePicker] = useState(
    defaultPaymentDuration.value === CUSTOM,
  );
  const [status, setStatus] = useState(defaultStatusValue);
  const [searchBy, setSearchBy] = useState(defaultSearchByOption.value);
  const [searchByValue, setSearchByValue] = useState(defaultSearchByValue);
  const [countryCode, setCountryCode] = useState(defaultCountryCodeValue);
  const method = defaultMethodValue;
  const channel = defaultChannelValue;
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
  const isOmniChannelMerchant =
    user.isOmniEnabledMerchant || (!!user?.pos_activation_status && user?.isOmniChannelMerchant);

  const handleSearch = (newSearchParams = {}) => {
    const newCountryCode = isContactSearch ? countryCode : '';
    const searchParams = {
      ...date,
      status,
      method,
      country_code: newCountryCode,
      source_channel: channel,
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

  const openExtraFiltersModal = () => {
    const props = {
      handleSearch,
    };
    openModal({
      size: 'small',
      isNew: true,
      component: (
        <SuspenseWithLoader>
          <ExtraFiltersModal {...props} />
        </SuspenseWithLoader>
      ),
    });
  };
  const clearChannel = () => {
    handleSearch({ source_channel: '' });
  };

  const clearMethod = () => {
    handleSearch({ method: '' });
  };

  return (
    <Box marginBottom="spacing.5" display="flex" flexDirection="column">
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

          {isOmniChannelMerchant ? (
            <Divider marginRight="spacing.3" marginLeft="spacing.3" orientation="vertical" />
          ) : null}
          {isOmniChannelMerchant ? (
            <Box>
              <Button
                isDisabled={loading}
                variant="tertiary"
                icon={FilterIcon}
                onClick={openExtraFiltersModal}
              >
                All Filters
              </Button>
            </Box>
          ) : (
            <Dropdown
              onChange={onPaymentMethodChange}
              options={paymentMethodOptions}
              defaultOptions={[defaultMethodOption]}
              prefixTitle="Payment method: "
              isDisabled={loading}
              bottomSheetTitle={paymentMethodSectionName}
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
            <Button
              accessibilityLabel="Search"
              icon={SearchIcon}
              size="medium"
              onClick={onSearch}
            />
          </Box>
        </StyledSearchByFilter>
      </StyledListFilter>
      {isOmniChannelMerchant ? (
        <Box maxWidth="auto" display="flex" alignItems="center" flexDirection="row">
          {method ? (
            <Box display="flex" alignItems="center" flexDirection="row">
              <Text type="normal" color="surface.text.subtle.lowContrast">
                Payment Method:
              </Text>
              <Tag marginLeft="spacing.3" size="medium" onDismiss={clearMethod}>
                {defaultMethodOption?.title || method}
              </Tag>
            </Box>
          ) : null}
          {channel && method ? (
            <Divider marginRight="spacing.3" marginLeft="spacing.3" orientation="vertical" />
          ) : null}
          {channel ? (
            <Box display="flex" alignItems="center" flexDirection="row">
              <Text type="normal" color="surface.text.subtle.lowContrast">
                Channel:
              </Text>
              <Tag marginLeft="spacing.3" size="medium" onDismiss={clearChannel}>
                {defaultChannelOption?.title || channel}
              </Tag>
            </Box>
          ) : null}
        </Box>
      ) : null}
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
    },
    dispatch,
  );

export default withRouter<any>(
  compose(connect(mapStateToProps, mapDispatchToProps)(PaymentsListFilter)),
);
