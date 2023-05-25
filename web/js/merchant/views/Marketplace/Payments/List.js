import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import { fetchMarketplacePayments as fetchAll } from 'merchant/reducers/collection';
import PaymentsList from 'merchant/views/Transactions/Payments/components/PaymentsList';
import { SelfServeActionPages } from 'common/constant/enums';
import ProductWrapper from 'common/ui/ProductWrapper';
import { navItems } from 'merchant/views/Marketplace/NavItems';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import DocsLink from 'merchant/components/DocsLink';
import TestModeBanner from 'merchant/components/TestModeBanner';

export default connect((state) => ({ ...state.mpPayments }), {
  fetchAll,
})((props) => (
  <ProductWrapper
    tabsData={navItems(props.isPlatformFeeTabEnabled)}
    extra={
      <>
        {RZPFeatures.ROUTE && <TakeATourButton feature={RZPFeatures.ROUTE} />}

        {props.docUrl && <DocsLink url={props.docUrl} />}
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
));
