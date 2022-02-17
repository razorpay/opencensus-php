import ModalHeader from 'common/ui/ModalHeader';
import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import { closeModal as closeModalReducer } from 'merchant_common/reducers/modals';
import copyToClipboard from 'common/utils/copyToClipboard';
import Questions from './Questions';
import Button from 'common/new-ui/Button';

function BankTransferDetails({ closeModal, bankDetails, onBack }) {
  const [isVerifiedDetailsOpen, setIsVerifiedDetailsOpen] = useState(false);
  const [isFaqsOpen, setIsFaqsOpen] = useState(false);

  const handleIsVerifiedDetailsOpen = () => {
    setIsVerifiedDetailsOpen((prev) => !prev);
  };

  return (
    <>
      <ModalHeader
        title={isFaqsOpen ? 'FAQ’s Bank Transfer Details' : 'Bank Transfer Details'}
        onCloseClick={() => {
          closeModal();
        }}
      />
      <div className="modal-body">
        {isFaqsOpen ? (
          <Questions onCloseClick={() => setIsFaqsOpen(false)} />
        ) : (
          <>
            <div className="bank-details-note">
              <p>
                Funds can only be added from bank account linked to your Razorpay verified account.
              </p>
            </div>
            <div className="verified-account-details-wrapper m-t p-b">
              <div className="account-query">
                <strong>
                  <button
                    onClick={handleIsVerifiedDetailsOpen}
                    type="button"
                    className="Btn--link Button--transparent Button"
                  >
                    View Verified Account{' '}
                    {isVerifiedDetailsOpen ? (
                      <i className="i i-chevron-up" />
                    ) : (
                      <i className="i i-chevron-down" />
                    )}
                  </button>
                </strong>
                <strong>
                  <button
                    onClick={() => setIsFaqsOpen(!isFaqsOpen)}
                    type="button"
                    className="Btn--link Button--transparent Button"
                  >
                    FAQ&apos;s
                  </button>
                </strong>
              </div>

              {isVerifiedDetailsOpen && (
                <div className="verified-account-details m-t m-b">
                  {bankDetails.allowed_payers.map(
                    ({ bank_account: { bank_name, account_number, ifsc } }) => (
                      <div className="verified-account-detail m-l" key={ifsc}>
                        <div className="detail detail-first">
                          <span className="bank-name">{`${bank_name}`}</span>
                          <span className="bank-acc-number">{account_number}</span>
                        </div>
                      </div>
                    ),
                  )}
                </div>
              )}
            </div>
            <div className="account-details">
              <h5 className="m-t m-b">Account Details (IMPS, NEFT, RTGS)</h5>
              <form className="m-t">
                <div className="form-group account-holder">
                  <label>Name:</label>
                  <div className="input-wrapper">
                    <input
                      type="text"
                      placeholder="Name"
                      name="accoutHolder"
                      className="form-control"
                      value={bankDetails.receivers[0].name}
                      readOnly
                    />
                    <button
                      type="button"
                      data-tip="Copied"
                      data-event="active"
                      onClick={() => {
                        copyToClipboard(bankDetails.receivers[0].name);
                      }}
                      className="Btn--link Button--transparent Button"
                    >
                      <i className="fa fa-clone" />
                    </button>
                  </div>
                </div>

                <div className="form-group account-no">
                  <label>Account No:</label>
                  <div className="input-wrapper">
                    <input
                      type="text"
                      placeholder="Account No."
                      name="accountNo"
                      className="form-control"
                      value={bankDetails.receivers[0].account_number}
                      readOnly
                    />
                    <button
                      type="button"
                      onClick={() => {
                        copyToClipboard(bankDetails.receivers[0].account_number);
                      }}
                      data-tip="Copied"
                      data-event="active"
                      className="Btn--link Button--transparent Button"
                    >
                      <i className="fa fa-clone" />
                    </button>
                  </div>
                </div>

                <div className="form-group ifsc-code">
                  <label>IFSC Code:</label>
                  <div className="input-wrapper">
                    <input
                      type="text"
                      placeholder="IFSC Code"
                      name="ifscCode"
                      className="form-control"
                      value={bankDetails.receivers[0].ifsc}
                      readOnly
                    />
                    <button
                      type="button"
                      data-tip="Copied"
                      data-event="active"
                      onClick={() => {
                        copyToClipboard(bankDetails?.receivers[0]?.ifsc);
                      }}
                      className="Btn--link Button--transparent Button"
                    >
                      <i className="fa fa-clone" />
                    </button>
                  </div>
                </div>

                {onBack && typeof onBack === 'function' && (
                  <div className="Modal__actions">
                    <Button.Primary
                      type="button"
                      className="btn btn-primary btn-block"
                      onClick={onBack}
                    >
                      Go Back
                    </Button.Primary>
                  </div>
                )}
              </form>
            </div>
          </>
        )}
      </div>
    </>
  );
}
const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ closeModal: closeModalReducer }, dispatch);

export default compose(connect(null, mapDispatchToProps))(BankTransferDetails);
