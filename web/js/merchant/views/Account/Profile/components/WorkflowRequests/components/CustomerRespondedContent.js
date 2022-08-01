import React from 'react';
import BankAccountUpdateStatus from './BankAccountUpdateStatus';

const CustomerRespondedContent = ({ isBankAccountUpdateWorkflow, content }) => {
  if (isBankAccountUpdateWorkflow) {
    return <BankAccountUpdateStatus content={content} />;
  }
  return <div className="workflow-status inprogress">{content}</div>;
};

export default CustomerRespondedContent;
