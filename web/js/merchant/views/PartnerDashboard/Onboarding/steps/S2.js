import React, { useEffect } from 'react';
import SlideController from './SlideController';
import PartnerSelectBox from './PartnerTypeSelector';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { track } from 'merchant/views/PartnerDashboard/Onboarding/ga';
import {
  getOnContactSupportClicked,
  getOnPrivacyPolicyClicked,
  getOnTnCClicked,
} from 'merchant/views/PartnerDashboard/Onboarding/steps/helpers/analytics';
import { ONBOARDING_LABELS } from 'merchant/views/PartnerDashboard/constants';
import ShowWhen from 'merchant/components/ShowWhen';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { MALAYSIAN_FOOTER_LINKS, FOOTER_LINKS } from 'merchant/components/Footer/index';

const TERM_CONDITION_LINK = {
  rzp: 'https://razorpay.com/s/terms-partners/',
  curlec: 'https://curlec.com/partnerships-terms-and-conditions/',
};

const PRIVACY_LINK = {
  rzp: FOOTER_LINKS[2].link,
  curlec: MALAYSIAN_FOOTER_LINKS[1].link,
};

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
  onCompleteClick,
  orgDetails,
  isOrgCurlec,
}) => {
  const orgCode = orgDetails.custom_code;
  const orgName = orgDetails.business_name;

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
    return onCompleteClick();
  };

  useEffect(() => {
    const container = document.querySelector('.partner-onboarding-base-screen');
    container.classList.add('step-2');
    return () => {
      container.classList.remove('step-2');
    };
  }, []);

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
  const onContactSupportClicked = getOnContactSupportClicked(screenName, handleOtherCTAClicks);
  const onPrivacyPolicyClicked = getOnPrivacyPolicyClicked(screenName, handleOtherCTAClicks);
  const onTnCClicked = getOnTnCClicked(screenName, handleOtherCTAClicks);

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
        <div
          className={`partner-illustration${isOrgCurlec ? ' curlec-partner-illustration' : ''}`}
        />
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
              <ShowWhen
                additionalCondition={(user) =>
                  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.ReferalLinks)
                }
              >
                <li> Refer using referral links </li>
              </ShowWhen>
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
              <ShowWhen
                additionalCondition={(user) =>
                  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PartnershipProgram)
                }
              >
                <li>Not applicable for RazorpayX Current account and Corporate Cards</li>
              </ShowWhen>
            </ul>
          </PartnerSelectBox>
        </div>
        <div className="bottom-container">
          <ShowWhen
            additionalCondition={(user) =>
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PartnershipProgram)
            }
          >
            <p style={{ marginTop: '10px' }}>
              Want to become a Platform Partner?
              <a
                href="https://razorpay.com/support/"
                target="_blank"
                onClick={onContactSupportClicked}
                rel="noopener noreferrer"
              >
                &nbsp;Contact Support <i className="i i-external-link " />
              </a>
            </p>
          </ShowWhen>
          <p>
            <a
              onClick={() => {
                abort?.();
                handleOtherCTAClicks('Other Razorpay Products');
              }}
              target="_blank"
              rel="noreferrer noopener"
              style={{
                textDecoration: 'underline',
                color: '#57666E',
              }}
            >
              I just want to use {orgName} products
            </a>
          </p>
          <p>
            By signing up you agree to our{' '}
            <a
              href={PRIVACY_LINK[orgCode]}
              target="_blank"
              onClick={onPrivacyPolicyClicked}
              rel="noopener noreferrer"
            >
              privacy policy
            </a>{' '}
            and{' '}
            <a
              href={TERM_CONDITION_LINK[orgCode]}
              target="_blank"
              className="highlight"
              onClick={onTnCClicked}
              rel="noreferrer noopener"
            >
              terms of use.
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
        nextBtnLabel={ONBOARDING_LABELS.GET_STARTED}
        nextBtnPendingLabel={ONBOARDING_LABELS.GET_STARTED_PENDING}
      />
    </>
  );
};

export default S2;
