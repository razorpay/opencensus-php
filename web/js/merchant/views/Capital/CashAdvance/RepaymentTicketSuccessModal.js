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
    <div class="repayment-modal p-b withdrawals">
      <ModalHeader
        class="header"
        title={
          <div class="flex">
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
      <p class="text-fade description">
        We have successfully received your repayment request against your
        withdrawals.
      </p>
      <div className="overflow-box">
        <div class="repayment-amount-details">
          Our team will reach you back within 8 working hours to proceed the
          repayment process.
        </div>
        <hr />
        <div class="other-details-wrapper">
          <div class="flex item">
            <p class="no-margin">Repayable Amount</p>
            <p class="pull-right">
              <Amount value={amount} />
            </p>
          </div>
          <div class="flex item">
            <p class="no-margin">Repayment Method</p>
            <p class="pull-right text-right">
              Will be deducted from your settlement Balance
            </p>
          </div>
          <Button.Primary class="full-width m-t m-b" onClick={closeModal}>
            Done
          </Button.Primary>
        </div>
      </div>
      <div className="block-note success p-r m-b text-center">
        <strong>Your Ticket Number is #{ticketNumber}</strong>
        <hr class="m-t m-b" />
        <p>
          A Confirmation mail has been send to &nbsp;
          <strong>{email}</strong>
        </p>
      </div>
    </div>
  );
}

export default RepaymentTicketSuccessModal;
