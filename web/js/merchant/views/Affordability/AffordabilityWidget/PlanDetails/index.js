import { Chip } from 'merchant/views/Affordability/components/chip';
import MethodBlocks from './MethodBlocks';
import Query from './Query';
import moment from 'moment';
import { useEffect, useState } from 'react';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import track from './track';

const PlanDetails = ({ affordability, user, loading }) => {
  const { pricing, enabled, lastAction, widget_enable_source } = affordability;
  const [isEnabled, setIsEnabled] = useState(enabled);
  let formattedDate, actionYear;
  if (!enabled && lastAction && lastAction.createdTime && lastAction.name === 'DISABLE') {
    // From the API side we are receiving the value in seconds and type is string
    // therefore we need to convert it to number first and convert to miliseconds
    // In case of state updates we need to keep the value as it is
    const lastActionDate = new Date(
      typeof lastAction.createdTime === 'number'
        ? lastAction.createdTime
        : Number(`${lastAction.createdTime}000`),
    );
    formattedDate = moment(lastActionDate).format('MMMM Do');
    actionYear = moment(lastActionDate).year();
  }

  useEffect(() => {
    track.planDetailsRender();
  }, []);

  useEffect(() => {
    setIsEnabled(enabled);
  }, [enabled]);

  const trackCustomizationGuide = () => {
    track.customizeWidget();
  };

  const trackSupportClick = () => {
    track.requestHelp();
  };

  const showPlanDetailsSection = false; // temporarily hidding the plan detail section

  return (
    <div className="plans-wrapper">
      {showPlanDetailsSection ? <p className="caption">Plan Details</p> : null}
      <div className="plan-details-grid">
        <div className="plan-details">
          {showPlanDetailsSection ? (
            <div className="plan-details-wrapper block">
              <div>
                <p className="plan-heading">{isEnabled ? 'Current Plan' : 'Last Active Plan'}</p>
                {loading ? (
                  <span className="PlaceholderLoader" />
                ) : (
                  <p className="plan-value">{!pricing || !pricing?.rate ? 'Free' : 'Paid'}</p>
                )}
              </div>
              {!isEnabled ? (
                <div>
                  <p className="plan-heading">Disabled On</p>
                  <p className="plan-value">{formattedDate}</p>
                  <p>{actionYear}</p>
                </div>
              ) : null}
              <div>
                <p className="plan-heading">
                  {!isEnabled ? 'Last ' : ''} Payment <span>(Excluding GST)</span>
                </p>
                <p className="plan-value">
                  {!pricing || !pricing?.rate ? (
                    '₹0'
                  ) : (
                    <>
                      ₹{pricing.rate / 100}
                      {pricing.default && pricing.rate < pricing.default ? (
                        <>
                          <span className="discounted-price">₹{pricing.default / 100}</span>
                          <Chip text="Special Offer!" type="offer" />
                        </>
                      ) : null}
                    </>
                  )}
                </p>
                <p>per month</p>
              </div>
              {isEnabled ? (
                <div className="customize-widget">
                  <p className="plan-heading">Customising your Widget</p>
                  <a
                    href="https://razorpay.com/docs/payments/payment-gateway/affordability/widget#customise-the-widget"
                    onClick={trackCustomizationGuide}
                    className="plan-value btn btn-link"
                    target="_blank"
                    rel="noreferrer noopener"
                  >
                    View Guide <i className="i i-external-link" />
                  </a>
                </div>
              ) : null}
              <div className="hidden-xs">
                <p className="plan-heading">In case of any queries</p>
                <a
                  target="_blank"
                  href="https://razorpay.com/support/#request"
                  className="plan-value btn btn-link link"
                  rel="noreferrer noopener"
                  onClick={trackSupportClick}
                >
                  Contact Support
                </a>
              </div>
            </div>
          ) : null}
          {isEnabled ? (
            <div className="configure-wrapper">
              <p className="caption config-head">Configure</p>
              <div className="offers-wrapper">
                <MethodBlocks />
              </div>
            </div>
          ) : null}
        </div>
        <Query enabled={isEnabled} user={user} source={widget_enable_source} />
      </div>
    </div>
  );
};

// eslint-disable-next-line babel/new-cap
export default compose(RTracking(() => window.rzpQ.component('PlanDetails'))(PlanDetails));
