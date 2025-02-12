import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';

function RepaymentTicketSuccessModal({
  amount,
  closeModal,
  ticketNumber,
  email,
}) {
  return (
    <div className="repayment-modal p-b withdrawals">
      <ModalHeader
        className="header"
        title={
          <div className="flex">
            <img
              height={16}
              src={require(`assets/success-tick-green.svg`)}
              alt="Loading icon"
            />{' '}
            &nbsp;
            <p>Repayment Requested!</p>
          </div>
        }
        onCloseClick={closeModal}
      />
      <p className="text-fade description">
        We have successfully received your repayment request against your
        withdrawals.
      </p>
      <div className="overflow-box">
        <div className="repayment-amount-details">
          Our team will reach you back within 8 working hours to proceed the
          repayment process.
        </div>
        <hr />
        <div className="other-details-wrapper">
          <div className="flex item">
            <p className="no-margin">Repayable Amount</p>
            <p className="pull-right">
              <Amount value={amount} />
            </p>
          </div>
          <div className="flex item">
            <p className="no-margin">Repayment Method</p>
            <p className="pull-right text-right">
              Will be deducted from your settlement Balance
            </p>
          </div>
          <Button.Primary className="full-width m-t m-b" onClick={closeModal}>
            Done
          </Button.Primary>
        </div>
      </div>
      <div className="block-note success p-r m-b text-center">
        <strong>Your Ticket Number is #{ticketNumber}</strong>
        <hr className="m-t m-b" />
        <p>
          A Confirmation mail has been send to &nbsp;
          <strong>{email}</strong>
        </p>
      </div>
    </div>
  );
}

export default RepaymentTicketSuccessModal;
