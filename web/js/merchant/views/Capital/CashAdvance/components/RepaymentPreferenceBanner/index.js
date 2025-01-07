import './style.styl';
import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import {
  CASH_ADVANCE_BASE_URL,
  CASH_ADVANCE_SECTIONS,
} from 'merchant/views/Capital/CashAdvance/constants';
import {
  setFirstTimeRepaymentPreference,
  getFirstTimeRepaymentPreference,
  isLenderLiquiloans,
} from 'merchant/views/Capital/CashAdvance/utils';

const RepaymentPreferenceBanner = (props) => {
  const { withdrawalConfiguration } = props;
  const hideSuccessBanner =
    getFirstTimeRepaymentPreference() && isLenderLiquiloans(withdrawalConfiguration);

  useEffect(() => {
    setTimeout(() => {
      setFirstTimeRepaymentPreference();
    }, 100);
  }, []);

  if (hideSuccessBanner) return null;

  return (
    <div className="banner-container">
      <img className="banner-icon" src={require("assets/check-round.svg")} alt="Tick icon" />
      <p className="banner-text">
        Your repayment preference for all future withdrawals is set to{' '}
        <strong>
          {withdrawalConfiguration?.data?.configuration?.auto_collection
            ? 'automatic daily deductions'
            : 'manual'}
        </strong>
      </p>

      <NavLink
        className="change-preference-link"
        end
        to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.SETTINGS}`}
      >
        Change Preference
      </NavLink>
    </div>
  );
};

const mapStateToProps = (state) => ({
  withdrawalConfiguration: state.withdrawals.withdrawalConfiguration,
});

export default connect(mapStateToProps)(RepaymentPreferenceBanner);
