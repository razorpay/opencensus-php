import React from 'react';
import BankAccountUpdateStatus from './BankAccountUpdateStatus';

const AwaitingCustomerResponseContent = ({
  isBankAccountUpdateWorkflow,
  needsClarificationMessage,
  showAddReplyButton,
  onReplyClick,
}) => {
  if (isBankAccountUpdateWorkflow) {
    const content = (
      <div className="action-required">
        <div className="title">Action required</div>
        We need a few more details to change your bank account - Please submit the required details
        -
        <button className="btn-link" onClick={onReplyClick ? onReplyClick : null}>
          Submit details
        </button>
      </div>
    );
    return (
      <BankAccountUpdateStatus
        content={content}
        statusType="Warning"
        className="needs-clarification"
      />
    );
  }
  return (
    <div className="workflow-status rejected">
      {needsClarificationMessage}
      {showAddReplyButton && (
        <button className="btn btn-link" onClick={onReplyClick ? onReplyClick : null}>
          Add Reply
        </button>
      )}
    </div>
  );
};

export default AwaitingCustomerResponseContent;
