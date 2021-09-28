import { useEffect, useState } from 'react';
import Spinner from 'common/ui/Spinner';
import HeaderAction from 'common/ui/HeaderAction';
import { groupBy, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import CreditDetails from './CreditDetails';
import CreditDetailsNew from './CreditDetailsNew';
import DocsLink from 'merchant/components/DocsLink';
import { analyticsTrack } from 'common/utils/analytics';
import ManageCreditAlerts from './ManageCreditAlerts';
import { CLICK_ON_MANAGE_ALERTS, OPEN_DOCUMENTATION } from '../ga';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { connect } from 'react-redux';

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

    loadCheckout(window.api_host);
  }, []);

  const handleManageAlert = () => {
    props.openModal({
      size: 'large',
      component: <ManageCreditAlerts />,
    });

    analyticsTrack(CLICK_ON_MANAGE_ALERTS);
  };

  return (
    <div class="credits content-wrapper content-sm">
      {showDocumentation && (
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <DocsLink
              url="https://razorpay.com/docs/payment-gateway/dashboard-guide/credits/"
              onClick={() => analyticsTrack(OPEN_DOCUMENTATION)}
            />
          </div>
        </HeaderAction>
      )}
      {loading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="list-group details-row-container">
          {props.user.isAllowedEdit('credits') && (
            <div class="manage-alerts-row">
              <span>Note: Standard TDR charges applies on adding funds</span>
              <span style={{ color: '#528ff0' }} onClick={handleManageAlert}>
                Manage Alerts
                {}
                <i class="i i-bell-outline" />
              </span>
            </div>
          )}

          {(user.business_type === '11' || user.business_type === '2') && (
            <div class="note">These credits can not be applied for credit cards transactions.</div>
          )}

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
            creditItems={creditItems.fee || []}
            onManageAlert={props.onManageAlert}
            trackToggleHistory={props.trackToggleHistory}
            type="fee"
            setStatus={setStatus}
          />

          <CreditDetails
            totalCredits={balanceData.refund_credits}
            title="Refund Credits"
            description="Do not want to refund from your settled amounts? Use refund credits."
            creditItems={creditItems.refund || []}
            trackToggleHistory={props.trackToggleHistory}
            type="refund"
            setStatus={setStatus}
          />
        </div>
      )}
    </div>
  );
}

export default connect((state) => ({ user: state.session.user }), null)(CreditsList);
