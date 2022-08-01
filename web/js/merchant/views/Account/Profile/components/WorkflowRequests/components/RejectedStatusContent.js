import React from 'react';
import BankAccountUpdateStatus from './BankAccountUpdateStatus';
import { hideWorkflowStatus } from '../utils';

const RejectedStatusContent = ({ isBankAccountUpdateWorkflow, content, user }) => {
  if (isBankAccountUpdateWorkflow) {
    const onCloseIconClick = () => {
      hideWorkflowStatus(user.id);
    };
    return (
      <BankAccountUpdateStatus
        content="Your bank account change request was rejected. Please check your email for details."
        statusType="Error"
        showCloseIcon={true}
        onCloseIconClick={onCloseIconClick}
      />
    );
  }
  return <div className="workflow-status rejected">{content}</div>;
};

export default RejectedStatusContent;
