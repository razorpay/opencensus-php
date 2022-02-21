import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import { TENURE_UNIT_LABELS } from '../Loans/constants';

function RepaymentModal({ closeModal, creditOffer }) {
  return (
    <div class="repayment-modal">
      <ModalHeader class="header" title="Repayment Details" onCloseClick={closeModal} />
      <small class="text-fade description">Check repayment details of disbursed loan here.</small>
      <div className="overflow-box">
        <div class="repayment-amount-details">
          <div class="flex">
            <p class="full-width no-margin">A daily repayment amount</p>
            <h4>
              <Amount value={creditOffer.installment.amount} />
            </h4>
          </div>
          <p class="text-small text-fade">
            Will be collected from your transactions once the loan is disbursed.
          </p>
        </div>
        <hr />
        <div class="other-details-wrapper">
          <div class="flex item">
            <p class="no-margin">Rate of Interest</p>
            <p class="pull-right">{creditOffer.loan_attributes.interest_rate}%</p>
          </div>
          <div class="flex item">
            <p class="no-margin">Tenure</p>
            <p class="pull-right">
              {creditOffer.installment.tenure} &nbsp;
              {creditOffer.installment.tenure === 1
                ? TENURE_UNIT_LABELS[creditOffer.installment.tenure_unit][0]
                : TENURE_UNIT_LABELS[creditOffer.installment.tenure_unit][1]}
            </p>
          </div>
          <div class="flex item">
            <p class="no-margin">EWI</p>
            <p class="pull-right">
              <Amount value={creditOffer?.installment?.amount * 7} />
            </p>
          </div>
        </div>
      </div>
      <div className="summary">
        <Amount value={creditOffer.installment.amount} /> will be collected as an Equated daily
        installment from your customer transactions.
        <Button.Primary class="full-width m-t m-b" onClick={closeModal}>
          Done
        </Button.Primary>
      </div>
    </div>
  );
}

export default RepaymentModal;
