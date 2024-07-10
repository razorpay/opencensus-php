import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { track } from 'merchant/views/PartnerDashboard/Onboarding/ga';
import { PARTNERSHIPS_WEBSITE_LINKS } from 'merchant/views/PartnerDashboard/constants';

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
        {orgCode === ORG_CUSTOM_CODE_MAP.CURLEC ? (
          <>
            <div className="" style={{ marginTop: '20px' }}>
              <p className="">
                Refer Merchants to a complete suite of payment products. What’s more, get rewarded
                for it!
              </p>
              <p style={{ marginTop: '20px' }}>
                From SaaS companies to Freelancers, our Partner Program is for anyone who can offer
                or advocate online payments.
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
          </>
        ) : (
          <>
            <div className="" style={{ marginTop: '20px' }}>
              <p className="">
                Get started with referring clients and track all your clients and do more, all
                directly from your partner dashboard.
              </p>
              <p style={{ marginTop: '20px' }}>
                You can also help your clients onboard faster by performing their KYC directly from
                your partner dashboard.
              </p>
            </div>
            <Box marginTop="spacing.6">
              <Text size="small" weight="medium">
                By signing up you agree to our{' '}
                <a
                  href={PARTNERSHIPS_WEBSITE_LINKS.PRIVACY_POLICY_URL}
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  privacy policy
                </a>{' '}
                and{' '}
                <a
                  href={PARTNERSHIPS_WEBSITE_LINKS.TERMS_AND_CONDITION_URL}
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  terms of use
                </a>
              </Text>
            </Box>
          </>
        )}
      </div>
      <SlideContoller sliderProps={props.sliderProps} onNext={handleNextClick} />
    </>
  );
};

export default S0;
