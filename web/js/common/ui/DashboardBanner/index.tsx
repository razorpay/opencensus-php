import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { fetchBanners as fetchBannersProp } from '../../../merchant/reducers/growthService';
import BannerComponent from './BannerComponent';
import { DashboardBannerProps } from './TypesDeclare/DashboardBannerTypes';
import { getCTAArray } from './util';
import { routeToRouteNameMap } from '../../../merchant/models/GrowthService/data';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { withRouter } from 'react-router';

const BannerFallbackComponent = () => null;

const DashboardBanner = ({
  fetchBanners,
  loading,
  banners,
  history,
}: DashboardBannerProps): React.ReactElement | Array<React.ReactElement> | Array<null> | null => {
  const routeName = routeToRouteNameMap[window.location.pathname] || '';

  useEffect(() => {
    fetchBanners({ fromWhere: window.location.pathname });
  }, []);

  let contentToShow: any = null;

  if (!loading && banners?.length) {
    contentToShow = (banners as any[]).map((banner) => {
      const ctaArray = getCTAArray(banner.buttons, history);
      return (
        <BannerComponent key={banner.id} ctaArray={ctaArray} {...banner} fromWhere={routeName} />
      );
    });
  }

  return <ErrorBoundary FallbackComponent={BannerFallbackComponent}>{contentToShow}</ErrorBoundary>;
};

export default compose<any>(
  withRouter,
  connect(
    (state) => ({
      ...(state?.growthService?.banners || []),
    }),
    {
      fetchBanners: fetchBannersProp,
    },
  ),
)(DashboardBanner);
