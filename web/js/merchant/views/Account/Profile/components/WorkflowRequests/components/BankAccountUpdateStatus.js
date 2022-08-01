import React from 'react';
import Alert from 'common/new-ui/Alert';

const BankAccountUpdateStatus = ({
  content,
  statusType = 'Warning',
  className,
  showCloseIcon,
  onCloseIconClick,
}) => {
  const AlertType = Alert[statusType];
  return (
    <div className="bank-account-update-status">
      <AlertType
        className={className}
        iconBefore="i-info-outline"
        showCloseIcon={showCloseIcon}
        onCloseIconClick={onCloseIconClick}
      >
        {content}
      </AlertType>
    </div>
  );
};

export default BankAccountUpdateStatus;
