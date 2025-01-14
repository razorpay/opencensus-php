import React, { useEffect, useState } from 'react';
import { Box, Link, RefreshIcon, Text } from '@razorpay/blade/components';
import { CurrencyCodeType } from '@razorpay/i18nify-js/currency';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import Dropdown from 'common/components/Dropdown';
import { Option } from 'common/components/Dropdown/types';
import { useMobile } from 'common/hooks/useMobile';
import {
  fetchSchedule as fetchScheduleFn,
  fetchHolidayList as fetchHolidayListFn,
  fetchSettlementConfig as fetchSettlementConfigFn,
} from 'merchant/reducers/settlements/details';
import OverviewContainer from 'merchant/views/Transactions/v2/Analytics/components/OverviewContainer';
import {
  usePaymentsData,
  useDisputesData,
  useFailedPaymentsData,
  useSuccessRateData,
} from 'merchant/views/Transactions/v2/Analytics/hooks';
import {
  BottomOverviewProps,
  EnvironmentsModes,
  LandingAnalyticsProps,
  PaymentTypes,
  RefetchData,
  RefetchDataTypes,
  TopOverviewContainerProps,
} from 'merchant/views/Transactions/v2/Analytics/types';
import { getOptions, isSrEnabledForUser } from 'merchant/views/Transactions/v2/Analytics/utils';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { trackOverviewDuration } from 'merchant/views/Transactions/v2/common/tracking';
import { Duration, DurationOption } from 'merchant/views/Transactions/v2/common/types';
import { endOfDay, getFromTime } from 'merchant/views/Transactions/v2/common/utils';

import BottomOverview from './BottomOverview';
import DowntimeBanner from './DowntimeBanner';
import TopOverviewContainer from './TopOverview';
import DocsLink from 'merchant/components/DocsLink';

