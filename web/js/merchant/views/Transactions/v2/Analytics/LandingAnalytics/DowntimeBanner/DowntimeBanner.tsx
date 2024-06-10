import React, { useContext, useMemo } from 'react';
import { Alert } from '@razorpay/blade/components';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import {
  EcosystemDowntimeProvider,
  EcosystemDowntimeContext,
} from 'merchant/views/EcosystemDowntimes/context';

import { getDowntimeBannerConfig } from './utils/downtime';

const DowntimeBannerAlert = (): JSX.Element | null => {
  const { state } = useContext(EcosystemDowntimeContext);
  const {
    activeDowntimes,
    isOngoingDowntimeLoading: isLoading,
    isOngoingDowntimeFetching: isFetching,
    isOngoingDowntimesError,
  } = state;

  const { shouldShowBanner, bannerMessage } = useMemo(
    () => getDowntimeBannerConfig({ activeDowntimes }),
    [activeDowntimes],
  );

  if (
    (isOngoingDowntimesError && !isFetching) ||
    isLoading ||
    isFetching ||
    !shouldShowBanner ||
    !bannerMessage
  ) {
    return null;
  }

  return (
    <Alert
      color="notice"
      description={bannerMessage}
      isDismissible={false}
      isFullWidth
      emphasis="intense"
      marginTop={{ base: 'spacing.4', m: 'spacing.0' }}
    />
  );
};

const DowntimeBanner = (): JSX.Element | null => {
  const splitz = useSplitzService();
  const shouldShowDowntimeBanner = isExperimentEnabled(
    splitz?.abExperiments?.enable_downtime_banner,
  );

  if (!shouldShowDowntimeBanner) {
    return null;
  }
  return (
    <EcosystemDowntimeProvider isPreviousDowntimesFetchDisabled>
      <DowntimeBannerAlert />
    </EcosystemDowntimeProvider>
  );
};

export default DowntimeBanner;
