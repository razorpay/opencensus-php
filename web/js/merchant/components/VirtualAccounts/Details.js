import { connect } from 'react-redux';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import Definition from 'rzp/ui/Definition';
import { VirtualAccountStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import AccountDetails, {
  getVirtualAccountDetails,
  getVirtualAccountDetailsToCopy,
} from 'merchant/components/VirtualAccounts/AccountDetails';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import Table from 'rzp/ui/Table/Index';
import { paymentId, amount } from 'rzp/ui/item/pair';
import { openModal, closeModal } from 'rzp/modules/modals';
import EnableTransferModal from './Modals/EnableTransferModal';

@connect(state => ({}), {
  openModal,
  closeModal,
})
export default class extends React.Component {
  openEnableTransferModal = () => {
    const { bankAccount, upiAddress } = getVirtualAccountDetails(
      this.props.virtualaccount
    );

    this.props.openModal({
      component: (
        <EnableTransferModal
          virtualAccountId={this.props.virtualaccount.id}
          isForBankAccount={!bankAccount}
          isForUPIAddress={!upiAddress}
        />
      ),
      size: 'small',
    });
  };

  render() {
    let {
      virtualaccount,
      va_payments,
      mode,
      isLoading,
      statusMsg,
      onClose,
      onMakeTestPaymentClick,
      onCopy = () => {},
    } = this.props;

    const isClosed = virtualaccount.status === 'closed';

    const { bankAccount, upiAddress } = getVirtualAccountDetails(
      virtualaccount
    );
    const valueToCopy = getVirtualAccountDetailsToCopy({
      bankAccount,
      upiAddress,
    });

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="i i-account-balance text-success icon--formal" />{' '}
              <strong>{virtualaccount.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <Alert type={statusMsg.type} message={statusMsg.message} />
              <div class="panel-body">
                <div class="VirtualAccountDetails">
                  <EntityDetailRow label="Account Details">
                    <CustomClipboard
                      value={valueToCopy}
                      onCopy={() => {
                        onCopy(virtualaccount);
                      }}
                    >
                      <div class="copy btn btn-link no-padding">
                        Copy Details
                      </div>
                    </CustomClipboard>
                  </EntityDetailRow>

                  <div class="divider" />
                  <AccountDetails
                    bankAccount={bankAccount}
                    upiAddress={upiAddress}
                  />
                </div>

                {!isClosed &&
                  !bankAccount && (
                    <button
                      class="btn btn-default"
                      onClick={this.openEnableTransferModal}
                    >
                      Enable Account Transfer
                    </button>
                  )}

                <br />

                {!isClosed &&
                  !upiAddress && (
                    <button
                      class="btn btn-default"
                      onClick={this.openEnableTransferModal}
                    >
                      Enable UPI Transfer
                    </button>
                  )}

                <div style={{ margin: '24px 0' }}>
                  <EntityDetailRow label="Amount Paid">
                    <Amount
                      value={virtualaccount.amount_paid}
                      currency={'INR'}
                    />
                  </EntityDetailRow>

                  <EntityDetailRow label="Status">
                    <VirtualAccountStatusLabel status={virtualaccount.status} />
                  </EntityDetailRow>

                  <EntityDetailRow
                    label="Account Description"
                    value={virtualaccount.description}
                  />

                  <EntityDetailRow
                    label="Customer Id"
                    value={virtualaccount.customer_id}
                  />

                  <EntityDetailRow label="Created At">
                    <Time
                      value={virtualaccount.created_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  </EntityDetailRow>

                  <EntityDetailRow label={isClosed ? 'Closed At' : 'Close By'}>
                    <Time
                      value={
                        isClosed
                          ? virtualaccount.closed_at
                          : virtualaccount.close_by
                      }
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  </EntityDetailRow>

                  {/* Notes */}
                  <EntityDetailRow label="Notes">
                    {virtualaccount.notes &&
                    Object.keys(virtualaccount.notes).length === 0
                      ? '--'
                      : Object.keys(virtualaccount.notes).map((key, index) => (
                          <div class="m-b" key={index}>
                            <Definition>
                              {key}
                              {String(virtualaccount.notes[key])}
                              <i />
                            </Definition>
                          </div>
                        ))}
                  </EntityDetailRow>
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
  }
}
