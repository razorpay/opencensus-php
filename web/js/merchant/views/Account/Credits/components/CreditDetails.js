import React from 'react';
import { connect } from 'react-redux';
import Amount from 'common/ui/Amount';
import { openModal } from 'merchant_common/reducers/modals';
import {
  clickHistoryCreditsGA,
  CLICK_ADD_FEE_CREDITS,
  CLICK_ADD_REFUND_CREDITS,
  FEE_CREDITS_ADDED,
  FEE_CREDITS_FAILED,
  REFUND_CREDITS_ADDED,
  REFUND_CREDITS_FAILED,
} from '../ga';
import ViewCreditHistoryTable from './ViewCreditHistoryTable';
import AddFundsForm from 'common/ui/AddFundsForm';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchCreditBalance } from 'merchantLA/reducers/credits';
import { analyticsTrack } from 'common/utils/analytics';

function CreditDetails({
  title,
  description,
  openModal,
  showNotification,
  user,
  type,
  setStatus,
  totalCredits,
  fetchCreditBalance,
  creditItems,
}) {
  const addCredits = (transaction) => {
    return new Promise((resolve, reject) => {
      if (transaction.razorpay_payment_id) {
        resolve(true);
      } else {
        reject('Payment failed');
      }
    })
      .then((_) => {
        fetchCreditBalance();
        showNotification({
          type: 'success',
          message: 'Credits added successfully',
        });
        type === 'fee' && analyticsTrack(FEE_CREDITS_ADDED);
        type === 'refund' && analyticsTrack(REFUND_CREDITS_ADDED);
      })
      .catch((error) => {
        setStatus({
          type: 'error',
          message: error,
        });
        type === 'fee' && analyticsTrack(FEE_CREDITS_FAILED);
        type === 'refund' && analyticsTrack(REFUND_CREDITS_FAILED);
      });
  };

  const addCreditsHandler = () => {
    const analyticsData =
      title === 'Fee Credits' ? CLICK_ADD_FEE_CREDITS : CLICK_ADD_REFUND_CREDITS;
    analyticsTrack(analyticsData);
    openModal({
      size: 'small',
      component: (
        <AddFundsForm type={type} addHandler={addCredits} user={user} statusHandler={setStatus} />
      ),
    });
  };

  return (
    <div class="balances-container">
      <div class="bal-cont-header">
        <div class="balances-lhs-container">
          <div class="balance-type-container">
            <p>{title}</p>
          </div>
          <div class="balance-amount-container">
            <Amount value={Math.abs(totalCredits)} currency="INR" />
          </div>
        </div>
        <div class="balances-add-funds">
          {user.isSelfServeCreditsEnabled && (
            <button class="btn btn-outline" onClick={() => addCreditsHandler(type)}>
              Add {title}
            </button>
          )}
        </div>
      </div>

      <div class="bal-cont-footer">
        <p>{description}</p>
      </div>
      <div class="coupon-details">
        {user.isSelfServeCreditsEnabled && (
          <div class="view-history">
            <button
              class="btn-link toggle-history"
              onClick={() => {
                openModal({
                  size: 'large',
                  component: <ViewCreditHistoryTable creditItems={creditItems} title={title} />,
                });
                analyticsTrack(clickHistoryCreditsGA(title));
              }}
            >
              View History
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  ...state.session,
});

export default connect(mapStateToProps, { openModal, showNotification, fetchCreditBalance })(
  CreditDetails,
);
