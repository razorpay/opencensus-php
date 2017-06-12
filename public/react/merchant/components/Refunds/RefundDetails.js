import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import DetailRow from 'merchant/components/DetailRow';
import { Link } from 'react-router-dom';
import NestedDetailRow from 'merchant/components/NestedDetailRow';

export default ({ refund, isLoading, statusMsg }) => {
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              Refund ID: <b>{refund.id}</b>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <Alert type={statusMsg.type} message={statusMsg.message} />
                <div class="list-group details-row-container">
                  <DetailRow
                    label="Payment"
                    value={() => (
                      <Link to={`/payments/${refund.payment_id}`}>
                        <code>{refund.payment_id}</code>
                      </Link>
                    )}
                  />

                  <DetailRow
                    label="Amount"
                    value={() => <Amount value={refund.amount} />}
                  />

                  <DetailRow label="Currency" value={refund.currency} />

                  <DetailRow
                    label="Created At"
                    value={() => (
                      <Time
                        value={refund.created_at}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />
                    )}
                  />

                  <NestedDetailRow
                    label="Acquirer Data"
                    value={refund.acquirer_data}
                  />
                  <NestedDetailRow label="Notes" value={refund.notes} />
                </div>
              </div>
            </div>
          </div>}
    </div>
  );
};
