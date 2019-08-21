import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { Link } from 'react-router-dom';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import RefundStatusTimeline from 'merchant/components/Refunds/RefundTimeline';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import RefundUpdate from 'merchant/components/Refunds/RefundUpdate';
import { showWhenUtil } from 'merchant/components/ShowWhen';

export default ({ refund, isLoading, statusMsg, viewRefundHistory }) => {
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            Refund Id: <b>{refund.id}</b>
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />
              <div class="list-group details-row-container">
                <EntityDetailRow
                  label="Payment"
                  value={() => (
                    <Link to={`/payments/${refund.payment_id}`}>
                      <code>{refund.payment_id}</code>
                    </Link>
                  )}
                />

                {showWhenUtil({ featureEnabled: 'card_transfer_refund' }) ? (
                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <ContentToggler onToggleClick={viewRefundHistory}>
                        <span>View History</span>
                        <RefundStatusTimeline refund={refund} />
                      </ContentToggler>
                    )}
                  />
                ) : null}

                <EntityDetailRow
                  label="Amount"
                  value={() => (
                    <Amount value={refund.amount} currency={refund.currency} />
                  )}
                />

                {showWhenUtil({ featureEnabled: 'card_transfer_refund' }) ? (
                  <EntityDetailRow
                    label="Refund Mode"
                    value={() => {
                      return refund.speed_processed !== null ? (
                        <span>
                          {refund.speed_processed.charAt(0).toUpperCase() +
                            refund.speed_processed.slice(1)}
                        </span>
                      ) : (
                        <span>{'Instant'}</span>
                      );
                    }}
                  />
                ) : null}

                <EntityDetailRow label="Currency" value={refund.currency} />

                <EntityDetailRow
                  label="Created At"
                  value={() => (
                    <Time
                      value={refund.created_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />

                <NestedEntityDetailRow
                  label="Acquirer Data"
                  value={refund.acquirer_data}
                />
                <NestedEntityDetailRow label="Notes" value={refund.notes} />
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
