import React, { useEffect, useMemo } from 'react';
import { classList } from 'common/utils/rzp-utils';
import SlideController from './SlideController';
import PartnerSelectBox from './PartnerTypeSelector';
import { triggerHotjarHeatmap } from 'common/utils/hotjar';

const s2 = ({
  role,
  onRoleSelect,
  sliderProps,
  abort,
  tracking,
  merchantId,
  experimentVariant,
  isMobile,
  lpVariant,
  lpFold
}) => {
  const shouldShowNewScreen = useMemo(() => experimentVariant === 'exposed', [experimentVariant]);
  const handleNextClick = () => {
    tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner.type.next', {
        merchantId: merchantId,
        partnerType: role,
        variant: experimentVariant,
        lpVariant: lpVariant,
        lpFold: lpFold
      }),
    );
  };

  const handleOtherCTAClicks = (action) => {
    tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_type_otherCTAs.selected', {
        merchantId: merchantId,
        otherCTA: action,
        variant: experimentVariant,
        lpVariant: lpVariant,
        lpFold: lpFold
      }),
    );
  };

  useEffect(() => {
    if (shouldShowNewScreen) {
      const container = document.querySelector('.partner-onboarding-base-screen');
      container.classList.add('step-2');
      triggerHotjarHeatmap('pure_platform_exposed_heatmap');
      return () => {
        container.classList.remove('step-2');
      };
    } else {
      triggerHotjarHeatmap('pure_platform_control_heatmap');
    }
  }, [shouldShowNewScreen]);

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
      closeButton && closeButton.removeEventListener('click', closeClickHandler);
      prevDotIcons.forEach((item) => () => {
        item.removeEventListener('click', dotIconClickHandler);
      });
    };
  }, []);

  const getNewScreen = () => {
    return (
      <>
        <div className={'partner-onbr-info step-2'}>
          <div className="partner-illustration"></div>
          <div className="title">Choose your Partnership&nbsp;Type</div>
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
              isMobile={isMobile}
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
                'Business that manage end-to-end payment collection for their customers. Eg: Restaurants, ERP'
              }
              isMobile={isMobile}
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
                    onClick={(e) => {
                      handleOtherCTAClicks('Partner Auth link');
                      e.stopPropagation();
                    }}
                  >
                    (Partner Auth)
                  </a>{' '}
                  integration to earn&nbsp;commission
                </li>
              </ul>
            </PartnerSelectBox>
            <PartnerSelectBox
              label={'Platform Partner'}
              onClick={() => onRoleSelect('pure_platform')}
              checked={role === 'pure_platform'}
              icon={'/dist/css/assets/onboarding/pure-platform-icon.svg'}
              description={
                'Do API integration to enable your merchants to process payments via Razorpay on your platform'
              }
              hoverContent={
                'Business that can benefit from providing a seamless payments experience to their merchants within their website/app. Eg: Zoho, Quickbooks'
              }
              isMobile={isMobile}
            >
              <ul>
                <li>
                  {' '}
                  Requires{' '}
                  <a
                    href="https://razorpay.com/docs/oauth/"
                    target="_blank"
                    onClick={(e) => {
                      handleOtherCTAClicks('Razorpay OAuth link');
                      e.stopPropagation();
                    }}
                  >
                    (Razorpay OAuth)
                  </a>{' '}
                  integration to earn&nbsp;commission
                </li>
                <li> Get automated commissions </li>
              </ul>
            </PartnerSelectBox>
          </div>
          <div className="bottom-container">
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
  // return new variant based on experiment value
  if (shouldShowNewScreen) {
    return getNewScreen();
  }

  // old screen variant
  return (
    <>
      <div className={'partner-onbr-info'}>
        <div className="title">What do you want to do as a Partner?</div>
        <div className="options-group">
          <div
            class={classList(
              'value',
              role !== 'reseller' && 'no-bottom-border',
              role === 'reseller' && 'active',
            )}
            onClick={() => {
              onRoleSelect('reseller');
            }}
          >
            <div style={{ flex: 9 }}>
              <p className="info info-grey">Refer Businesses</p>
              <p>Refer individuals and businesses to Razorpay</p>
            </div>
            <div className="check">
              <i class="i i-check" />
            </div>
          </div>
          <div
            class={classList(
              'value',
              role !== 'aggregator' && 'no-top-border',
              role === 'aggregator' && 'active',
            )}
            onClick={() => onRoleSelect('aggregator')}
          >
            <div style={{ flex: 9 }}>
              <p className="info info-grey">Refer & Manage Merchants</p>
              <p>Refer and manage the payment stack for your referred accounts</p>
            </div>
            <div className="check">
              <i class="i i-check" />
            </div>
          </div>
        </div>
        <div>
          <p style={{ marginTop: '10px' }}>
            For Enterprise solution
            <a
              href="https://razorpay.com/support/"
              target="_blank"
              onClick={() => handleOtherCTAClicks('Contact Support')}
            >
              &nbsp;Contact Support <i className="i i-external-link " />
            </a>
          </p>
          <p style={{ marginTop: '10px' }}>
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
