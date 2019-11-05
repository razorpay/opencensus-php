import react from 'react';
import moment from 'moment';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import ModalHeader from 'rzp/ui/ModalHeader';
import AccountDetails, { getVirtualAccountDetails } from './AccountDetails';
import { getVirtualAccountDetailsToCopy } from './AccountDetails';

const AccountDetailsSummary = ({
  modalTitle,
  closeModal,
  virtualAccount,
  onCopy = () => {},
  showUPIAddressDetails = true,
  showBankAccountDetails = true,
}) => {
  const { bankAccount, upiAddress } = getVirtualAccountDetails(virtualAccount);
  const valueToCopy = getVirtualAccountDetailsToCopy({
    bankAccount,
    upiAddress,
  });

  return (
    <div>
      <ModalHeader title={modalTitle} onCloseClick={closeModal} />

      <div class="modal-body">
        <p class="text-muted">
          Share the following information with the customer to accept payments
        </p>

        <br />

        <AccountDetails
          bankAccount={bankAccount}
          upiAddress={upiAddress}
          showUPIAddressDetails={showUPIAddressDetails}
          showBankAccountDetails={showBankAccountDetails}
        />

        {virtualAccount.close_by && (
          <div class="form-group">
            <div class="text-muted">Close By</div>
            <div>
              <b>
                {moment(virtualAccount.close_by * 1000).format(
                  'DD MMM YYYY, hh:mm:ss a'
                )}
              </b>
            </div>
          </div>
        )}

        <br />

        <CustomClipboard
          value={valueToCopy}
          onCopy={() => {
            onCopy(virtualAccount);
          }}
        >
          <button type="button" class="btn btn-primary btn-block m-t">
            Copy Account Details
          </button>
        </CustomClipboard>
      </div>
    </div>
  );
};

export default AccountDetailsSummary;
