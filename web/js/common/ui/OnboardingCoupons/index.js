import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import Button from 'common/new-ui/Button';
import { showProductsModal } from 'merchant/reducers/home';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { updateModalConfigDetails } from 'merchant/reducers/ModalConfigApi';
import { isMobileDevice } from 'merchant/components/Home/data';
import MwebCoupon from 'assets/onboarding/mobweb_coupon.svg';

const OnboardingCoupons = ({
  closeModal,
  showProductModal,
  tracking,
  mtuCouponCount,
  autoOpenOnboardingCoupon,
  isButtonClicked,
}) => {
  useEffect(() => {
    analyticsTrack({
      objectName: 'Limited time MTU offer popup',
      actionName: 'loaded',
      screen: 'home page',
      properties: {
        location: 'top header',
        popupCount: mtuCouponCount,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toCleverTap: true,
    });
    tracking.trackEvent(
      window.rzpQ.merchantActions().success('merchant_dashboard.view_onboarding_coupons', {
        ID: 'AUG21-1LFREECREDITS-PL',
      }),
    );

    if (!autoOpenOnboardingCoupon && mtuCouponCount < 1) {
      updateModalConfigDetails({ mtu_coupon_popup_count: 1 }, 'onboarding');
    }
    if (typeof mtuCouponCount === 'number' && autoOpenOnboardingCoupon && !isButtonClicked) {
      const payload = { mtu_coupon_popup_count: mtuCouponCount + 1 }; //increase the current count by 1
      updateModalConfigDetails(payload, 'onboarding');
    }
  }, []);

  return (
    <div className="Onboarding-coupon">
      <button type="button" className="close btn" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div className="content">
        <div className="offer-period">Limited time offer</div>
        {isMobileDevice() && <img src={MwebCoupon} alt="coupon" className="mweb-coupon-img" />}
        <div className="credit-text">CONGRATULATIONS ON RECEIVING LIMITED PERIOD OFFER!!</div>
        <div className="bottom-text">
          Your transaction fee is waived off! Accept the first payment in the next 5 days to avail
          ZERO transaction fees on payments up to <span className="amount">Rs 10,000.</span>
        </div>
        <div className="content__btn">
          <Button.Primary
            type="button"
            className="accept-payment"
            onClick={() => {
              closeModal();
              showProductModal();
              analyticsTrack({
                objectName: 'Accept Payments Limited time offer',
                actionName: 'clicked',
                screen: 'home page',
                properties: {
                  location: 'top header',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              tracking.trackEvent(
                window.rzpQ
                  .merchantActions()
                  .success('merchant_dashboard.click_onboarding_coupons_cta1', {
                    ID: 'AUG21-1LFREECREDITS-PL',
                  }),
              );
            }}
          >
            Accept payments
          </Button.Primary>
        </div>
      </div>
    </div>
  );
};

export default compose(
  rTracking(() => window.rzpQ.component('OnboardingCoupons')),
  connect(null, {
    showProductModal: showProductsModal,
  }),
)(OnboardingCoupons);
