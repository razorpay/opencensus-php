import React from 'react';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { DisputeStatusLabel } from 'merchant/components/StatusLabel';
import { titleCase, daysFromToday } from 'common/utils/rzp-utils';

export const daysLeftInExpiry = (expiresOn, prefixForDays = '') => {
  const daysLeft = daysFromToday(expiresOn);
  if (daysLeft < 0) {
    return <span class="text-muted">Passed</span>;
  } else if (daysLeft === 0) {
    return <strong class="text-danger">Today</strong>;
  } else {
    return `${prefixForDays}${daysLeft} day${daysLeft > 1 ? 's' : ''}`;
  }
};

const DisputeDetails = (props) => {
  const { dispute, isLoading, error, onCloseSecView, goToLink } = props;
  return (
    <div class="content-wrapper content-sm txn-details dispute-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            {onCloseSecView && (
              <button type="button" class="close close-secondary" onClick={onCloseSecView}>
                <i class="i i-arrow-back" />
                <i class="i i-close" />
              </button>
            )}
            Dispute Id: <strong>{dispute.id}</strong>
          </div>
          <Alert type="error" message={error} />
          <div class="SliderPanel__Body">
            {dispute.status === 'open' && (
              <div class="alert alert-warning rzp-banner">
                <div class="rzp-banner-text">
                  {/* text required only for fraud dispute */}
                  {dispute.phase === 'fraud' && (
                    <p>
                      This transaction is suspected to be fraudulent. If you agree, a good practice
                      would be to initiate a refund, in order to prevent a chargeback.
                    </p>
                  )}

                  <p>
                    {/* text depending upon if dispute is fraud or not */}
                    {dispute.phase === 'fraud' ? (
                      'If you think this is a valid transaction, then '
                    ) : (
                      <>
                        A customer has raised a dispute for&nbsp;
                        <Amount value={dispute.amount} currency={dispute.currency} />
                        ,&nbsp;
                      </>
                    )}
                    {/* Text required in all types of dispute  */}
                    kindly respond to the mail sent to you by&nbsp;
                    <Time value={dispute.respond_by} format="ll" />
                    &nbsp; ({daysLeftInExpiry(dispute.respond_by, 'in ')}).
                  </p>

                  {/* text NOT required for fraud dispute */}
                  {dispute.phase !== 'fraud' && (
                    <p>Failing to do so, the disputed amount will be deducted from your account.</p>
                  )}
                </div>
              </div>
            )}

            <div class="panel-body">
              <div class="list-group details-row-container">
                {/* disputed amount */}
                <EntityDetailRow label="Amount">
                  <Amount value={dispute.amount} currency={dispute.currency} />
                </EntityDetailRow>

                {/* status of dispute */}
                <EntityDetailRow label="Status">
                  <DisputeStatusLabel status={dispute.status} />
                </EntityDetailRow>
              </div>

              {/* expiry date of dispute */}
              <EntityDetailRow label="Respond By">
                {dispute.status === 'open' ? (
                  <>
                    <Time value={dispute.respond_by} format="LL" />
                    &nbsp;({daysLeftInExpiry(dispute.respond_by, 'In ')})
                  </>
                ) : (
                  '--'
                )}
              </EntityDetailRow>

              {/* phase of dispute */}
              <EntityDetailRow label="Type" value={titleCase(dispute.phase)} />

              {/* reason_description of dispute */}
              <EntityDetailRow label="Reason" value={dispute.reason_description} />

              {/* created_at of dispute */}
              <EntityDetailRow label="Created At">
                <Time value={dispute.created_at} format="LL|hh:mm A" />
              </EntityDetailRow>

              {/* payment */}
              <EntityDetailRow label="Payment">
                <a onClick={() => goToLink(`payments/${dispute.payment_id}`)}>
                  <code>{dispute.payment_id}</code>
                </a>
              </EntityDetailRow>

              {/* comment */}
              <EntityDetailRow label="Comment" value={() => dispute.comment || '--'} />
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DisputeDetails;
