import React from 'react';
import SlideContoller from './SlideController';

const S0 = (props) => {
  const handleNextClick = () => {
    props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner.welcome', {
        merchantId: props.merchantId,
      }),
    );

    window.trackHubs({
      name: 'update_property',
      data: {
        partner_dashboard_welcome_module: true,
      },
    });
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
        <div style={{ marginTop: '121px', padding: '2px' }}>
          <a
            href="https://razorpay.com/partners/"
            target="_blank"
            className=""
            style={{
              textDecoration: 'none',
              color: '#518FF0',
              marginTop: '40px',
            }}
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
