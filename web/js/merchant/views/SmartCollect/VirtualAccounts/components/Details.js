import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Banner from 'common/ui/Banner';
import Button from 'common/new-ui/Button';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import Definition from 'common/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { VirtualAccountStatusLabel } from 'merchant/components/StatusLabel';
import AccountDetails, {
  getVirtualAccountDetails,
  getVirtualAccountDetailsToCopy,
} from './AccountDetails';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Table from 'common/ui/Table/Index';
import AccountDetailsSummary from './Modals/AccountDetailsSummary';
import EnableTransferMode from './Modals/EnableTransferMode';
import { paymentId, amount } from 'common/ui/item/pair';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateVirtualAccountDetails } from 'merchant/reducers/virtualaccounts';

@connect(
  (state) => ({
    user: state.session.user,
    isTestMode: state.session.mode === 'test',
  }),
  {
    openModal,
    closeModal,
    showNotification,
    updateVirtualAccountDetails,
  },
)
export default class extends React.Component {
  openEnableTransferModeModal = () => {
    const { bankAccount1, bankAccount2, upiAddress } = getVirtualAccountDetails(
      this.props.virtualaccount,
    );

    this.props.track('enable');

    this.props.openModal({
      component: (
        <EnableTransferMode
          closeModal={this.props.closeModal}
          isForBankAccount={!(bankAccount1 || bankAccount2)}
          isForUPIAddress={!upiAddress}
          updateVirtualAccountDetails={this.updateVirtualAccountDetails}
        />
      ),
      size: 'medium',
    });
  };

  updateVirtualAccountDetails = (payload) => {
    return this.props
      .updateVirtualAccountDetails(this.props.virtualaccount.id, payload)
      .then((data) => {
        this.props.closeModal();

        let accountDetails, modalTitle;
        let showUPIAddressDetails, showBankAccountDetails;

        if (payload.types && payload.types.indexOf('vpa') > -1) {
          modalTitle = 'UPI Transfer Enabled';
          showUPIAddressDetails = true;
        } else if (payload.types && payload.types.indexOf('bank_account') > -1) {
          modalTitle = 'Account Transfer Enabled';
          showBankAccountDetails = true;
        }

        this.props.openModal({
          size: 'small',
          component: (
            <AccountDetailsSummary
              modalTitle={modalTitle}
              closeModal={this.props.closeModal}
              virtualAccount={data}
              showUPIAddressDetails={showUPIAddressDetails}
              showBankAccountDetails={showBankAccountDetails}
            />
          ),
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors || 'Some network error has occurred',
        });
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
      user,
      isTestMode,
    } = this.props;

    const isClosed = virtualaccount.status === 'closed';

    const { bankAccount1, bankAccount2, upiAddress } = getVirtualAccountDetails(virtualaccount);

    const hasBankAccount = bankAccount1 || bankAccount2;

    const valueToCopy = getVirtualAccountDetailsToCopy({
      bankAccount1,
      bankAccount2,
      upiAddress,
    });

    const showTestPaymentBtn = mode === 'test' && virtualaccount.status === 'active';

    return (
      <div class="content-wrapper content-sm txn-details VirtualAccount--Details">
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
                  <EntityDetailRow label={<b>Account Details</b>}>
                    <CustomClipboard
                      value={valueToCopy}
                      onCopy={() => {
                        onCopy(virtualaccount);
                      }}
                    >
                      <div class="copy btn btn-link no-padding">Copy Details</div>
                    </CustomClipboard>
                  </EntityDetailRow>

                  <div class="divider" />
                  <AccountDetails
                    bankAccount1={bankAccount1}
                    bankAccount2={bankAccount2}
                    upiAddress={upiAddress}
                  />
                </div>

                {!user.isVACreationBankAccountDisabled && !isClosed && !hasBankAccount && (
                  <>
                    <br />

                    <button class="btn btn-default" onClick={this.openEnableTransferModeModal}>
                      Enable Account Transfer
                    </button>
                  </>
                )}

                {!isClosed && !upiAddress && !isTestMode && (
                  <>
                    <br />

                    <button class="btn btn-default" onClick={this.openEnableTransferModeModal}>
                      Enable UPI Transfer
                    </button>
                  </>
                )}

                <div style={{ margin: '24px 0' }}>
                  <EntityDetailRow label="Amount Paid">
                    <Amount value={virtualaccount.amount_paid} currency={'INR'} />
                  </EntityDetailRow>

                  <EntityDetailRow label="Status">
                    <VirtualAccountStatusLabel status={virtualaccount.status} />
                  </EntityDetailRow>

                  {virtualaccount.allowed_payers && (
                    <EntityDetailRow label="Third Party Validation">
                      <AllowedPayersList allowedPayers={virtualaccount.allowed_payers} />
                    </EntityDetailRow>
                  )}

                  <EntityDetailRow label="Account Description" value={virtualaccount.description} />

                  <EntityDetailRow label="Customer Id" value={virtualaccount.customer_id} />

                  <EntityDetailRow label="Created At">
                    <Time value={virtualaccount.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                  </EntityDetailRow>

                  <EntityDetailRow label={isClosed ? 'Closed At' : 'Close By'}>
                    <Time
                      value={isClosed ? virtualaccount.closed_at : virtualaccount.close_by}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  </EntityDetailRow>

                  {/* Notes */}
                  <EntityDetailRow label="Notes">
                    {virtualaccount.notes && Object.keys(virtualaccount.notes).length === 0
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
                  <button class="btn btn-default" onClick={() => onClose(virtualaccount)}>
                    Close Account
                  </button>
                ) : null}

                {showTestPaymentBtn && (
                  <Banner class="VA-test-payment">
                    <Button onClick={onMakeTestPaymentClick}>Make a Test Payment</Button>

                    <div>
                      <strong>Test Mode:</strong> Make a test payment to this virtual acocunt.
                    </div>
                  </Banner>
                )}

                <hr />

                <div>
                  <p class="text-muted" style={{ lineHeight: '35px' }}>
                    Payments to this account - {va_payments.length} payments
                    <Link
                      class="pull-right"
                      to={`/smartcollect/payments/?virtual_account_id=${virtualaccount.id}`}
                      onClick={() => this.props.track('view_payments')}
                    >
                      View All Payments
                    </Link>
                  </p>

                  <Table rows={va_payments} columns={[paymentId, amount]} showHeaders={false} />
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}

const AllowedPayersList = ({ allowedPayers }) => (
  <table class="allowed-payers-list">
    <thead>
      <tr>
        <th>IFSC Code</th>
        <th>Acc. Number</th>
      </tr>
    </thead>
    <tbody>
      {allowedPayers.map(({ bank_account }) => (
        <tr>
          <td>{bank_account.ifsc}</td>
          <td>{bank_account.account_number}</td>
        </tr>
      ))}
    </tbody>
  </table>
);
