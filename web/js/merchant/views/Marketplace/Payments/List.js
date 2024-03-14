import { useEffect } from 'react';
import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import { fetchMarketplacePayments as fetchAll } from 'merchant/reducers/collection';
import PaymentsList from 'merchant/views/Transactions/v1/Payments/components/PaymentsList';
import { SelfServeActionPages } from 'common/constant/enums';
import ProductWrapper from 'common/ui/ProductWrapper';
import { navItems } from 'merchant/views/Marketplace/NavItems';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import DocsLink from 'merchant/components/DocsLink';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { platformFeeTabDisplayedAnalytics } from 'merchant/views/Marketplace/MarketplaceAnalytics';

export default connect((state) => ({ ...state.mpPayments, user: state.session.user }), {
  fetchAll,
})((props) => {
  useEffect(() => {
    if (props.isPlatformFeeTabEnabled) {
      platformFeeTabDisplayedAnalytics(props.user.id);
    }
  }, [props.isPlatformFeeTabEnabled, props.user.id]);
  return (
    <ProductWrapper
      tabsData={navItems(props.isPlatformFeeTabEnabled, props.isPartnerPlatformFeeEnabled)}
      extra={
        <>
          {RZPFeatures.ROUTE ? <TakeATourButton feature={RZPFeatures.ROUTE} /> : null}

          {props.docUrl ? <DocsLink url={props.docUrl} /> : null}
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
