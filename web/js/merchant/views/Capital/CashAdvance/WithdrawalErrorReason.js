import React from 'react';

import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';

function WithdrawalErrorReason({
  closeModal,
  amounts,
  repayDues,
  trackGA,
  description,
  texts,
  comparsionIcon,
}) {
  return (
    <div className="repayment-modal withdrawals">
      <ModalHeader
        className="header"
        title={
          <div className="flex">
            <img src={require(`assets/capital/question_circle.svg`)} alt="Loading icon" /> &nbsp;
            <p>Why Can’t I withdraw?</p>
          </div>
        }
        onCloseClick={() => {
          trackGA({
            eventAction: 'Low Balance | Close Icon',
          });
          closeModal();
        }}
      />
      <small className="text-fade description">Check the reason for not able to withdraw money.</small>
      <div className="overflow-box">
        <div className="repayment-amount-details">
          <p className="no-margin">{description}</p>
        </div>
        <div className="flex p-l p-r m-all">
          <div
            className="panel-body full-width table-bordered no-margin"
            style={{ borderColor: 'rgba(240, 81, 80, 0.82)' }}
          >
            <div className="no-margin">
              <Amount value={amounts.requested} parentQuerySelector=".repayment-modal" />
            </div>
            <span className="amount__title">{texts.amount.left.title}</span>
            <p className="text-small text-faded amount__subTitle">{texts.amount.left.subtitle}</p>
          </div>
          <h3 className="p-all"> {comparsionIcon} </h3>
          <div className="panel-body full-width table-bordered no-margin">
            <div className="no-margin">
              <Amount value={amounts.threshold} parentQuerySelector=".repayment-modal" />
            </div>
            <span className="amount__title">{texts.amount.right.title}</span>
            <p className="text-small text-faded amount__subTitle">{texts.amount.right.subtitle}</p>
          </div>
        </div>
        <div className="p-l p-r m-all flex repayment-modal__ctas">
          <Button.Transparent
            className="full-width no-margin"
            onClick={() => {
              trackGA({
                eventAction: "Low Balance | I'll do later",
              });

              closeModal();
            }}
          >
            <strong>Okay, I'll do later</strong>
          </Button.Transparent>
          <button
            className="Button--primary Button m-l full-width no-margin"
            onClick={() => {
              trackGA({
                eventAction: 'Low Balance | Repay Dues',
              });

              closeModal();
              return repayDues();
            }}
          >
            Repay Dues
          </button>
        </div>
      </div>
      <div className="summary p-b">{texts.footerText}</div>
    </div>
  );
}

export default WithdrawalErrorReason;
