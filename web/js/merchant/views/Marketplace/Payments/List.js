import { useEffect } from 'react';
import { connect } from 'react-redux';

import { SelfServeActionPages } from 'common/constant/enums';
import ProductWrapper from 'common/ui/ProductWrapper';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { RZPFeatures } from 'merchant/helpers/data';
import { fetchMarketplacePayments as fetchAll } from 'merchant/reducers/collection';
import { platformFeeTabDisplayedAnalytics } from 'merchant/views/Marketplace/MarketplaceAnalytics';
import { navItems } from 'merchant/views/Marketplace/NavItems';
import PaymentsList from 'merchant/views/Transactions/v1/Payments/components/PaymentsList';

export default connect((state) => ({ ...state.mpPayments, user: state.session.user }), {
  fetchAll,
})((props) => {
  const { isPlatformFeeTabEnabled, docUrl, isPartnerPlatformFeeEnabled, user } = props;
  useEffect(() => {
    if (isPlatformFeeTabEnabled) {
      platformFeeTabDisplayedAnalytics(user.id);
    }
  }, [isPlatformFeeTabEnabled, user.id]);
  return (
    <ProductWrapper
      tabsData={navItems(user, isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled)}
      extra={
        <>
          {RZPFeatures.ROUTE ? <TakeATourButton feature={RZPFeatures.ROUTE} /> : null}

          {docUrl ? <DocsLink url={docUrl} /> : null}
        </>
      }
    >
      <content>
        <TestModeBanner />
        <PaymentsList
          {...props}
          quickTourFeature={RZPFeatures.ROUTE}
          isRoute
          selfServeActionsPage={SelfServeActionPages.RoutePayments}
        />
      </content>
    </ProductWrapper>
  );
});
