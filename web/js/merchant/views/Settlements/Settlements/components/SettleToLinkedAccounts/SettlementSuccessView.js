import React from 'react';
import Amount from 'common/ui/Amount';

const SettlementSuccessView = ({ amount }) => {
  return (
    <>
      <div className="breakup">
        <div className="dropdown-1">
          <div className="currency-big-1">
            <Amount value={amount} currency="INR" parentQuerySelector=".breakup" />
          </div>
        </div>
      </div>
      <div className="help-block">
        The settlement has been initiated and should be reflected on your linked bank accounts in
        some time.
      </div>
    </>
  );
};

export default SettlementSuccessView;
