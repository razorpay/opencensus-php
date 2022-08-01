import React from 'react';
import Alert from 'common/new-ui/Alert';

const NeedsClarificationContent = ({ isBankAccountUpdateWorkflow, needsClarificationMessage }) => {
  if (isBankAccountUpdateWorkflow) {
    return (
      <>
        <header className="bank-details-header">
          Submit details as per the instruction below:
        </header>
        <Alert.Warning iconBefore="i-comment-info">
          <div className="razorpay-support">
            <div className="title">From: Razorpay Support</div>
            <div className="description">{needsClarificationMessage}</div>
          </div>
        </Alert.Warning>
      </>
    );
  }
  return (
    <>
      <p>
        <strong>Needs Clarification on: </strong>
      </p>
      <p className="text-muted">{needsClarificationMessage}</p>
    </>
  );
};

export default NeedsClarificationContent;
