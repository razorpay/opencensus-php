import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import { VirtualAccountStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import AccountDetails from 'merchant/components/VirtualAccounts/AccountDetails';

export default props => {
  let {
    virtualaccount,
    va_payments,
    isLoading,
    statusMsg,
    onClose,
    onDelete,
  } = props;

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="icon icon-account-balance text-success" />
              {' '}
              <strong>{virtualaccount.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <Alert type={statusMsg.type} message={statusMsg.message} />
              <div class="panel-body">
                <AccountDetails virtualaccount={virtualaccount} />

                <div style={{ margin: '24px 0' }}>
                  <EntityDetailRow
                    label="Created At"
                    value={() => (
                      <Time
                        value={virtualaccount.created_at}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />
                    )}
                  />

                  <EntityDetailRow
                    label="Amount Paid"
                    value={() => <Amount value={virtualaccount.amount_paid} />}
                  />

                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <VirtualAccountStatusLabel
                        status={virtualaccount.status}
                      />
                    )}
                  />

                  <EntityDetailRow
                    label="Bank Account ID"
                    value={virtualaccount.bank_account.id}
                  />
                </div>

                <div class="btn-toolbar">
                  {virtualaccount.status !== 'closed'
                    ? <button
                        class="btn btn-default"
                        onClick={() => onClose(virtualaccount)}
                      >
                        Close Account
                      </button>
                    : null}
                  <button
                    class="btn btn-link"
                    onClick={() => onDelete(virtualaccount)}
                  >
                    Delete Account
                  </button>
                </div>

                <hr />

                <div>
                  <p class="text-muted">
                    Payments to this account -
                    {' '}
                    <u>{va_payments.length} payments</u>
                  </p>

                  <table class="table table-hover">
                    <tbody>
                      {va_payments.map(payment => {
                        return (
                          <tr key={payment.id}>
                            <td>{payment.id}</td>
                            <td>{payment.amount}</td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>}
    </div>
  );
};
