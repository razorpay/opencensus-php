import moment from 'moment';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import ModalHeader from 'common/ui/ModalHeader';
import AccountDetails, {
  getVirtualAccountDetails,
  getVirtualAccountDetailsToCopy,
} from 'merchant/views/SmartCollect/VirtualAccounts/components/AccountDetails';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

const AccountDetailsSummary = ({
  modalTitle,
  closeModal,
  virtualAccount,
  onCopy = () => {},
  showUPIAddressDetails = true,
  showBankAccountDetails = true,
}) => {
  const { bankAccount1, bankAccount2, upiAddress } = getVirtualAccountDetails(virtualAccount);
  const valueToCopy = getVirtualAccountDetailsToCopy({
    bankAccount1,
    bankAccount2,
    upiAddress,
  });

  return (
    <div className="VirtualAccountSummary">
      <ModalHeader title={modalTitle} onCloseClick={closeModal} />

      <div className="modal-body">
        <p className="text-muted">
          Share the following information with the customer to accept payments
        </p>

        <br />

        <AccountDetails
          bankAccount1={bankAccount1}
          bankAccount2={bankAccount2}
          upiAddress={upiAddress}
          showUPIAddressDetails={showUPIAddressDetails}
          showBankAccountDetails={showBankAccountDetails}
        />

        {virtualAccount.close_by && (
          <EntityDetailRow label="Close By">
            <b>{moment(virtualAccount.close_by * 1000).format('DD MMM YYYY, hh:mm a')}</b>
          </EntityDetailRow>
        )}

        <br />

        <CustomClipboard
          value={valueToCopy}
          onCopy={() => {
            onCopy(virtualAccount);
          }}
        >
          <button type="button" className="btn btn-primary btn-block m-t">
            Copy Customer Identifier Details
          </button>
        </CustomClipboard>
      </div>
    </div>
  );
};

export default AccountDetailsSummary;
