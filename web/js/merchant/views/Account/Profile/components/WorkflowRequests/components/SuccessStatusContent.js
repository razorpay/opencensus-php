import React from 'react';
import BankAccountUpdateStatus from './BankAccountUpdateStatus';
import { hideWorkflowStatus } from '../utils';

const SuccessStatusContent = ({ isBankAccountUpdateWorkflow, content, user }) => {
  if (isBankAccountUpdateWorkflow) {
    const onCloseIconClick = () => {
      hideWorkflowStatus(user.id);
    };
    return (
      <BankAccountUpdateStatus
        content={content}
        statusType="Success"
        showCloseIcon
        onCloseIconClick={onCloseIconClick}
      />
    );
  }
  return <div className="workflow-status success">{content}</div>;
};

export default SuccessStatusContent;
