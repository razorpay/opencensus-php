import { useEffect, useState } from 'react';
import Spinner from 'common/ui/Spinner';
import HeaderAction from 'common/ui/HeaderAction';
import { groupBy, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import CreditDetails from './CreditDetails';
import CreditDetailsNew from './CreditDetailsNew';
import DocsLink from 'merchant/components/DocsLink';
import { analyticsTrack } from 'common/utils/analytics';
import ManageCreditAlerts from './ManageCreditAlerts';
import { CLICK_ON_MANAGE_ALERTS } from '../ga';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import Alert from 'common/ui/Forms/Alert';

function CreditsList(props) {
  const { creditsData, balanceData, loading, showDocumentation = true } = props;
  const creditItems = groupBy(creditsData.items, 'type');
  const [status, setStatus] = useState({});
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
            <DocsLink url="https://razorpay.com/docs/payment-gateway/dashboard-guide/credits/" />
          </div>
        </HeaderAction>
      )}
      {loading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="list-group details-row-container">
          {Object.keys(status).length > 0 && <Alert type={status.type} message={status.message} />}

          {props.user.isAllowedEdit('credits') && (
            <div class="manage-alerts-row">
              <span>Note: Standard TDR charges applies on adding funds</span>
              <span style={{ color: '#528ff0' }} onClick={handleManageAlert}>
                Manage Alerts
                <i class="i i-bell-outline" />
              </span>
            </div>
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

export default CreditsList;
