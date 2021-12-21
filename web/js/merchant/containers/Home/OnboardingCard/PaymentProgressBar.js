import React, { useEffect, useState } from 'react';
import { withRouter } from 'react-router-dom';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import { getActivationState } from 'merchant/components/Activation/ActivationUtils';
import { merchantFetch } from 'merchant/utils/ajax';
import { formatNumberWithCommas } from 'common/utils/numerals';
import { paiseToRupees } from 'common/utils/rzp-utils';
import Time from 'common/ui/Time';

const PaymentProgressBar = ({ user, mode, history, limitBreach }) => {
  const [paymentProgress, setPaymentProgress] = useState(0);
  const [lastUpdatedTime, setLastUpdateTime] = useState(0);
  const [content, setContent] = useState(null);
  const [button, setButton] = useState();

  useEffect(() => {
    if (user.activation_form_milestone === 'L1') {
      merchantFetch({
        url: 'merchant/analytics',
        method: 'post',
        data: {
          filters: {
            default: [
              {
                created_at: { gte: user.created_at, lte: new Date().getTime() },
                authorized_at: { gt: 0 },
              },
            ],
          },
          aggregations: {
            transactionVolume: {
              agg_type: 'sum',
              details: {
                index: 'payments',
                column: 'base_amount',
                mode,
              },
            },
          },
        },
      }).then((PaymentProgressData) => {
        if (PaymentProgressData?.data?.transactionVolume) {
          const payment = PaymentProgressData?.data?.transactionVolume?.result[0].value;
          setPaymentProgress(paiseToRupees(payment));
          setLastUpdateTime(PaymentProgressData?.data?.transactionVolume?.last_updated_at);
        }
      });
    }
  }, [mode]);

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
          <button
            className="btn btn-primary"
            onClick={() => history.push(user.isActivationFormFullView ? '/kyc' : '/activation')}
          >
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
  }, [mode]);

  const isLatestTransaction = limitBreach?.amount > paymentProgress;

  return (
    // eslint-disable-next-line react/jsx-no-useless-fragment
    <React.Fragment>
      {content && user.activated ? (
        <React.Fragment>
          <div className="PaymentProgressBar-onboarding">
            <div className="info">{content}</div>
            <ProgressBarInfo
              credits={limitBreach?.limit || '15000'}
              accepted={isLatestTransaction ? limitBreach?.amount : paymentProgress}
            />
            {button ? button : null}
          </div>
          {limitBreach.escaltionsLastUpdatedAt || lastUpdatedTime ? (
            <div className="status-info">
              <small>
                <i className="i i-info-outline" />
                &nbsp;
                <span>
                  Payment volume last updated{' '}
                  <Time
                    value={
                      isLatestTransaction ? limitBreach.escaltionsLastUpdatedAt : lastUpdatedTime
                    }
                    relative
                  />
                </span>
              </small>
            </div>
          ) : null}
        </React.Fragment>
      ) : null}
    </React.Fragment>
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

const ProgressBar = ({ width, percent, backgroundColor: bgColor, ProgressColor }) => {
  return (
    <div>
      <div
        className="progress-div"
        style={{ width, backgroundColor: bgColor || 'rgb(233, 233, 233)' }}
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
  rTracking(() => window.rzpQ.component('PaymentProgressBar')),
)(PaymentProgressBar);
