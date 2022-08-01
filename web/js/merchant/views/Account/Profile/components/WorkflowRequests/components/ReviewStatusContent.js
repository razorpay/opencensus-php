import React from 'react';
import BankAccountUpdateStatus from './BankAccountUpdateStatus';

const ReviewStatusContent = ({ isBankAccountUpdateWorkflow, content }) => {
  if (isBankAccountUpdateWorkflow) {
    return <BankAccountUpdateStatus content={content} />;
  }
  return <div className="workflow-status inprogress">{content}</div>;
};

export default ReviewStatusContent;
