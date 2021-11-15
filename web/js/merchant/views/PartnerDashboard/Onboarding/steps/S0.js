import React from 'react';
import SlideContoller from './SlideController';
import { track } from '../ga';

const S0 = (props) => {
  const handleNextClick = () => {
    props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner.welcome', {
        merchantId: props.merchantId,
        lpVariant: props.lpVariant,
        lpFold: props.lpFold,
      }),
    );

    if (window.trackHubs) {
      window.trackHubs({
        name: 'update_property',
        data: {
          partner_dashboard_welcome_module: true,
        },
      });
    }

    track({
      eventAction: 'New User Welcome Screen',
      eventLabel: `Partner Onboarding | Next | ${props.businessTypeName}`,
    });
  };

  const handleLearnMoreClick = () => {
    props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner.welcome.learn_about_program', {
        merchantId: props.merchantId,
        lpVariant: props.lpVariant,
        lpFold: props.lpFold,
      }),
    );
  };

  return (
    <>
      <div className="partner-onbr-info">
        <div class="title">Welcome to your Partner Dashboard</div>
        <div className="" style={{ marginTop: '20px' }}>
          <p className="">
            Refer Merchants to a complete suite of payment products. What’s more, get rewarded for
            it!
          </p>
          <p style={{ marginTop: '20px' }}>
            From SaaS companies to Freelancers, our Partner Program is for anyone who can offer or
            advocate online payments.
          </p>
        </div>
        <div className="learn-more">
          <a
            href="https://razorpay.com/partners/"
            target="_blank"
            className=""
            style={{
              textDecoration: 'none',
              color: '#518FF0',
              marginTop: '40px',
            }}
            onClick={handleLearnMoreClick}
            rel="noreferrer"
          >
            Learn more about Partner Program <i className="i i-external-link " />
          </a>
        </div>
      </div>
      <SlideContoller sliderProps={props.sliderProps} onNext={handleNextClick} />
    </>
  );
};

export default S0;
