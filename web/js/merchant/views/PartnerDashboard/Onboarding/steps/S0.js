import React from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { track } from 'merchant/views/PartnerDashboard/Onboarding/ga';

import SlideContoller from './SlideController';

const PARTNER_PROGRAM_URL = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: 'https://razorpay.com/partners/',
  [ORG_CUSTOM_CODE_MAP.CURLEC]: 'https://curlec.com/docs/partners/',
};

const S0 = (props) => {
  const orgCode = props.orgDetails.custom_code;
  const partnerProgramUrl = PARTNER_PROGRAM_URL[orgCode] || PARTNER_PROGRAM_URL.rzp;

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

    analyticsTrack({
      objectName: 'New User Welcome Screen',
      actionName: 'next clicked',
      screen: props.screenName,
      properties: {
        location: 'partner onboarding base screen',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toCleverTap: true,
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
            href={partnerProgramUrl}
            target="_blank"
            className=""
            style={{
              textDecoration: 'none',
              color: '#518FF0',
              marginTop: '40px',
            }}
            onClick={handleLearnMoreClick}
            rel="noreferrer noopener"
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
