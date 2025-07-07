import React from 'react';
import { Link as BladeLink, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import { SelfServeActionPages } from 'common/constant/enums';
import Alert from 'common/new-ui/Alert';
import Button from 'common/new-ui/Button';
import { withSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import Banner from 'common/ui/Banner';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Definition from 'common/ui/Definition';
import Spinner from 'common/ui/Spinner';
import Table from 'common/ui/Table/Index';
import Time from 'common/ui/Time';
import { paymentId, amount } from 'common/ui/item/pair';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { VirtualAccountStatusLabel } from 'merchant/components/StatusLabel';
import { AXIS_BANK_MIGRATION_FAQ } from 'merchant/constants/urls';
import { fetchFeatureStatus } from 'merchant/reducers/config';
import { updateVirtualAccountDetails } from 'merchant/reducers/virtualaccounts';
import { EditExpiry } from 'merchant/views/PaymentLinks/PaymentLinks/components/Edit/index';
import { isAxisBank, isRBLBank } from 'merchant/views/SmartCollect/VirtualAccounts/helpers';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import { showNotification } from 'merchant_common/reducers/notifications';
import AccountDetails, {
  getVirtualAccountDetails,
  getVirtualAccountDetailsToCopy,
} from './AccountDetails';

import AccountDetailsSummary from './Modals/AccountDetailsSummary';
import EnableTransferMode from './Modals/EnableTransferMode';

const _paymentId = () => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(
        item,
        SelfServeActionPages.SmartcollectCustomeridentifiers,
        'customeridentifier-details',
      );
      return <div>{intermediateElement}</div>;
    },
  };
};

class VirtualAccountDetails extends React.Component {
  state = {
    isEditSingleVaMid: false,
  };

  UNSAFE_componentWillMount() {
    // check edit_single_va_expiry MID feature
    this.props
      .fetchFeatureStatus(this.props.user.id, 'edit_single_va_expiry')
      .then((fetchFeatureStatusResp) => {
        if (fetchFeatureStatusResp.data.status) {
          this.setState({
            isEditSingleVaMid: true,
          });
        }
      })
      .catch((err) => {
        if (err) {
          this.props.showNotification({
            type: 'error',
            message: err.errors[0],
          });
        }
      });
  }

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

        let modalTitle, showUPIAddressDetails, showBankAccountDetails;

