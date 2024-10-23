import React, { useEffect, useState } from 'react';
import { Box, Link, RefreshIcon, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import Dropdown from 'common/components/Dropdown/Dropdown';
import { Option } from 'common/components/Dropdown/types';
import { useMobile } from 'common/hooks/useMobile';
import LoadFailed from 'merchant/views/Transactions/v2/Analytics/components/LoadFailed';
import OverviewContainer from 'merchant/views/Transactions/v2/Analytics/components/OverviewContainer';
import SuccessRateBanner from 'merchant/views/Transactions/v2/Analytics/components/SuccessRateBanner';
import {
  useFailedPaymentsData,
  useSuccessRateData,
} from 'merchant/views/Transactions/v2/Analytics/hooks';
import {
  EnvironmentsModes,
  SuccessRateBannerSection,
} from 'merchant/views/Transactions/v2/Analytics/types';
import {
  getAnalyticsPropsForFailedPyaments,
  getOptions,
  isSrEnabledForUser,
} from 'merchant/views/Transactions/v2/Analytics/utils';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { trackOverviewDuration } from 'merchant/views/Transactions/v2/common/tracking';
import { Duration, DurationOption } from 'merchant/views/Transactions/v2/common/types';
import { endOfDay, getFromTime } from 'merchant/views/Transactions/v2/common/utils';

import AnalyticsBoilerPlate from './AnalyticsBoilerPlate';

const FailedPaymentsOverview = ({ mode, user, orgName }): JSX.Element => {
  const {
    failedPaymentsData,
    failureInfo,
    loading: isLoading,
    fetchFailedPaymentsData,
    failed: didFetchFailed,
  } = useFailedPaymentsData();
  const { successRateData, fetchSuccessRateData, loading: isSRLoading } = useSuccessRateData();
  const isSrEnabled = isSrEnabledForUser({
    mode,
    user,
  });
  const shouldShowSR = isSrEnabled && !isSRLoading;
  const isMobile = useMobile(mobileBreakoints);
  const { defaultDate, defaultDuration, durationOptions } = getOptions(isMobile);

  const [dateRange, setDateRange] = useState<Duration>(defaultDate);

  const onDurationChange = ([{ title, value }]: Option[]) => {
    const from = getFromTime(value as DurationOption['value']).unix();
    const to = endOfDay.unix();
    const dateConfig = {
      from,
      to,
    };
    setDateRange(dateConfig);
    trackOverviewDuration(title);
  };

  const refetchData = () => {
    // these APIs don't work in test mode
    if (mode === EnvironmentsModes.TEST) return;
    fetchFailedPaymentsData(dateRange);
    if (isSrEnabled) {
      fetchSuccessRateData(dateRange);
    }
  };
  useEffect(() => {
    refetchData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [dateRange]);

  return (
    <OverviewContainer>
      <Box
        display="flex"
        flexDirection="row"
        alignItems="center"
        gap="spacing.2"
        justifyContent="space-between"
      >
        <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.2">
          <Text weight="semibold" color="surface.text.gray.normal">
            Failed
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
        {didFetchFailed ? (
          <Link icon={RefreshIcon} variant="button" onClick={refetchData}>
            Refresh
          </Link>
        ) : null}
      </Box>
      {didFetchFailed ? (
        <LoadFailed
          title="We couldn't load the summary of your failed payments."
          subtitle="Refresh to try again"
          height={isMobile ? 120 : 150}
        />
      ) : (
        <AnalyticsBoilerPlate
          isLoading={isLoading}
          isMobile={isMobile}
          data={getAnalyticsPropsForFailedPyaments(failedPaymentsData, failureInfo, orgName)}
        />
      )}
      {shouldShowSR ? (
        <Box padding={['spacing.4', 'spacing.2']}>
          <SuccessRateBanner
            successRateData={successRateData}
            section={SuccessRateBannerSection.FAILED_PAYMENTS_OVERVIEW}
          />
        </Box>
      ) : null}
    </OverviewContainer>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
  user: state.session.user,
  orgName: state.session.org?.business_name,
});
export default connect(mapStateToProps)(FailedPaymentsOverview);
