import moment from 'moment';
import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { DisputeStatusLabel } from 'merchant/components/StatusLabel';
import { titleCase } from 'rzp/utils/rzp-utils';

export default props => {
  const { dispute } = props;

  return (
    <div class="content-wrapper content-sm txn-details">
      <div class="panel panel-default SliderPanel">
        <div class="panel-heading">{dispute.id}</div>

        <div class="SliderPanel__Body">
          <div class="alert alert-warning rzp-banner">
            <div class="rzp-banner-text">
              Customer has raised a dispute for&nbsp;
              <Amount
                value={dispute.amount}
                currency={dispute.currency}
              />.&nbsp; To contest the dispute, upload all the supporting
              documents. If you choose to accept this dispute, or you do not
              respond by {moment(dispute.expires_on, 'X').format('ll')} you will
              lose the dispute and the disputed amount will be deducted from
              your account.
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
              <Time value={dispute.expires_on} format="LL" />
            </EntityDetailRow>

            {/* phase of dispute */}
            <EntityDetailRow label="Type" value={titleCase(dispute.phase)} />

            {/* reason_description of dispute */}
            <EntityDetailRow
              label="Reason"
              value={dispute.reason_description}
            />

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
            <EntityDetailRow
              label="Upload Documents"
              value="The documents should be .jpeg, .png or .pdf format with the maximum size of 1 MB"
            />
          </div>
        </div>
      </div>
    </div>
  );
};
