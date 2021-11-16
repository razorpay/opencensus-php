import React, { useEffect } from 'react';
import SlideController from './SlideController';
import PartnerSelectBox from './PartnerTypeSelector';
import { track } from '../ga';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const S2 = ({
  role,
  onRoleSelect,
  sliderProps,
  abort,
  tracking,
  merchantId,
  isMobile,
  lpVariant,
  lpFold,
  businessTypeName,
  screenName,
}) => {
  const handleNextClick = () => {
    tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner.type.next', {
        merchantId,
        partnerType: role,
        lpVariant,
        lpFold,
      }),
    );

    track({
      eventAction: 'Select - Type',
      eventLabel: `Partner Onboarding | Next | ${businessTypeName}`,
    });

    analyticsTrack({
      objectName: 'Partner Select Type',
      actionName: 'next clicked',
      screen: screenName,
      properties: {
        location: 'partner onboarding base screen',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toCleverTap: true,
    });
  };

  const handleOtherCTAClicks = (action) => {
    tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_type_otherCTAs.selected', {
        merchantId,
        otherCTA: action,
        lpVariant,
        lpFold,
      }),
    );
  };

  useEffect(() => {
    const container = document.querySelector('.partner-onboarding-base-screen');
    container.classList.add('step-2');
    return () => {
      container.classList.remove('step-2');
    };
  }, []);

  useEffect(() => {
    const closeButton = document.querySelector('.partner-onboarding-base-screen button.close');
    const closeClickHandler = () => {
      handleOtherCTAClicks('Cancel');
    };
    if (closeButton) {
      closeButton.addEventListener('click', closeClickHandler);
    }

    const prevDotIcons = document.querySelectorAll(
      '.partner-onboarding-base-screen .SliderDots-dot',
    );
    const dotIconClickHandler = () => {
      handleOtherCTAClicks('Dot icon');
    };
    prevDotIcons.forEach((item) => {
      item.addEventListener('click', dotIconClickHandler);
    });

    return () => {
      closeButton?.removeEventListener('click', closeClickHandler);
      prevDotIcons.forEach((item) => () => {
        item.removeEventListener('click', dotIconClickHandler);
      });
    };
  }, []);

  return (
    <>
      <div className="partner-onbr-info step-2">
        <div className="partner-illustration" />
        <div className="title">Choose your Partnership&nbsp;Type</div>
        <div className="options-group select-partner-type-options">
          <PartnerSelectBox
            label="Reseller Partner"
            onClick={() => onRoleSelect('reseller')}
            checked={role === 'reseller'}
            icon="/dist/css/assets/onboarding/reseller-icon.svg"
            description="Refer your connections and get rewarded"
            hoverContent="Freelancer, Startup Incubator, Entrepreneur, Influencer, Blogger, Web developer, Designer etc"
            isMobile={isMobile}
          >
            <ul>
              <li> Earn referral bonus </li>
              <li> Get automated commissions </li>
              <li> Refer using referral links </li>
            </ul>
          </PartnerSelectBox>
          <PartnerSelectBox
            label="Aggregator Partner"
            onClick={() => onRoleSelect('aggregator')}
            checked={role === 'aggregator'}
            icon="/dist/css/assets/onboarding/aggregator-icon.svg"
            description="Manage account and payment cycle of your merchants (Tech integration required)"
            hoverContent="Business that manage end-to-end payment collection for their customers. Eg: ERP, Restaurant Management Platform"
            isMobile={isMobile}
          >
            <ul>
              <li> Manage merchant account </li>
              <li> Earn referral bonus </li>
              <li>
                {' '}
                Requires{' '}
                <a
                  href="https://razorpay.com/docs/partners/aggregators-integration/"
                  target="_blank"
                  onClick={(e) => {
                    handleOtherCTAClicks('Partner Auth link');
                    e.stopPropagation();
                  }}
                  rel="noopener noreferrer"
                >
                  (Partner Auth)
                </a>{' '}
                integration to get automated&nbsp;commissions
              </li>
            </ul>
          </PartnerSelectBox>
        </div>
        <div className="bottom-container">
          <p style={{ marginTop: '10px' }}>
            Want to become a Platform Partner?
            <a
              href="https://razorpay.com/support/"
              target="_blank"
              onClick={() => handleOtherCTAClicks('Contact Support')}
              rel="noopener noreferrer"
            >
              &nbsp;Contact Support <i className="i i-external-link " />
            </a>
          </p>
          <p>
            <a
              onClick={() => {
                abort?.();
                handleOtherCTAClicks('Other Razorpay Products');
              }}
              target="_blank"
              style={{
                textDecoration: 'underline',
                color: '#57666E',
              }}
            >
              I just want to use Razorpay products
            </a>
          </p>
        </div>
      </div>
      <SlideController
        sliderProps={{
          ...sliderProps,
          prev: () => {
            sliderProps?.prev();
            handleOtherCTAClicks('back button');
          },
        }}
        disNext={!role}
        onNext={handleNextClick}
      />
    </>
  );
};

export default S2;
