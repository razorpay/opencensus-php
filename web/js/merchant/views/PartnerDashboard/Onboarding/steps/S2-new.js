import React, { useEffect } from 'react';
import { classList } from 'common/utils/rzp-utils';
import SlideController from './SlideController';
import PartnerSelectBox from './PartnerTypeSelector';

const s2 = ({ role, onRoleSelect, sliderProps, abort, tracking, merchantId }) => {
  const handleNextClick = () => {
    tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner.type.next', {
        merchantId: merchantId,
        partnerType: role,
      }),
    );
  };

  const handleOtherCTAClicks = (action) => {
    tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_type_otherCTAs.selected', {
        merchantId: merchantId,
        otherCTA: action,
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

  return (
    <>
      <div className={'partner-onbr-info step-2'}>
        <div className="partner-illustration"></div>
        <div className="title">Choose your partnership type</div>
        <div className="options-group select-partner-type-options">
          <PartnerSelectBox
            label={'Reseller Partner'}
            onClick={() => onRoleSelect('reseller')}
            checked={role === 'reseller'}
            icon={'/dist/css/assets/onboarding/reseller-icon.svg'}
            description={'Refer your connections and get rewarded'}
            hoverContent={
              'Freelancer, Startup Incubator, Entrepreneur, Influencer, Blogger, Web developer, Designer etc'
            }
          >
            <ul>
              <li> Earn referral bonus </li>
              <li> Get automated commissions </li>
              <li> Refer using referral links </li>
            </ul>
          </PartnerSelectBox>
          <PartnerSelectBox
            label={'Aggregator Partner'}
            onClick={() => onRoleSelect('aggregator')}
            checked={role === 'aggregator'}
            icon={'/dist/css/assets/onboarding/aggregator-icon.svg'}
            description={'Manage account and payment cycle of your merchants'}
            hoverContent={
              'Businesses that manage end-to-end payment collection for their customers. Eg: Restaurants, ERP'
            }
          >
            <ul>
              <li> Manage merchant account </li>
              <li> Earn referral bonus </li>
              <li> Get automated commissions </li>
              <li>
                {' '}
                Requires{' '}
                <a
                  href="https://razorpay.com/docs/partners/aggregators-integration/"
                  target="_blank"
                >
                  (Partner Auth)
                </a>{' '}
                integration{' '}
              </li>
            </ul>
          </PartnerSelectBox>
          <PartnerSelectBox
            label={'Platform Partner'}
            onClick={() => onRoleSelect('pure_platform')}
            checked={role === 'pure_platform'}
            icon={'/dist/css/assets/onboarding/pure-platform-icon.svg'}
            description={
              'Enable your merchants to process payments via Razorpay on your app/website'
            }
            hoverContent={
              'Businesses that can benefit from providing a seamless payments experience to their merchants within their website / app. Eg: Zoho, Quickbooks'
            }
          >
            <ul>
              <li> Get a customized commission plan </li>
              <li>
                {' '}
                Requires{' '}
                <a href="https://razorpay.com/docs/oauth/" target="_blank">
                  (Razorpay OAuth)
                </a>{' '}
                integration{' '}
              </li>
            </ul>
          </PartnerSelectBox>
        </div>
        <div>
          <p style={{ marginTop: '10px' }}>
            Not able to find partner type?
            <a
              href="https://razorpay.com/support/"
              target="_blank"
              onClick={() => handleOtherCTAClicks('Contact Support')}
            >
              &nbsp;Contact Support <i className="i i-external-link " />
            </a>
          </p>
          <p>
            <a
              onClick={() => {
                abort && abort();
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
            sliderProps.prev && sliderProps.prev();
            handleOtherCTAClicks('back button');
          },
        }}
        disNext={!role}
        onNext={handleNextClick}
      />
    </>
  );
};

export default s2;
