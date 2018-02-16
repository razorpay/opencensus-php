import moment from 'moment';
import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { DisputeStatusLabel } from 'merchant/components/StatusLabel';
import { titleCase } from 'rzp/utils/rzp-utils';
import { daysLeftInExpiry } from 'merchant/utils/disputes';

export default props => {
  const { dispute, isLoading, error } = props;
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">{dispute.id}</div>
          <Alert type="error" message={error} />
          <div class="SliderPanel__Body">
            {dispute.status === 'open' && (
              <div class="alert alert-warning rzp-banner">
                <div class="rzp-banner-text">
                  <p>
                    {dispute.phase === 'fraud' ? (
                      "The customer's bank has reported a possibly fraudulent transaction. We recommend that you respond to the email sent to you by "
                    ) : (
                      <React.Fragment>
                        A customer has raised a dispute for&nbsp;
                        <Amount
                          value={dispute.amount}
                          currency={dispute.currency}
                        />&nbsp; Kindly respond to the mail sent to you by
                        &nbsp;
                      </React.Fragment>
                    )}
                    <Time value={dispute.respond_by} format="ll" />&nbsp; ({daysLeftInExpiry(
                      dispute.respond_by,
                      'in '
                    )}).
                  </p>

                  <p>
                    Failing to do so,{' '}
                    {dispute.phase !== 'fraud' &&
                      'you will loose the dispute and '}
                    the disputed amount{' '}
                    {dispute.phase === 'fraud' ? 'might' : 'will'} be deducted
                    from your account.
                  </p>
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
                  <React.Fragment>
                    <Time value={dispute.respond_by} format="LL" />
                    &nbsp;({daysLeftInExpiry(dispute.respond_by, 'In ')})
                  </React.Fragment>
                ) : (
                  '--'
                )}
              </EntityDetailRow>

              {/* phase of dispute */}
              <EntityDetailRow label="Type" value={titleCase(dispute.phase)} />

              {/* reason_description of dispute */}
              <EntityDetailRow
                label="Reason"
                value={dispute.reason_description}
              />

              {/* created_at of dispute */}
              <EntityDetailRow label="Created At">
                <Time value={dispute.created_at} format="LL|hh:mm A" />
              </EntityDetailRow>

              {/* payment */}
              <EntityDetailRow label="Payment">
                <Link to={`/payments/${dispute.payment_id}`}>
                  <code>{dispute.payment_id}</code>
                </Link>
              </EntityDetailRow>

              {/* comment */}
              <EntityDetailRow
                label="Comment"
                value={() => dispute.comment || '--'}
              />

              {/* documents uploaded */}
              {/*<EntityDetailRow
              label="Upload Documents"
              value="The documents should be .jpeg, .png or .pdf format with the maximum size of 1 MB"
            />*/}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
