import React from 'react';
import { useStore } from '@federated/apps/shell/commonStore';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import ErrorFallbackComponent from 'common/ui/WhatsNew/ErrorFallbackComponent';
import NotificationIcon from 'common/ui/WhatsNew/Icon';
import ShowWhen from 'merchant/components/ShowWhen';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazyLoader from 'merchant/routes/LazyLoader';
import { withI18Service } from 'common/i18';

const WhatsNew = lazyLoader(
  () => import(/* webpackChunkName: 'merchantWhatsNew' */ 'common/ui/WhatsNew/Old'),
);

const ConnectedAnnouncements = ({ i18 }) => {
  const user = useStore((state) => state.session.user);
  const { org } = useStore((state) => state.session);
  const { windowWidth } = useStore((state) => state.app);
  const { products, setShowSearchOnMobile } = useConnectedNavigationStore();

  const selectedProductTitle = products.selectedProduct?.title;

  const { isConfigTagEnabled } = i18;

  //TODO: Move the mobileWidth here and in App.js in a commonplace
  const mobileWidth = user?.isUniversalSearchEnabled ? 1280 : 950;

  const showMobileNav = windowWidth < mobileWidth;

  const showAnnouncementButton =
    user.isOrgAllowedFunctionality('external_links') &&
    !isConfigTagEnabled('announcements.announcements');

  if (!showAnnouncementButton) {
    return null;
  }

  return (
    <GrowthAssetEB FallbackComponent={ErrorFallbackComponent}>
      {user.isWhatsNewLazyEnabled ? (
        <NotificationIcon
          showMobileNav={showMobileNav}
          isConnectedNavigation={true}
          selectedProductTitle={selectedProductTitle}
        />
      ) : (
        <ShowWhen additionalCondition={() => !org.features.includes('disable_announcements')}>
          <SuspenseWithLoader type="default">
            <WhatsNew
              showMobileNav={showMobileNav}
              isConnectedNavigation={true}
              selectedProductTitle={selectedProductTitle}
            />
          </SuspenseWithLoader>
        </ShowWhen>
      )}
    </GrowthAssetEB>
  );
};

export default withI18Service(ConnectedAnnouncements);
