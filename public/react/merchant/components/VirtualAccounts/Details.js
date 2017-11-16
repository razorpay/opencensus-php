import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import { VirtualAccountStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import AccountDetails from 'merchant/components/VirtualAccounts/AccountDetails';
import Table from 'rzp/ui/Table/Index';
import { paymentId, amount } from 'rzp/ui/item/pair';

export default props => {
  let {
    virtualaccount,
    va_payments,
    mode,
    isLoading,
    statusMsg,
    onClose,
    onMakeTestPaymentClick,
    onCopy = () => {},
  } = props;

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="icon icon-account-balance text-success icon--formal" />{' '}
            <strong>{virtualaccount.id}</strong>
          </div>

          <div class="SliderPanel__Body">
            <Alert type={statusMsg.type} message={statusMsg.message} />
            <div class="panel-body">
              <AccountDetails virtualaccount={virtualaccount} onCopy={onCopy} />

              <div style={{ margin: '24px 0' }}>
                <EntityDetailRow
                  label="Amount Paid"
                  value={() => <Amount value={virtualaccount.amount_paid} />}
                />

                <EntityDetailRow
                  label="Status"
                  value={() => (
                    <VirtualAccountStatusLabel status={virtualaccount.status} />
                  )}
                />

                <EntityDetailRow
                  label="Account Description"
                  value={virtualaccount.description}
                />

                <EntityDetailRow
                  label="Customer Id"
                  value={virtualaccount.customer_id}
                />

                <EntityDetailRow
                  label="Created At"
                  value={() => (
                    <Time
                      value={virtualaccount.created_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />
              </div>

              {virtualaccount.status !== 'closed' ? (
                <button
                  class="btn btn-default"
                  onClick={() => onClose(virtualaccount)}
                >
                  Close Account
                </button>
              ) : null}

              <hr />

              <div>
                {mode === 'test' && virtualaccount.status === 'active' ? (
                  <button
                    class="btn btn-link pull-right"
                    onClick={onMakeTestPaymentClick}
                  >
                    Make a Test Payment
                  </button>
                ) : null}

                <p class="text-muted" style={{ lineHeight: '35px' }}>
                  Payments to this account - {va_payments.length} payments
                </p>

                <Table
                  rows={va_payments}
                  columns={[paymentId, amount]}
                  showHeaders={false}
                />
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
