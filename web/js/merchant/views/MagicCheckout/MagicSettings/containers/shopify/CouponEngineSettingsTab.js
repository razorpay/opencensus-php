import React, { useState, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import rzpLogo from 'assets/logo-dark.png';
import { AsyncBtn } from 'common/new-ui/Button';
import SwitchField from 'common/ui/Forms/SwitchField';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import {
  EnableCouponsOnCheckoutWrapper,
  LogoWrapper,
  LogoImage,
  Title,
  ToggleWrapper,
} from 'merchant/views/MagicCheckout/CouponEngine/styles/EnableSettings';
import FormWrapper from 'merchant/views/MagicCheckout/common/components/FormWrapper';
import { showNotification } from 'merchant_common/reducers/notifications';

const EnableCouponsToggle = ({ isChecked, onToggle }) => (
  <SwitchField onChange={onToggle} checked={isChecked} type="prime" />
);

const EnableCouponsOnCheckout = ({ settings, updateSettings, showNotification }) => {
  const [isCouponsEnabled, setIsCouponsEnabled] = useState(settings.one_cc_coupon_engine || false);
  const [isLoading, setIsLoading] = useState(false);

  const handleToggleClick = useCallback((toggleState) => {
    setIsCouponsEnabled(toggleState);
  }, []);

  const handleSave = () => {
    setIsLoading(true);
    const params = {
      platform: settings.platform,
      shop_id: settings.shop_id,
      one_cc_coupon_engine: isCouponsEnabled,
    };

    updateSettings(params, false)
      .then(() => {
        setIsLoading(false);
        showNotification({
          type: 'success',
          message: 'Settings saved successfully.',
        });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Something went wrong. Please try again.',
        });
        setIsLoading(false);
      });
  };

  return (
    <div className="checkout-settings">
      <FormWrapper formTitle="Enable coupons for customers">
        <div className="settings-card" style={{ marginTop: '12px' }}>
          <div className="settings-card-widget padding-20">
            <div className="display-flex settings-card-widget-wrapper flex--column">
              <EnableCouponsOnCheckoutWrapper>
                <LogoWrapper>
                  <LogoImage src={rzpLogo} alt="Razorpay" />
                  <Title>Coupons on Magic</Title>
                </LogoWrapper>
                <ToggleWrapper>
                  <EnableCouponsToggle
                    isChecked={isCouponsEnabled}
                    onToggle={(checked) => handleToggleClick(checked)}
                  />
                  <b
                    className={`toggle-status ${isCouponsEnabled ? 'text-primary' : 'text-faded'}`}
                  >
                    {isCouponsEnabled ? 'Enabled' : 'Disabled'}
                  </b>
                </ToggleWrapper>
              </EnableCouponsOnCheckoutWrapper>
              <div className="display-flex settings-card-widget-content">
                Enable your coupons to make them accessible at Checkout and ready for your customers
                to use
              </div>
            </div>
          </div>
        </div>
      </FormWrapper>
      <AsyncBtn.Primary
        type="button"
        onClick={handleSave}
        className="save-cta"
        disabled={isLoading}
        isPending={isLoading}
      >
        Save settings
      </AsyncBtn.Primary>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(EnableCouponsOnCheckout);
