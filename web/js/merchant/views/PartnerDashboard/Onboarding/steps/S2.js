import React, { useEffect } from 'react';
import { useI18Service } from 'common/i18';

import SlideController from './SlideController';
import PartnerSelectBox from './PartnerTypeSelector';
import { getOnContactSupportClicked } from 'merchant/views/PartnerDashboard/Onboarding/steps/helpers/analytics';
import TnCFooter from 'merchant/views/PartnerDashboard/Onboarding/steps/TnCFooter';
import { ONBOARDING_LABELS } from 'merchant/views/PartnerDashboard/constants';
import ShowWhen from 'merchant/components/ShowWhen';

const S2 = ({
  abort,
  handleOtherCTAClicks,
  isMobile,
  isOrgCurlec,
  onCompleteClick,
  onRoleSelect,
  orgDetails,
  role,
  screenName,
  sliderProps,
}) => {
  const orgName = orgDetails.business_name;
  const { isConfigTagEnabled } = useI18Service();

  useEffect(() => {
    const container = document.querySelector('.partner-onboarding-base-screen');
    container.classList.add('step-2');
    return () => {
      container.classList.remove('step-2');
    };
  }, []);

  const onContactSupportClicked = getOnContactSupportClicked(screenName, handleOtherCTAClicks);

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
            icon={require("assets/onboarding/reseller-icon.svg")}
            description="Refer your connections and get rewarded"
            hoverContent="Freelancer, Startup Incubator, Entrepreneur, Influencer, Blogger, Web developer, Designer etc"
            isMobile={isMobile}
          >
            <ul>
              <li> No cap on Earnings </li>
              <li> Get automated commissions </li>
              <ShowWhen
                additionalCondition={() => !isConfigTagEnabled('partnership.referral_links')}
              >
                <li> Refer using referral links </li>
              </ShowWhen>
            </ul>
          </PartnerSelectBox>
          <PartnerSelectBox
            label="Aggregator Partner"
            onClick={() => onRoleSelect('aggregator')}
            checked={role === 'aggregator'}
            icon={require("assets/onboarding/aggregator-icon.svg")}
            description="Manage account and payment cycle of your merchants (Tech integration required)"
            hoverContent="Business that manage end-to-end payment collection for their customers. Eg: ERP, Restaurant Management Platform"
            isMobile={isMobile}
          >
            <ul>
              <li> Manage merchant account </li>
              <li> No cap on Earnings </li>
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
                additionalCondition={() => !isConfigTagEnabled('partnership.partnership_program')}
              >
                <li>Not applicable for RazorpayX Current account and Corporate Cards</li>
              </ShowWhen>
            </ul>
          </PartnerSelectBox>
        </div>
        <div className="bottom-container">
          <ShowWhen
            additionalCondition={() => !isConfigTagEnabled('partnership.partnership_program')}
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
          <TnCFooter
            orgDetails={orgDetails}
            handleOtherCTAClicks={handleOtherCTAClicks}
            screenName={screenName}
          />
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
        onNext={onCompleteClick}
        nextBtnLabel={ONBOARDING_LABELS.GET_STARTED}
        nextBtnPendingLabel={ONBOARDING_LABELS.GET_STARTED_PENDING}
      />
    </>
  );
};

export default S2;
