import React from 'react';
import Amount from 'common/ui/Amount';
import { REPAYMENT_VIEWS, REPAYMENT_USER_METHODS_TYPE } from '../constants';

const RepaySuccess = ({ setView, resultAmounts }) => {
  const handleDoneClick = () => {
    setView(REPAYMENT_VIEWS.SUMMARY);
  };
  const userRepayMethodText = REPAYMENT_USER_METHODS_TYPE[resultAmounts.userRepayMethod] || '';
  return (
    <div className="success">
      <div className="flex">
        <img
          className="mr-8"
          height={20}
          src="/dist/css/assets/success-tick-green.svg"
          alt="Success Tick"
        />
        <h1 className="title">Repayment Successful!</h1>
      </div>
      <div className="description">
        <p>
          Your next repayable amount has been successfully repaid from{' '}
          {resultAmounts.settlementAmount !== 0 ? 'Settlement Balance' : ''}
          {resultAmounts.settlementAmount !== 0 && resultAmounts.bankAmount !== 0 ? ' & ' : ''}
          {resultAmounts.bankAmount !== 0 ? userRepayMethodText : ''}.
          <br />
          Your remaining settlement balance is{' '}
          <Amount
            className="repay--amount"
            currency="INR"
            value={resultAmounts.remainingSettlementBalance * 100}
          />
          .
        </p>
      </div>
      <div className="flex">
        <div className="details flex">
          <div className="total-repaid">
            <div className="details--heading">Total Repaid</div>
            <div className="details--amount">
              <Amount currency="INR" value={resultAmounts.repayAmount * 100} />
            </div>
          </div>
          {resultAmounts.interestAmount !== 0 && (
            <div className="interest-paid">
              <div className="details--heading">Interest Repaid</div>
              <div className="details--amount">
                <Amount currency="INR" value={resultAmounts.interestAmount * 100} />
              </div>
            </div>
          )}
          {resultAmounts.principalAmount !== 0 && (
            <div className="principal-repaid">
              <div className="details--heading">Principal Repaid</div>
              <div className="details--amount">
                <Amount currency="INR" value={resultAmounts.principalAmount * 100} />
              </div>
            </div>
          )}
          <div className="repayment-via">
            <div className="details--heading">Repayment Via</div>
            <div className="flex">
              {resultAmounts.settlementAmount !== 0 && (
                <div>
                  <div
                    className={`settlement-title settlement-balance${
                      resultAmounts.bankAmount !== 0 ? ' border-right' : ''
                    }`}
                  >
                    Settlement Balance
                  </div>
                  <div>
                    <Amount currency="INR" value={resultAmounts.settlementAmount * 100} />
                  </div>
                </div>
              )}
              {resultAmounts.bankAmount !== 0 && (
                <div>
                  <div className="settlement-title">{userRepayMethodText}</div>
                  <div>
                    <Amount currency="INR" value={resultAmounts.bankAmount * 100} />
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
        <div className="done-btn">
          <button className="btn btn-primary mt-11" onClick={handleDoneClick}>
            Done
          </button>
        </div>
      </div>
    </div>
  );
};

export default RepaySuccess;
