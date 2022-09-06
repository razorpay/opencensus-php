import './SettleToLinkedAccounts.styl';
import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import Amount, { AmountTooltip } from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import { AsyncBtn } from 'common/new-ui/Button';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { fetchRouteOndemandSettlements as fnfetchRouteOndemandSettlements } from 'merchant/reducers/collection';
import { getLinkedAccountsBalance, settleLinkedAccountsBalance } from '../api';

const SettleToLinkedAccount = ({
  user: { merchant },
  confirm,
  onLinkedAccountsSettlementSuccess,
  showNotification,
  fetchRouteOndemandSettlements,
}) => {
  const [loading, setLoading] = useState(true);
  const [balance, setBalance] = useState(0);

  const showErrorNotification = (message) => {
    showNotification({
      type: 'error',
      message,
    });
  };

  useEffect(() => {
    getLinkedAccountsBalance()
      .then((res) => {
        if (res.success) {
          setBalance(parseInt(res?.data?.balance || 0, 10));
        } else {
          showErrorNotification('Error fetching balance, Please try again!');
        }
      })
      .catch(() => {
        showErrorNotification('Error fetching balance, Please try again!');
      })
      .finally(() => {
        setLoading(false);
      });
  }, []);

  const renderConfirmation = () => (
    <div className="m-b">
      The settlement amount is:{` `}
      <span className="bold-amount">
        <Amount value={balance} currency="INR" parentQuerySelector=".modal-body" />
      </span>
    </div>
  );

  const handleAction = () => {
    return settleLinkedAccountsBalance(merchant.id, balance)
      .then((res) => {
        if (res.success) {
          onLinkedAccountsSettlementSuccess(balance);
          fetchRouteOndemandSettlements({
            count: 25,
            skip: 0,
          });
        } else {
          showErrorNotification('Something went wrong, Please try again!');
        }
      })
      .catch(() => {
        showErrorNotification('Something went wrong, Please try again!');
      });
  };

  const handleConfirm = () => {
    confirm({
      header: 'Are you sure you want to do this settlement?',
      className: 'route-settlements-modal',
      affirmativeLabel: 'Yes, Settle',
      abortLabel: "No, Don't ",
      message: renderConfirmation,
      action: handleAction,
    });
  };

  if (loading) {
    return (
      <div className="spinner-wrapper-center">
        <Spinner />
      </div>
    );
  }

  return (
    <>
      <div className="InputGroup Input Input--vTop linked-account-input">
        <Input
          label="Amount pending to be settled"
          required={false}
          addonBefore={<AmountTooltip currency="INR" parentQuerySelector=".Modal" />}
          autoFocus={false}
          name="amount"
          className="Input Input--Amount"
          defaultValue={balance / 100}
          disabled={true}
        />
      </div>
      <div>
        <div className="settlement-message">
          {balance
            ? 'The amount will be setlled to your linked accounts'
            : 'Your linked accounts don’t have any pending settlements.'}
        </div>
        <AsyncBtn.Primary disabled={!balance} className="submit-btn" onClick={handleConfirm}>
          Confirm
        </AsyncBtn.Primary>
      </div>
    </>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  showNotification: fnShowNotification,
  fetchRouteOndemandSettlements: fnfetchRouteOndemandSettlements,
})(SettleToLinkedAccount);
