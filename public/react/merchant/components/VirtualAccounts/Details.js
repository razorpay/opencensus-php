import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import { VirtualAccountStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import AccountDetails from 'merchant/components/VirtualAccounts/AccountDetails';
import Table from 'rzp/ui/Table/Index';
import { paymentId, amount, status } from 'rzp/ui/item/pair';

export default props => {
  let {
    virtualaccount,
    va_payments,
    mode,
    isLoading,
    statusMsg,
    onClose,
    onDelete,
    onMakeTestPaymentClick,
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
                  {mode === 'test' && virtualaccount.status === 'active'
                    ? <button
                        class="btn btn-link pull-right"
                        onClick={onMakeTestPaymentClick}
                      >
                        Make a Test Payment
                      </button>
                    : null}

                  <p class="text-muted" style={{ lineHeight: '35px' }}>
                    Payments to this account -
                    {' '}
                    <u>{va_payments.length} payments</u>
                  </p>

                  <Table
                    rows={va_payments}
                    columns={[paymentId, amount]}
                    showHeaders={false}
                  />
                </div>
              </div>
            </div>
          </div>}
    </div>
  );
};
