import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import { VirtualAccountStatusLabel } from 'merchant/components/StatusLabel';
import DetailRow from 'merchant/components/DetailRow';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';

export default props => {
  let { virtualaccount, isLoading, statusMsg, onClose, onDelete } = props;

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
                <div class="row">
                  <div class="col-sm-9">
                    <table class="table table-bordered va-account-details">
                      <tbody>
                        <tr>
                          <td class="text-muted">Account Number</td>
                          <td>
                            <b>{virtualaccount.bank_account.account_number}</b>
                          </td>
                        </tr>
                        <tr>
                          <td class="text-muted">Beneficiary Name</td>
                          <td><b>{virtualaccount.name}</b></td>
                        </tr>
                        <tr>
                          <td class="text-muted">IFSC Code</td>
                          <td><b>{virtualaccount.bank_account.ifsc}</b></td>
                        </tr>
                        <tr>
                          <td colSpan="2" class="text-center">
                            <CustomClipboard
                              value={`Account Number: ${virtualaccount.bank_account.account_number}\nBeneficiary Name: ${virtualaccount.name}\nIFSC: ${virtualaccount.bank_account.ifsc}`}
                            >
                              <div class="copy">Copy to Clipboard</div>
                            </CustomClipboard>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div>
                  <DetailRow
                    label="Created At"
                    value={() => (
                      <Time
                        value={virtualaccount.created_at}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />
                    )}
                  />

                  <DetailRow
                    label="Amount Paid"
                    value={() => <Amount value={virtualaccount.amount_paid} />}
                  />

                  <DetailRow
                    label="Status"
                    value={() => (
                      <VirtualAccountStatusLabel
                        status={virtualaccount.status}
                      />
                    )}
                  />

                  <DetailRow
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
              </div>
            </div>
          </div>}
    </div>
  );
};