const LandingAnalytics = ({
  mode,
  user,
  fetchSettlementConfig,
  fetchSchedule,
  fetchHolidayList,
}: LandingAnalyticsProps): JSX.Element => {
  const isRefundPendingEnabled = user.isRefundPendingStatusEnabled;
  const currency = user.merchant?.currency as CurrencyCodeType;
  const {
    fetchPaymentData,
    paymentsData: {
      paymentByMethod,
      paymentCapturedAmount,
      paymentCapturedCount,
      refundAmount,
      refundCount,
    },
    loading: isPaymentsDataLoading,
    failed: isPaymentsDataFailed,
  } = usePaymentsData({ isRefundPendingEnabled, currency });
  const {
    fetchDisputesData,
    disputeData: {
      openDisputes: { count: openDisputesCount },
      totalDisputeAmount,
      underReviewDisputes: { count: underReviewDisputesCount },
    },
    loading: isDisputeDataLoading,
    failed: isDisputeDataFailed,
  } = useDisputesData({
    mode,
  });
  const {
    fetchFailedPaymentsData,
    failedPaymentsData,
    loading: isFailedPaymentsDataLoading,
    failed: isFailedPaymentsDataFailed,
  } = useFailedPaymentsData();
  const { successRateData, fetchSuccessRateData, loading: isSRLoading } = useSuccessRateData();

  const isSrEnabled = isSrEnabledForUser({
    mode,
    user,
  });
  const isMobile = useMobile(mobileBreakoints);
  const { defaultDate, defaultDuration, durationOptions } = getOptions(isMobile);
  const [duration, setDateDuration] = useState<Duration>(defaultDate);
  const [durationOption, setDurationOption] = useState<Option>(defaultDuration);

  const refetchData = (type: RefetchData) => {
    // these APIs don't work in test mode
    if (
      [RefetchDataTypes.Payments, PaymentTypes.Refunds, PaymentTypes.Failed].includes(type) &&
      mode === EnvironmentsModes.TEST
    )
      return;
    switch (type) {
      case RefetchDataTypes.Payments:
      case PaymentTypes.Refunds:
        fetchPaymentData(duration);
        break;
      case PaymentTypes.Disputes:
        fetchDisputesData(duration);
        break;
      case PaymentTypes.Failed:
        fetchFailedPaymentsData(duration);
        break;
      case RefetchDataTypes.All: {
        if (mode === EnvironmentsModes.LIVE) {
          fetchPaymentData(duration);
          fetchFailedPaymentsData(duration);
          if (isSrEnabled) fetchSuccessRateData(duration);
        }
        fetchDisputesData(duration);
        break;
      }
      /* istanbul ignore next */
      default:
        break;
    }
  };

  const refetchFailedApi = () => {
    if (isPaymentsDataFailed) refetchData(RefetchDataTypes.Payments);
    if (isDisputeDataFailed) refetchData(PaymentTypes.Disputes);
    if (isFailedPaymentsDataFailed) refetchData(PaymentTypes.Failed);
  };

  const onDurationChange = ([{ title, value }]: Option[]) => {
    const from = getFromTime(value as DurationOption['value']).unix();
    const to = endOfDay.unix();
    const updatedDuration = {
      from,
      to,
    };
    setDurationOption({ title, value });
    setDateDuration(updatedDuration);
    trackOverviewDuration(title);
  };

  const bottomOverviewProps: BottomOverviewProps = {
    durationOption,
    mode,
    currency,
    data: {
      refund: {
        amount: refundAmount,
        count: refundCount,
        loading: isPaymentsDataLoading,
        failed: isPaymentsDataFailed,
      },
      disputes: {
        amount: totalDisputeAmount,
        open: openDisputesCount,
        underReview: underReviewDisputesCount,
        loading: isDisputeDataLoading,
        failed: isDisputeDataFailed,
      },
      failed: {
        amount: failedPaymentsData,
        loading: isFailedPaymentsDataLoading,
        failed: isFailedPaymentsDataFailed,
      },
    },
  };

  const topOverviewProps: TopOverviewContainerProps = {
    durationOption,
    isPaymentsDataLoading,
    isPaymentsDataFailed,
    paymentCapturedAmount,
    paymentCapturedCount,
    paymentByMethod,
    isMobile,
    currency,
    successRateData,
    shouldShowSrBanner: isSrEnabled && !isSRLoading,
  };

  const isLoading = isPaymentsDataLoading || isDisputeDataLoading || isFailedPaymentsDataLoading;
  const isFetchFailed = isPaymentsDataFailed || isDisputeDataFailed || isFailedPaymentsDataFailed;

  useEffect(() => {
    refetchData(RefetchDataTypes.All);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [duration]);

  useEffect(() => {
    fetchSettlementConfig();
    fetchSchedule();
    fetchHolidayList();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <Box display="flex" flexDirection="column" gap="spacing.3">
      <DowntimeBanner />
      <OverviewContainer>
        <Box
          display="flex"
          flexDirection="row"
          alignItems="center"
          gap="spacing.2"
          justifyContent="space-between"
        >
          <Box display="flex" flexDirection="row" gap="spacing.2">
            <Text weight="semibold" color="surface.text.gray.normal">
              Overview
            </Text>
            <Dropdown
              onChange={onDurationChange}
              options={durationOptions}
              defaultOptions={[defaultDuration]}
              isDisabled={isLoading}
              bottomSheetTitle="Duration"
              isLink={true}
            />
          </Box>
          {isFetchFailed ? (
            <Link icon={RefreshIcon} variant="button" onClick={refetchFailedApi}>
              Refresh
            </Link>
          ) : null}
          <DocsLink url="DASHBOARD_TRANSACTION_URL_DOC" shouldUseBladeLink />
        </Box>
        <TopOverviewContainer {...topOverviewProps} />
        <BottomOverview {...bottomOverviewProps} />
      </OverviewContainer>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchHolidayList: fetchHolidayListFn,
      fetchSchedule: fetchScheduleFn,
      fetchSettlementConfig: fetchSettlementConfigFn,
    },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps)(LandingAnalytics));
