import { useEffect, useState } from 'react';
import Spinner from 'common/ui/Spinner';
import { groupBy, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import CreditDetails from './CreditDetails';
import CreditDetailsNew from './CreditDetailsNew';
import DocsLink from 'merchant/components/DocsLink';
import { analyticsTrack } from 'common/utils/analytics';
import ManageCreditAlerts from './ManageCreditAlerts';
import { CLICK_ON_MANAGE_ALERTS, OPEN_DOCUMENTATION } from 'merchant/views/Account/Credits/ga';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { connect } from 'react-redux';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { BellIcon, Box, Heading, Link, Text } from '@razorpay/blade/components';

function CreditsList(props) {
  const { creditsData, balanceData, loading, showDocumentation = true, user } = props;
  const creditItems = groupBy(creditsData.items, 'type');

  const [, setStatus] = useState({});

  useEffect(() => {
    analyticsTrack({
      objectName: 'credits',
      actionName: 'viewed',
      screen: 'my account',
      properties: {
        action: 'cancel',
        location: 'credits',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    loadCheckout(window.checkout_api_host);
  }, []);

  const handleManageAlert = () => {
    props.openModal({
      size: 'large',
      component: <ManageCreditAlerts />,
    });
    selfServeTrackInitiate({
      selfServeAction: 'Credit Alert Created',
      page: 'Credits',
      screen: 'My Account',
    });
    analyticsTrack(CLICK_ON_MANAGE_ALERTS);
  };

  return (
    <div className="credits content-wrapper content-sm" style={{ backgroundColor: '#f9fafb' }}>
      {loading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <Box display="flex" flexDirection="column" gap="spacing.8">
          <Box
            display="flex"
            width="100%"
            flexDirection={{ base: 'column', m: 'row' }}
            justifyContent="space-between"
            alignItems={{ base: 'unset', m: 'center' }}
          >
            <Heading size="small" weight="semibold">
              Your Credits
            </Heading>
            <Box display="flex" gap="spacing.7" alignItems="center">
              {props.user.isAllowedEdit('credits') && (
                <Link
                  variant="button"
                  icon={BellIcon}
                  iconPosition="right"
                  onClick={handleManageAlert}
                >
                  Manage Alerts
                </Link>
              )}
              {showDocumentation && (
                <DocsLink
                  shouldUseBladeLink={true}
                  url="https://razorpay.com/docs/payment-gateway/dashboard-guide/credits/"
                  onClick={() => analyticsTrack(OPEN_DOCUMENTATION)}
                />
              )}
            </Box>
          </Box>
          <Box display="flex" flexDirection="column" gap="spacing.7">
            <Box display="flex" flexDirection="column" gap="spacing.5">
              <CreditDetailsNew
                totalCredits={balanceData.credits}
                title="Amount Credits"
                description="Transactions worth amount credits will be free of any transaction fee."
                creditItems={creditItems.amount}
                toggleText="Past Coupons"
                trackToggleHistory={props.trackToggleHistory}
              />
              <CreditDetails
                totalCredits={balanceData.fee_credits}
                title="Fee Credits"
                description="Get your transactions settled in full. Transaction charges will be deducted from fee credits."
                creditItems={[...(creditItems.fee || []), ...(creditItems.fee_withdraw || [])]}
                onManageAlert={props.onManageAlert}
                trackToggleHistory={props.trackToggleHistory}
                type="fee"
                setStatus={setStatus}
              />
              <CreditDetails
                totalCredits={balanceData.refund_credits}
                title="Refund Credits"
                description="Do not want to refund from your settled amounts? Use refund credits."
                creditItems={[
                  ...(creditItems.refund || []),
                  ...(creditItems.refund_withdraw || []),
                ]}
                trackToggleHistory={props.trackToggleHistory}
                type="refund"
                setStatus={setStatus}
              />
            </Box>
            <Text size="small" weight="regular" color="surface.text.gray.normal">
              Note: Standard TDR charges applies on adding funds.
              {(user.business_type === '11' || user.business_type === '2') &&
                'These credits can not be applied for credit cards transactions.'}
            </Text>
          </Box>
        </Box>
      )}
    </div>
  );
}

export default connect((state) => ({ user: state.session.user }), null)(CreditsList);
