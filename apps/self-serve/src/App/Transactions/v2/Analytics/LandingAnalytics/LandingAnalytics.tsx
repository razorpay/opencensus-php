import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators, compose } from 'redux';
import { Box, Link, RefreshIcon, Text } from '@razorpay/blade/components';
import Dropdown from '@dashboard/shared-ui/components/Dropdown';
import { Option } from '@dashboard/shared-ui/components/Dropdown/types';
import {
  fetchSchedule as fetchScheduleFn,
  fetchHolidayList as fetchHolidayListFn,
  fetchSettlementConfig as fetchSettlementConfigFn,
} from '@dashboard/shared-utils/reducers/settlements';
import { useStore } from 'shell/commonStore';
import { useMobile } from '@dashboard/shared-ui/hooks';
import TopOverviewContainer from './TopOverview';
import BottomOverview from './BottomOverview';
import OverviewContainer from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/OverviewContainer';
import {
  usePaymentsData,
  useDisputesData,
  useFailedPaymentsData,
  useSuccessRateData,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/hooks';
import { endOfDay, getFromTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { Duration, DurationOption } from 'apps/self-serve/src/App/Transactions/v2/common/types';
import {
  getOptions,
  isSrEnabledForUser,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/utils';
import { mobileBreakoints } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import {
  BottomOverviewProps,
  EnvironmentsModes,
  PaymentTypes,
  RefetchData,
  RefetchDataTypes,
  TopOverviewContainerProps,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import { Currency } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import { trackOverviewDuration } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';

const LandingAnalytics = (): JSX.Element => {
  const session = useStore((state) => state.session);
  const mode = session.mode;
  const user = session.user;

  const isRefundPendingEnabled = user.isRefundPendingStatusEnabled;
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
  } = usePaymentsData({ isRefundPendingEnabled });
  const {
    fetchDisputesData,
    disputeData: { openDisputesCount, totalDisputeAmount, underReviewDisputesCount },
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
  const currency = user.merchant?.currency as Currency;
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
    // TODO: Commenting thiese call as this moved to shell wrapper
    // Moved to Transactions/SelfServeStateWrapper.tsx
    // fetchSettlementConfig();
    // fetchSchedule();
    // fetchHolidayList();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <OverviewContainer>
      <Box
        display="flex"
        flexDirection="row"
        alignItems="center"
        gap="spacing.2"
        justifyContent="space-between"
      >
        <Box display="flex" flexDirection="row" gap="spacing.2">
          <Text type="normal" weight="bold" contrast="low">
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
      </Box>
      <TopOverviewContainer {...topOverviewProps} />
      <BottomOverview {...bottomOverviewProps} />
    </OverviewContainer>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) => {
  return bindActionCreators(
    {
      fetchHolidayList: fetchHolidayListFn,
      fetchSchedule: fetchScheduleFn,
      fetchSettlementConfig: fetchSettlementConfigFn,
    },
    dispatch,
  );
};

export default compose(connect(null, mapDispatchToProps)(LandingAnalytics));
