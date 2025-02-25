import React, { Fragment, Suspense, useEffect } from 'react';
import { Box } from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';

import { FullPageLoader, FullPageLoaderCenterToMainContent } from 'common/components/Loader';
import { useSplitzService } from 'common/splitz';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import { isEligibleForReKyc } from 'merchant/components/ReKycStatusAlerts/utils';
import { useUCSDataQuery } from 'merchant/containers/Home/RTUX/hooks/useUCSDataQuery';
import { useUCSLayoutQuery } from 'merchant/containers/Home/RTUX/hooks/useUCSLayoutQuery';
import lazy from 'merchant/routes/LazyLoader';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { getUcsAliasFromQueryKey, getBaseWidget, track } from 'merchant/widgets/utils';

import { ResponsiveWrapper } from './styles';
import DiwaliReportBanner from '../DiwaliReportBanner';
import FestivalThemeBanner from '../FestivalThemeBanner';
import { isEligibleForRazorpayRewind } from 'merchant/components/RazorpayRewind/utils';

const RTUX_HOMEPAGE_LAYOUT_KEY = ['rtux-homepage', 'layout'];
const RTUX_HOMEPAGE_DATA_KEY = ['rtux-homepage', 'data'];

const ReKycStatusBanner = lazy(() =>
  import(/* webpackChunkName: 'rekycStatusBanner' */ 'merchant/components/ReKycStatusAlerts').then(
    (module) => ({ default: module.ReKycStatusBanner }),
  ),
);

const RazorpayRewind = lazy(
  () => import(/* webpackChunkName: 'payments-recap' */ 'merchant/components/RazorpayRewind'),
);

const RTUXHomepage = (): JSX.Element => {
  const {
    data: layoutData,
    isFetching: isFetchingLayout,
    isError: isErrorLayout,
  } = useUCSLayoutQuery(RTUX_HOMEPAGE_LAYOUT_KEY, { alias: 'home_page' });
  const user = useStore((state) => state.session.user);
  const splitz = useSplitzService();
  const { data, isFetching, isError, refetch, error } = useUCSDataQuery(RTUX_HOMEPAGE_DATA_KEY, {
    alias: 'home_page',
  });
  const screen = getUcsAliasFromQueryKey(RTUX_HOMEPAGE_DATA_KEY) ?? '';
  // home page widget doesn't has a type or id
  const widgetId = `merchantDashboard.${screen}`;
  const shouldShowReKycBanner = isEligibleForReKyc(splitz, user);
  const shouldShowRazorpayRewind = isEligibleForRazorpayRewind(splitz, user);

  const retryHandler = () => {
    if (isError) refetch();
  };

  useEffect(() => {
    analyticsTrack({
      objectName: 'home page',
      actionName: 'displayed',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        flow: 'new',
      },
    });
  }, []);

  useEffect(() => {
    if (!isFetching) {
      track({
        objectName: 'widget',
        actionName: error ? 'error' : 'loaded',
        screen,
        properties: {
          widgetId,
          actionBy: widgetId,
          ...(error ? { error: `${error}` } : {}),
        },
      });
    }
  }, [isFetching, error]);

  // if layout errors out, fallback to default loader
  if (isFetchingLayout || (isErrorLayout && isFetching)) {
    return isMobileDevice() ? <FullPageLoader /> : <FullPageLoaderCenterToMainContent />;
  }
  // only retry for data, layout error is handled by default loader
  if (isError)
    return (
      <Box margin="spacing.7">
        <ErrorState
          text="Something went wrong"
          retryHandler={retryHandler}
          backgroundColor="surface.background.gray.intense"
          marginX="spacing.0"
          analyticsProperties={{
            screen,
            widgetId,
            actionBy: widgetId,
            ...(error ? { error: `${error}` } : {}),
          }}
        />
      </Box>
    );

  const dataSource = isFetching ? layoutData : data;

  return (
    <ResponsiveWrapper>
      <Box display="flex" flexDirection="column" paddingY="spacing.5" gap="spacing.6">
        {shouldShowReKycBanner ? (
          <Suspense fallback={null}>
            <ReKycStatusBanner isRtux={true} />
          </Suspense>
        ) : null}
        {shouldShowRazorpayRewind ? (
          <Suspense fallback={null}>
            <RazorpayRewind isRtux={true} />
          </Suspense>
        ) : null}
        <FestivalThemeBanner isRtux={true} />
        <DiwaliReportBanner isRtux={true} />
        {dataSource.components.map((widgetData) => (
          <Fragment key={widgetData.type}>
            {getBaseWidget({
              widget: widgetData,
              isLoading: isFetching,
              queryKey: RTUX_HOMEPAGE_DATA_KEY,
            })}
          </Fragment>
        ))}
      </Box>
    </ResponsiveWrapper>
  );
};

export default RTUXHomepage;
