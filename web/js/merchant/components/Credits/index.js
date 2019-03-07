import { Fragment } from 'react';
import Spinner from 'rzp/ui/Spinner';
import HeaderAction from 'rzp/ui/HeaderAction';

import { groupBy } from 'rzp/utils/rzp-utils';
import CreditDetails from './CreditDetails';
import CreditDetailsNew from './CreditDetailsNew';
import ShowWhen from 'merchant/components/ShowWhen';

export default props => {
  let { creditsData, balanceData, loading, currentUser } = props;

  if (!currentUser) {
    error =
      'Your user account is not associated at present with any active merchant account.';
  }
  const creditItems = groupBy(creditsData.items, 'type');

  return (
    <div class="credits content-wrapper content-sm">
      <HeaderAction>
        <div class="btn-toolbar pull-right">
          <ShowWhen
            additionalCondition={user =>
              user.isOrgAllowedFunctionality('external_links')
            }
          >
            <a
              class="btn btn-link"
              href="https://docs.razorpay.com/v1/page/credits"
              target="_blank"
            >
              Documentation &nbsp;
              <i class="i i-external-link" />
            </a>
          </ShowWhen>
        </div>
      </HeaderAction>

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
                  description="Transactions worth amount credits will be free of charge."
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
