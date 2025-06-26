import React from 'react';
import { Box, Text, Heading, List, ListItem, ListItemText } from '@razorpay/blade/components';

import logoEdumerge from 'assets/onboarding/logo-edumerge.svg';
import logoDukaan from 'assets/onboarding/logo-dukaan.svg';
import logoDjubo from 'assets/onboarding/logo-djubo.svg';
import logoShopaccino from 'assets/onboarding/logo-shopaccino.svg';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { track } from 'merchant/views/PartnerDashboard/Onboarding/ga';

import SlideContoller from './SlideController';

const PARTNER_PROGRAM_URL = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: 'https://razorpay.com/partners/',
  [ORG_CUSTOM_CODE_MAP.CURLEC]: 'https://curlec.com/docs/partners/',
};

const PARTNER_LOGOS = [
  {
    src: logoDukaan,
    alt: 'logo-dukaan',
    id: 'dukaan',
  },
  {
    src: logoEdumerge,
    alt: 'logo-edumerge',
    id: 'edumerge',
  },
  {
    src: logoDjubo,
    alt: 'logo-djubo',
    id: 'djubo',
  },
  {
    src: logoShopaccino,
    alt: 'logo-shopaccino',
    id: 'shopaccino',
  },
];

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
    <Box display="flex" flexDirection="column" height="380px">
      <Box flex="1" maxWidth="55%">
        <Heading size="large" weight="medium">
          Razorpay Partner Program
        </Heading>
        {orgCode === ORG_CUSTOM_CODE_MAP.CURLEC ? (
          <>
            <div className="" style={{ marginTop: '20px' }}>
              <p className="">
                Refer Merchants to a complete suite of payment products. What's more, get rewarded
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
            <Text marginTop="spacing.3" size="large" color="surface.text.staticBlack.normal">
              Designed for digital enablers helping businesses go or grow online.
            </Text>
            <Box display="flex" marginY="spacing.7" gap="spacing.5">
              {PARTNER_LOGOS.map((logo) => (
                <img key={logo.id} src={logo.src} alt={logo.alt} style={{ minHeight: '26px' }} />
              ))}
            </Box>
            <Box>
              <Heading size="large" weight="medium">
                Wondering if you're a fit?
              </Heading>
              <List variant="ordered" marginTop="spacing.3" size="large">
                <ListItem>
                  <ListItemText color="surface.text.staticBlack.normal">
                    Do you offer web development, consulting, or SaaS/ERP solutions?
                  </ListItemText>
                </ListItem>
                <ListItem>
                  <ListItemText color="surface.text.staticBlack.normal">
                    Do your clients need reliable payment solutions?
                  </ListItemText>
                </ListItem>
              </List>
            </Box>
          </>
        )}
      </Box>

      <Box>
        <SlideContoller
          sliderProps={props.sliderProps}
          onNext={handleNextClick}
          nextBtnLabel="Yes, I'm a Fit"
        />
      </Box>
    </Box>
  );
};

export default S0;
