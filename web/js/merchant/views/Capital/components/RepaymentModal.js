import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import { TENURE_UNIT_LABELS } from '../Loans/constants';

function RepaymentModal({ closeModal, creditOffer }) {
  return (
    <div className="repayment-modal">
      <ModalHeader className="header" title="Repayment Details" onCloseClick={closeModal} />
      <small className="text-fade description">Check repayment details of disbursed loan here.</small>
      <div className="overflow-box">
        <div className="repayment-amount-details">
          <div className="flex">
            <p className="full-width no-margin">A daily repayment amount</p>
            <h4>
              <Amount value={creditOffer.installment.amount} />
            </h4>
          </div>
          <p className="text-small text-fade">
            Will be collected from your transactions once the loan is disbursed.
          </p>
        </div>
        <hr />
        <div className="other-details-wrapper">
          <div className="flex item">
            <p className="no-margin">Rate of Interest</p>
            <p className="pull-right">{creditOffer.loan_attributes.interest_rate}%</p>
          </div>
          <div className="flex item">
            <p className="no-margin">Tenure</p>
            <p className="pull-right">
              {creditOffer.installment.tenure} &nbsp;
              {creditOffer.installment.tenure === 1
                ? TENURE_UNIT_LABELS[creditOffer.installment.tenure_unit][0]
                : TENURE_UNIT_LABELS[creditOffer.installment.tenure_unit][1]}
            </p>
          </div>
          <div className="flex item">
            <p className="no-margin">EWI</p>
            <p className="pull-right">
              <Amount value={creditOffer?.installment?.amount * 7} />
            </p>
          </div>
        </div>
      </div>
      <div className="summary">
        <Amount value={creditOffer.installment.amount} /> will be collected as an Equated daily
        installment from your customer transactions.
        <Button.Primary className="full-width m-t m-b" onClick={closeModal}>
          Done
        </Button.Primary>
      </div>
    </div>
  );
}

export default RepaymentModal;
