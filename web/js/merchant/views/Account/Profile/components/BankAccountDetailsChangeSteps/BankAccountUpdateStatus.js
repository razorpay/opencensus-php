import React from 'react';

const BankAccountUpdateStatus = ({
  data: { icon, title, subtitle } = {},
  onButtonClick,
  buttonText,
}) => {
  return (
    <div className="bank-details-change-states-wrapper">
      <div className="bank-details-change-state">
        {icon && <i className={`bank-details-icon bank-details-icon--${icon}`} />}
        <div className="bank-details-header">{title}</div>
        <div>{subtitle}</div>
        <div className="form-actions">
          <button type="button" className="btn" onClick={onButtonClick}>
            {buttonText}
          </button>
        </div>
      </div>
    </div>
  );
};

export default BankAccountUpdateStatus;
