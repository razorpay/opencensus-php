import React, { useEffect, useState } from 'react';
import { Box, Link, RefreshIcon, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import Dropdown from '@dashboard/shared-ui/components/Dropdown/Dropdown';
import { Option } from '@dashboard/shared-ui/components/Dropdown/types';
import { useMobile } from '@dashboard/shared-ui/hooks';
import LoadFailed from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/LoadFailed';
import OverviewContainer from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/OverviewContainer';
import { useRefundsData } from 'apps/self-serve/src/App/Transactions/v2/Analytics/hooks';
import {
  EnvironmentsModes,
  RefundsOverviewProps,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import {
  getAnalyticsPropsForRefunds,
  getOptions,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/utils';
import { mobileBreakoints } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { trackOverviewDuration } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { Duration, DurationOption } from 'apps/self-serve/src/App/Transactions/v2/common/types';
import { endOfDay, getFromTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import AnalyticsBoilerPlate from './AnalyticsBoilerPlate';

const RefundsOverview = ({ mode, user }: RefundsOverviewProps): JSX.Element => {
  const isRefundPendingEnabled = user.isRefundPendingStatusEnabled;
  const {
    fetchRefundData,
    loading: isLoading,
    refundsData,
    failed: didFetchFailed,
  } = useRefundsData({
    isRefundPendingEnabled,
  });
  const isMobile = useMobile(mobileBreakoints);
  const { defaultDate, defaultDuration, durationOptions } = getOptions(isMobile);

  const [duration, setDateDuration] = useState<Duration>(defaultDate);

  const onDurationChange = ([{ title, value }]: Option[]): void => {
    const from = getFromTime(value as DurationOption['value']).unix();
    const to = endOfDay.unix();
    const updatedDuration = {
      from,
      to,
    };
    setDateDuration(updatedDuration);
    trackOverviewDuration(title);
  };

  const refetchData = () => {
    // these APIs don't work in test mode
    if (mode === EnvironmentsModes.TEST) return;
    fetchRefundData(duration);
  };

  useEffect(() => {
    refetchData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [duration]);

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
            Refunds
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
          title="We couldn't load the summary of your refunds."
          subtitle="Refresh to try again"
          height={isMobile ? 120 : 150}
        />
      ) : (
        <AnalyticsBoilerPlate
          isLoading={isLoading}
          isMobile={isMobile}
          data={getAnalyticsPropsForRefunds(refundsData)}
        />
      )}
    </OverviewContainer>
  );
};

const mapStateToProps = (state: any) => ({
  mode: state.session.mode,
  user: state.session.user,
});
export default connect(mapStateToProps)(RefundsOverview);
