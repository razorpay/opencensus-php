import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import ListGroupToggler from 'rzp/ui/ListGroupToggler';
import TableBody from 'rzp/ui/TableBody';
import DetailRow from 'merchant/components/DetailRow';
import { Link } from 'react-router-dom';

export default ({ refund, isLoading, statusMsg }) => {
  let refundNotes = null;
  if (Object.keys(refund.notes).length) {
    refundNotes = (
      <div>
        <DetailRow label="Notes" />
        <div class="panel-body">
          <div class="list-group">
            {Object.keys(refund.notes).map(key => (
              <DetailRow key={key} label={key} value={refund.notes[key]} />
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default">
            <Alert type={statusMsg.type} message={statusMsg.message} />

            <div class="panel-heading">
              Refund ID: <b>{refund.id}</b>
            </div>

            <div class="panel-body">
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

                {refundNotes}
              </div>
            </div>
          </div>}
    </div>
  );
};
