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
            <div class="alert alert-warning rzp-banner">
              <div class="rzp-banner-text">
                {dispute.phase === 'fraud' ? (
                  <React.Fragment>
                    The customer&#39;s bank has reported a possibly fraudulent
                    transaction. &nbsp;We recommend that you respond to the
                    email sent to you on your registered email address by &nbsp;<Time
                      value={dispute.expires_on}
                      format="ll"
                    />{' '}
                    failing which there might be a amount deduction from your
                    account.
                  </React.Fragment>
                ) : (
                  <React.Fragment>
                    A Customer has raised a dispute for&nbsp;
                    <Amount
                      value={dispute.amount}
                      currency={dispute.currency}
                    />.&nbsp; Kindly respond to the mail sent to you on your
                    registered email address by &nbsp;<Time
                      value={dispute.expires_on}
                      format="ll"
                    />{' '}
                    ({daysLeftInExpiry(dispute.expires_on, 'in ')}) failing
                    which you will loose the dispute and the disputed amount
                    will be deducted from your account.
                  </React.Fragment>
                )}
              </div>
            </div>

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
                    <Time value={dispute.expires_on} format="LL" />
                    &nbsp;({daysLeftInExpiry(dispute.expires_on, 'In ')})
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
