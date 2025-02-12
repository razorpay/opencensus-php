import { useEffect } from 'react';
import { connect } from 'react-redux';

import ProductWrapper from 'common/ui/ProductWrapper';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { RZPFeatures } from 'merchant/helpers/data';
import { platformFeeTabDisplayedAnalytics } from 'merchant/views/Marketplace/MarketplaceAnalytics';
import { navItems } from 'merchant/views/Marketplace/NavItems';
import PaymentsList from 'merchant/views/Transactions/v2/Payments/components/PaymentsList';
import { Box } from '@razorpay/blade/components';

export default connect(
  (state) => ({ user: state.session.user }),
  null,
)((props) => {
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
        <Box padding="spacing.7">
          <PaymentsList {...props} isMarketplacePayments />
        </Box>
      </content>
    </ProductWrapper>
  );
});
