import React, { useEffect, useState } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import { getActivationState } from 'merchant/components/Activation/ActivationUtils';
import { merchantFetch } from 'merchant/utils/ajax';
import { formatNumberWithCommas } from 'common/utils/numerals';
import { paiseToRupees } from 'common/utils/rzp-utils';

const PaymentProgressBar = ({ user, mode, history, limitBreach }) => {
  const [paymentProgress, setPaymentProgress] = useState(0);
  const [content, setContent] = useState(null);
  const [button, setButton] = useState();

  useEffect(() => {
    if (limitBreach?.amount && limitBreach?.amount < limitBreach?.limit) {
      merchantFetch({
        url: 'merchant/analytics',
        method: 'post',
        data: {
          filters: {
            default: [
              {
                created_at: {
                  gte: user.createdAt,
                },
              },
            ],
          },
          aggregations: {
            transactionVolume: {
              agg_type: 'sum',
              details: {
                index: 'payments',
                column: 'base_amount',
                mode: mode,
              },
            },
          },
        },
      }).then((PaymentProgressData) => {
        if (PaymentProgressData?.data?.transactionVolume) {
          const payment = PaymentProgressData?.data?.transactionVolume?.result?.value;
          setPaymentProgress({
            paymentProgress: paiseToRupees(payment),
          });
        }
      });
    }
  }, []);

  useEffect(() => {
    const activationState = getActivationState(user, user.isUnregisteredBusiness);
    let activationFlowContent, activationFlowButton;

    const limitBreachHappened =
      !!limitBreach && limitBreach.type === 'payment_breach'
        ? (limitBreach.amount * 100) / limitBreach.limit >= 100
        : false;

    switch (activationState) {
      case 'L1_instantly_activated':
      case 'poi_verified': {
        activationFlowContent = limitBreachHappened
          ? 'You have reached the payments limit of  ₹15,000 for now. To extend the limit complete your KYC and get it approved'
          : 'You can accept payments upto ₹15,000 for now. To extend the limit complete your KYC and get it approved';
        activationFlowButton = (
          <button className="btn btn-primary" onClick={() => history.push('/activation')}>
            Complete KYC
          </button>
        );
        break;
      }
      default:
        break;
    }

    setContent(activationFlowContent);
    setButton(activationFlowButton);
  }, []);

  const limitBreachHappened =
    !!limitBreach && limitBreach.type === 'payment_breach'
      ? (limitBreach.amount * 100) / limitBreach.limit >= 100
      : false;

  return (
    <>
      {content && (user.activated || limitBreach?.amount) ? (
        <div className="PaymentProgressBar-onboarding">
          <div className="info">{content}</div>
          <ProgressBarInfo
            credits={limitBreach?.limit || '15000'} // hardcoding to 15k untill BE gives the value
            accepted={limitBreachHappened ? limitBreach?.amount : paymentProgress}
          />
          {button ? button : null}
        </div>
      ) : null}
    </>
  );
};

const ProgressBarInfo = ({ accepted, credits }) => {
  const percentage = (accepted * 100) / credits;

  return (
    <div className="progressbar">
      <div class="amount-total">
        <span
          class="amount-accepted"
          style={{ color: percentage >= 100 ? '#D12D2D' : 'rgb(62, 122, 235)' }}
        >
          ₹ {formatNumberWithCommas(accepted)}
        </span>{' '}
        / ₹ {formatNumberWithCommas(credits)}
      </div>
      <ProgressBar
        width="100%"
        percent={percentage}
        ProgressColor={percentage >= 100 ? '#D12D2D' : 'rgb(62, 122, 235)'}
      />
    </div>
  );
};

const ProgressBar = ({ width, percent, backgroundColor, ProgressColor }) => {
  return (
    <div>
      <div
        className="progress-div"
        style={{ width: width, backgroundColor: backgroundColor || 'rgb(233, 233, 233)' }}
      >
        <div
          style={{
            width: `${percent || 0}%`,
            backgroundColor: ProgressColor || 'rgb(62, 122, 235)',
          }}
          className="progress-container"
        />
      </div>
    </div>
  );
};

export default compose(
  withRouter,
  connect((state) => ({
    limitBreach: state.home.limitBreach,
  })),
  RTracking(() => window.rzpQ.component('PaymentProgressBar')),
)(PaymentProgressBar);
