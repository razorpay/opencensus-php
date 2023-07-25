import React from 'react';
import Amount from 'common/ui/Amount';
import { connect } from 'react-redux';

function CurrentBalance({ currentBalance }) {
  const balance = currentBalance.data?.balance || 0;

  return (
    <div class="balances-container">
      <div class="bal-cont-header">
        <div class="balances-lhs-container">
          <div class="balance-type-container">
            <p>Current Balance</p>
          </div>
          <div class="balance-amount-container">
            {balance < 0 && <p class="negative-marker">-</p>}
            <Amount
              value={Math.abs(balance)}
              currency="INR"
              className={balance < 0 ? 'negative-balance' : ''}
            />
          </div>
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    ...state.session,
    currentBalance: state.home.current_balance,
  };
};

export default connect(mapStateToProps, null)(CurrentBalance);