        if (payload.types && payload.types.indexOf('vpa') > -1) {
          modalTitle = 'UPI Transfer Enabled';
          showUPIAddressDetails = true;
        } else if (payload.types && payload.types.indexOf('bank_account') > -1) {
          modalTitle = 'Customer Identifier Transfer Enabled';
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
    const {
      virtualaccount,
      va_payments,
      mode,
      isLoading,
      onClose,
      onMakeTestPaymentClick,
      onCopy = () => {},
      user,
      isTestMode,
      updateCloseByDate,
      splitz,
    } = this.props;
    const isClosed = virtualaccount.status === 'closed';

    let isRblAccountMigrationEnabled = false;
    if (splitz) {
      const {
        abExperiments: { rbl_account_migration },
      } = splitz;

      isRblAccountMigrationEnabled = rbl_account_migration?.variables?.result === 'on';
    }
    // bankAccount1 : new/latest account
    // bankAccount2 : old account
    const { bankAccount1, bankAccount2, upiAddress } = getVirtualAccountDetails(virtualaccount);
    const hasBankAccount = bankAccount1 || bankAccount2;
    const isRblAccountMigrated =
      isRblAccountMigrationEnabled && isAxisBank(bankAccount1) && isRBLBank(bankAccount2);

    const valueToCopy = getVirtualAccountDetailsToCopy({
      bankAccount1,
      bankAccount2,
      upiAddress,
    });
    const bankAccount2valueToCopy = getVirtualAccountDetailsToCopy({
      bankAccount2,
      upiAddress,
    });
    const showTestPaymentBtn = mode === 'test' && virtualaccount.status === 'active';
    const rblBankExpiryDate = new Date('2023-11-01');
    let closeByContent = () => <span>No closing date</span>;
    if (!isClosed && this.state.isEditSingleVaMid) {
      closeByContent = () => (
        <EditExpiry
          value={virtualaccount.close_by}
          editFn={updateCloseByDate}
          entityId={virtualaccount.id}
          isRoleAllowedEdit={true}
          isExpireByRequired={!!virtualaccount.close_by}
          entityName={virtualaccount.entity}
        />
      );
    } else if (virtualaccount.close_by) {
      closeByContent = () => (
        <Time
          value={isClosed ? virtualaccount.closed_at : virtualaccount.close_by}
          format="DD MMM YYYY, hh:mm a"
        />
      );
    }

    return (
      <div className="content-wrapper content-sm txn-details VirtualAccount--Details">
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div className="panel panel-default SliderPanel">
            <div className="panel-heading">
              <i className="i i-account-balance text-success icon--formal" />{' '}
              <strong>{virtualaccount.id}</strong>
            </div>
            <div className="SliderPanel__Body">
              <div className="panel-body">
                {isRblAccountMigrated && (
                  <Alert.Warning iconBefore="i-warning">
                    Share new customer identifier details with your customers to accept payments.
                    Your older customer identifiers will not accept payments from 1 Nov 2023.
                    <BladeLink
                      target="_blank"
                      href={AXIS_BANK_MIGRATION_FAQ}
                      rel="noreferrer noopener"
                    >
                      {' '}
                      Why ?
                    </BladeLink>
                  </Alert.Warning>
                )}
                <div className="VirtualAccountDetails">
                  <EntityDetailRow
                    label={
                      <b>
                        {bankAccount2
                          ? 'Old Customer Identifier Details'
                          : 'Customer Identifier Details'}
                      </b>
                    }
                  >
                    {isRblAccountMigrated ? (
                      <CustomClipboard
                        value={valueToCopy}
                        onCopy={() => {
                          onCopy(virtualaccount);
                        }}
                      >
                        <div style={{ fontSize: '12px' }}>
                          {new Date() > rblBankExpiryDate ? 'Expired' : 'Expires on 1 Nov'}
                        </div>
                      </CustomClipboard>
                    ) : (
                      <CustomClipboard
                        value={valueToCopy}
                        onCopy={() => {
                          onCopy(virtualaccount);
                        }}
                      >
                        <div className="copy btn btn-link no-padding">Copy Details</div>
                      </CustomClipboard>
                    )}
                  </EntityDetailRow>

                  <div className="divider" />
                  <AccountDetails
                    bankAccount1={bankAccount2 || bankAccount1}
                    upiAddress={!bankAccount2 && upiAddress}
                  />
                  {bankAccount2 && (
                    <div>
                      <div className="divider" />
                      <EntityDetailRow
                        label={
                          <b style={{ color: '#58666e', fontSize: '14px' }}>
                            New Customer Identifier Details
                          </b>
                        }
                      >
                        <CustomClipboard
                          value={bankAccount2valueToCopy}
                          onCopy={() => {
                            onCopy(virtualaccount);
                          }}
                        >
                          <div className="copy btn btn-link no-padding">Copy Details</div>
                        </CustomClipboard>
                      </EntityDetailRow>
                      <AccountDetails bankAccount1={bankAccount1} upiAddress={upiAddress} />
                    </div>
                  )}
                </div>

                {!user.isVACreationBankAccountDisabled && !isClosed && !hasBankAccount && (
                  <>
                    <br />

                    <button className="btn btn-default" onClick={this.openEnableTransferModeModal}>
                      Enable Customer Identifier Transfer
                    </button>
                  </>
                )}

                {!isClosed && !upiAddress && !isTestMode && (
                  <>
                    <br />

                    <button className="btn btn-default" onClick={this.openEnableTransferModeModal}>
                      Enable UPI Transfer
                    </button>
                  </>
                )}

                <div style={{ margin: '24px 0' }}>
                  <EntityDetailRow label="Amount Paid">
                    <Amount value={virtualaccount.amount_paid} currency="INR" />
                  </EntityDetailRow>

                  <EntityDetailRow label="Status">
                    <VirtualAccountStatusLabel status={virtualaccount.status} />
                  </EntityDetailRow>

                  {virtualaccount.allowed_payers && (
                    <EntityDetailRow label="Third Party Validation">
                      <AllowedPayersList allowedPayers={virtualaccount.allowed_payers} />
                    </EntityDetailRow>
                  )}

                  <EntityDetailRow
                    label="Customer Identifier Description"
                    value={virtualaccount.description}
                  />

                  <EntityDetailRow label="Customer Id" value={virtualaccount.customer_id} />

                  <EntityDetailRow label="Created At">
                    <Time value={virtualaccount.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                  </EntityDetailRow>

                  <EntityDetailRow
                    label={isClosed ? 'Closed At' : 'Close By'}
                    value={closeByContent}
                  />

                  {/* Notes */}
                  <EntityDetailRow label="Notes">
                    {virtualaccount.notes && Object.keys(virtualaccount.notes).length === 0
                      ? '--'
                      : virtualaccount?.notes &&
                        Object.keys(virtualaccount?.notes).map((key, index) => (
                          <div className="m-b" key={index}>
                            <Definition>
                              {key}
                              {String(virtualaccount?.notes[key])}
                              <i />
                            </Definition>
                          </div>
                        ))}
                  </EntityDetailRow>
                </div>

                {virtualaccount.status !== 'closed' ? (
                  <button className="btn btn-default" onClick={() => onClose(virtualaccount)}>
                    Close Customer Identifier
                  </button>
                ) : null}

                {showTestPaymentBtn && (
                  <Banner className="VA-test-payment">
                    <Button onClick={onMakeTestPaymentClick}>Make a Test Payment</Button>

                    <div>
                      <strong>Test Mode:</strong> Make a test payment to this customer identifier.
                    </div>
                  </Banner>
                )}

                <hr />

                <div>
                  <Box
                    display="flex"
                    flexDirection="row"
                    justifyContent="space-between"
                    alignItems="center"
                  >
                    <p className="text-muted" style={{ lineHeight: '35px' }}>
                      Last 10 payments on this customer identifier
                    </p>
                    <Link
                      className="pull-right"
                      to={`/smartcollect/payments/?virtual_account_id=${virtualaccount.id}`}
                      onClick={() => this.props.track('view_payments')}
                    >
                      View All Payments
                    </Link>
                  </Box>
                  <Table rows={va_payments} columns={[_paymentId(), amount]} showHeaders={false} />
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}

export default connect(
  (state) => ({
    user: state.session.user,
    isTestMode: state.session.mode === 'test',
  }),
  {
    openModal,
    closeModal,
    showNotification,
    updateVirtualAccountDetails,
    fetchFeatureStatus,
  },
)(withSplitzService(VirtualAccountDetails));

const AllowedPayersList = ({ allowedPayers }) => (
  <table className="allowed-payers-list">
    <thead>
      <tr>
        <th>IFSC Code</th>
        <th>Acc. Number</th>
      </tr>
    </thead>
    <tbody>
      {allowedPayers.map(({ bank_account, idx }) => (
        <tr key={idx}>
          <td>{bank_account.ifsc}</td>
          <td>{bank_account.account_number}</td>
        </tr>
      ))}
    </tbody>
  </table>
);
