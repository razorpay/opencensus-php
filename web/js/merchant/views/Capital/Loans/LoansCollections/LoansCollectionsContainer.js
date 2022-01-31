import React from 'react';
import { NavLink, withRouter } from 'react-router-dom';
import Overview from './Overview';
import RepaymentHistory from './RepaymentHistory';
import { LOANS_SECTIONS, LOANS_BASE_URL } from '../constants';
import { calculateLoanBreakup } from './util';
import { PLAN_STATUS } from './constants';
import Button from 'common/new-ui/Button';
// import NoPermission from './NoPermission';
import { connect } from 'react-redux';

const LoansCollectionsContainer = ({
  match: {
    params: { section = LOANS_SECTIONS.OVERVIEW },
  },
  loanData,
  onRefresh,
  // user,
  history,
}) => {
  const { installments } = loanData;
  const { totalPrincipalAmount, totalInterestAmount } = calculateLoanBreakup(
    installments.installments || [],
  );
  const renderSection = () => {
    switch (section) {
      default:
      case LOANS_SECTIONS.OVERVIEW: {
        return <Overview {...loanData} onRefresh={onRefresh} />;
      }
      case LOANS_SECTIONS.REPAYMENTS_HISTORY: {
        return (
          <RepaymentHistory
            {...loanData}
            onRefresh={onRefresh}
            loanAmount={totalPrincipalAmount + totalInterestAmount}
          />
        );
      }
    }
  };

  const onTabClick = () => {
    const isPaymentSuccess = document.getElementsByClassName('payment_suc_fail__msg');
    if (isPaymentSuccess.length) {
      onRefresh();
    }
  };

  const onApplyForNewLoan = () => {
    history.push(`${LOANS_BASE_URL}apply`);
  };

  const isPlanClosed = loanData.plan.status === PLAN_STATUS.COMPLETED;

  // disabling till feature flag is
  // const isPlanActive = loanData.plan.status === PLAN_STATUS.CREATED;

  // if (!user.isLoansCollectionsEnabled && !isPlanActive) {
  //   return <NoPermission />;
  // }

  return (
    <div className="cash-advance-container">
      <tabbed-container>
        <h1 className="cash-advance-title">Business Loan</h1>
        <header className="loans-nav-items">
          <div>
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
          </div>
          {isPlanClosed ? (
            <div className="apply-loan-button-wrapper">
              <Button onClick={onApplyForNewLoan} className="btn Button--primary apply-loan-btn">
                Apply for New Loan
              </Button>
            </div>
          ) : null}
        </header>
        <content className="cash-advance-body">
          <div className="loans-collections-wrapper">{renderSection()}</div>
        </content>
      </tabbed-container>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default withRouter(connect(mapStateToProps)(LoansCollectionsContainer));
