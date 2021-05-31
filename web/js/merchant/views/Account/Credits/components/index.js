import { Fragment, useEffect } from 'react';
import Spinner from 'common/ui/Spinner';
import HeaderAction from 'common/ui/HeaderAction';

import { groupBy, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import CreditDetails from './CreditDetails';
import CreditDetailsNew from './CreditDetailsNew';
import DocsLink from 'merchant/components/DocsLink';
import { analyticsTrack } from 'common/utils/analytics';

export default (props) => {
  let { creditsData, balanceData, loading, currentUser, showDocumentation = true } = props;

  if (!currentUser) {
    error = 'Your user account is not associated at present with any active merchant account.';
  }
  const creditItems = groupBy(creditsData.items, 'type');
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
  }, []);
  return (
    <div class="credits content-wrapper content-sm">
      {showDocumentation && (
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <DocsLink url="https://razorpay.com/docs/payment-gateway/dashboard-guide/my-account/#credits" />
          </div>
        </HeaderAction>
      )}
      {loading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="list-group details-row-container">
          {!(creditsData && creditsData.items.length) ? (
            <h3 class="empty-table text-center">No Credits</h3>
          ) : (
            <Fragment>
              {(!!balanceData.credits || !!creditItems.amount) && (
                <CreditDetailsNew
                  totalCredits={balanceData.credits}
                  title="Amount Credits"
                  description={
                    <>
                      Transactions made upto the credit amount in your account will be free of
                      charge. The credits are not valid for credit card transactions if your
                      business type is <strong>not registered.</strong>
                    </>
                  }
                  creditItems={creditItems.amount}
                  toggleText={'Past Coupons'}
                  trackToggleHistory={props.trackToggleHistory}
                />
              )}

              {(!!balanceData.fee_credits || !!creditItems.fee) && (
                <CreditDetails
                  totalCredits={balanceData.fee_credits}
                  title="Fee Credits"
                  description="Get your amounts settled in full. Fees charged from credits."
                  creditItems={creditItems.fee}
                  onManageAlert={props.onManageAlert}
                  trackToggleHistory={props.trackToggleHistory}
                />
              )}

              {(!!balanceData.refund_credits || !!creditItems.refund) && (
                <CreditDetails
                  totalCredits={balanceData.refund_credits}
                  title="Refund Credits"
                  description="Do not want to refund from your settled amounts? Use refund credits."
                  creditItems={creditItems.refund}
                  trackToggleHistory={props.trackToggleHistory}
                />
              )}
            </Fragment>
          )}
        </div>
      )}
    </div>
  );
};
