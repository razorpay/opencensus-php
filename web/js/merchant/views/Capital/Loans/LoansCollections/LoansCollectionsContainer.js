import React from 'react';
import { NavLink, withRouter } from 'react-router-dom';
import Overview from './Overview';
import RepaymentHistory from './RepaymentHistory';
import { LOANS_SECTIONS, LOANS_BASE_URL } from '../constants';
import { calculateLoanBreakup } from './util';

const LoansCollectionsContainer = ({
  match: {
    params: { section = LOANS_SECTIONS.OVERVIEW },
  },
  allData,
  onRefresh,
}) => {
  const { installments } = allData;
  const { totalPrincipalAmount, totalInterestAmount } = calculateLoanBreakup(
    installments.installments || [],
  );
  const renderSection = () => {
    switch (section) {
      default:
      case LOANS_SECTIONS.OVERVIEW: {
        return <Overview {...allData} onRefresh={onRefresh} />;
      }
      case LOANS_SECTIONS.REPAYMENTS_HISTORY: {
        return (
          <RepaymentHistory
            {...allData}
            onRefresh={onRefresh}
            loanAmount={totalPrincipalAmount + totalInterestAmount}
          />
        );
      }
    }
  };

  const onTabClick = () => {
    const isPaymentSuccess = document.getElementsByClassName('payment-success');
    if (isPaymentSuccess.length) {
      onRefresh();
    }
  };

  return (
    <div className="cash-advance-container">
      <tabbed-container>
        <h1 className="cash-advance-title">Business Loan</h1>
        <header>
          <NavLink onClick={onTabClick} exact to={`${LOANS_BASE_URL}${LOANS_SECTIONS.OVERVIEW}`}>
            Overview
          </NavLink>
          <NavLink
            onClick={onTabClick}
            exact
            to={`${LOANS_BASE_URL}${LOANS_SECTIONS.REPAYMENTS_HISTORY}`}
          >
            Repayments History
          </NavLink>
        </header>
        <content className="cash-advance-body">
          <div className="loans-collections-wrapper">{renderSection()}</div>
        </content>
      </tabbed-container>
    </div>
  );
};

export default withRouter(LoansCollectionsContainer);
