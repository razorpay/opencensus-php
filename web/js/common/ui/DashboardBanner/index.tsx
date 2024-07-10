import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { fetchBanners as fetchBannersProp } from 'merchant/reducers/growthService';
import BannerComponent from './BannerComponent';
import { DashboardBannerProps } from './TypesDeclare/DashboardBannerTypes';
import { getCTAArray } from './util';
import { routeToRouteNameMap } from 'merchant/models/GrowthService/data';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import { withRouter } from 'common/deprecated/withRouter';

const DashboardBanner = ({
  fetchBanners,
  loading,
  banners,
  history,
  location,
  user,
}: DashboardBannerProps): React.ReactElement | Array<React.ReactElement> | Array<null> | null => {
  if (!user.isINCountry) {
    return null;
  }

  const routeName = routeToRouteNameMap[location.pathname] || location.pathname;

  useEffect(() => {
    fetchBanners({ fromWhere: location.pathname });
  }, [location.pathname]);

  let contentToShow: any = null;

  if (!loading && banners?.length) {
    contentToShow = (banners as any[]).map((banner) => {
      const ctaArray = getCTAArray(banner.buttons, history, banner?.id);
      return (
        <BannerComponent key={banner.id} ctaArray={ctaArray} {...banner} fromWhere={routeName} />
      );
    });
  }

  return contentToShow;
};

const DashboardBannerWithCompose = compose<any>(
  withRouter,
  connect(
    (state) => ({
      ...(state?.growthService?.banners || []),
      user: state.session.user,
    }),
    {
      fetchBanners: fetchBannersProp,
    },
  ),
)(DashboardBanner);

const DashboardBannerWrapper = (): JSX.Element => {
  return (
    <GrowthAssetEB>
      <DashboardBannerWithCompose />
    </GrowthAssetEB>
  );
};

export default DashboardBannerWrapper;
