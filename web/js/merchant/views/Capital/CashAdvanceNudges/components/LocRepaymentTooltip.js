import '../styles/LocRepaymentTooltip.styl';
import React, { useEffect, useState } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { fetchInstallments as fnFetchInstallments } from 'merchant/reducers/capital/withdrawals';
import { getNextRepayBreakup } from 'merchant/views/Capital/CashAdvance/OverviewFooter/utils';
import { AsyncBtn } from 'common/new-ui/Button';
import Spinner from 'common/ui/Spinner';
import Amount from 'common/ui/Amount';
import { getCurrentBalance } from '../utils';
import { handleRepayment, updateRepaymentData } from '../api';
import { EVENT_TYPES, trackRepayNowCtaClickOnHoldTootip } from '../analytics';

const LocRepaymentTooltip = (props) => {
  const [isLoading, setLoading] = useState(false);
  const [isRepaymentInProgress, setRepaymentInProgress] = useState(false);
  const {
    user,
    withdrawalConfiguration,
    installments,
    showNotification,
    fetchInstallments,
  } = props;
  const { nextRepayInterestAmount, nextRepayPrincipalAmount } = getNextRepayBreakup(
    installments || [],
  );
  const nextRepayAmount = nextRepayInterestAmount + nextRepayPrincipalAmount;
  const screen = window.location.pathname.includes('settlements')
    ? 'PG Dashboard | Settlements'
    : 'PG Home';
  const properties = {
    cash_advance_limit: Number(withdrawalConfiguration?.data?.configuration?.internal_credit_limit),
    current_balance: getCurrentBalance(withdrawalConfiguration),
  };

  useEffect(() => {
    if (withdrawalConfiguration?.data) {
      trackRepayNowCtaClickOnHoldTootip({
        screen,
        action: EVENT_TYPES.RENDERED,
        properties,
      });
    }
  }, [withdrawalConfiguration]);

  useEffect(() => {
    const getInstallments = async () => {
      setLoading(true);
      await fetchInstallments({
        owner_id: user.current,
        from: moment().startOf('day').unix(),
        to: moment().add(30, 'days').unix(),
      });
      setLoading(false);
    };

    getInstallments();
  }, [fetchInstallments, user]);

  const updateRepaymentcallback = (params) => {
    updateRepaymentData(...params).then(async () => {
      showNotification({
        type: 'success',
        message: 'Repayment received successfully!',
      });
      await fetchInstallments({
        owner_id: user.current,
        from: moment().startOf('day').unix(),
        to: moment().add(30, 'days').unix(),
      });
      setRepaymentInProgress(false);
    });
  };

  const onRepaymentError = () => {
    showNotification({
      type: 'error',
      message: 'Something went wrong!',
    });
    setRepaymentInProgress(false);
  };

  const handleClick = () => {
    trackRepayNowCtaClickOnHoldTootip({
      screen,
      action: EVENT_TYPES.CLICKED,
      properties,
    });
    setRepaymentInProgress(true);
    handleRepayment(user, nextRepayAmount, updateRepaymentcallback, onRepaymentError);
  };

  if (isLoading)
    return (
      <div className="loc-repayment-tooltip spinner-container">
        <Spinner />
      </div>
    );

  if (nextRepayAmount > 0) {
    return (
      <div className="loc-repayment-tooltip">
        <div className="title">
          <Amount className="repay-amount-title" value={nextRepayAmount} hidePaisa />
          <span> due for Cash Advance</span>
        </div>
        <div className="subtitle">
          To be able to settle your funds, please repay the due amout now
        </div>

        <AsyncBtn.Transparent
          className="repay-btn"
          isPending={isRepaymentInProgress}
          type="button"
          onClick={handleClick}
        >
          REPAY NOW
        </AsyncBtn.Transparent>
      </div>
    );
  }

  return (
    <div className="disable-ondemand-msg">
      On-demand Instant Settlements have been disabled because you have delayed the repayments on{' '}
      <span className="highlight-tooltip"> LOC.</span>
      <br /> <br />
      Please complete the repayments to re-enable Instant Settlements.
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  installments: state.withdrawals.installments,
  withdrawalConfiguration: state.withdrawals.withdrawalConfiguration,
});

export default connect(mapStateToProps, {
  showNotification: fnShowNotification,
  fetchInstallments: fnFetchInstallments,
})(LocRepaymentTooltip);
