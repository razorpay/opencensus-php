import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { Spinner, Switch } from '@razorpay/blade/components';

import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { showNotification } from 'merchant_common/reducers/notifications';

import { GenericRecord } from 'merchant/views/MagicCheckout/types';

import {
  EnableCouponsOnCheckoutWrapper,
  LogoWrapper,
  LogoImage,
  ToggleWrapper,
} from 'merchant/views/MagicCheckout/CouponEngine/styles/EnableSettings';
import { Title } from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/EnableCouponTabStyles';

import rzpLogo from 'assets/logo-dark.png';

interface EnableCouponsOnCheckoutProps {
  settings: GenericRecord;
  updateSettings: (params: GenericRecord, showLoader: boolean) => Promise<unknown>;
  showNotification: (payload: GenericRecord) => void;
}

const EnableCouponsOnCheckout: React.FC<EnableCouponsOnCheckoutProps> = ({
  settings,
  updateSettings,
  showNotification,
}) => {
  const [isCouponsEnabled, setIsCouponsEnabled] = useState<boolean>(
    (settings.one_cc_coupon_engine as boolean) || false,
  );
  const [isLoading, setIsLoading] = useState<boolean>(false);

  const handleSave = () => {
    setIsLoading(true);
    const params = {
      platform: settings.platform,
      shop_id: settings.shop_id,
      one_cc_coupon_engine: !isCouponsEnabled,
    };

    updateSettings(params, false)
      .then(() => {
        setIsCouponsEnabled((isCouponsEnabled) => !isCouponsEnabled);
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
      })
      .finally(() => setIsLoading(false));
  };

  return (
    <>
      <Title>Enable Coupons On Magic checkout</Title>
      <div className="settings-card" style={{ marginTop: '12px' }}>
        <div className="settings-card-widget padding-20">
          <div className="display-flex settings-card-widget-wrapper flex--column">
            <EnableCouponsOnCheckoutWrapper>
              <LogoWrapper>
                <LogoImage src={rzpLogo} alt="Razorpay" />
                <Title>Coupons on Magic</Title>
              </LogoWrapper>
              <ToggleWrapper>
                {isLoading ? (
                  <Spinner accessibilityLabel="loading" />
                ) : (
                  <Switch
                    isChecked={isCouponsEnabled}
                    onChange={handleSave}
                    accessibilityLabel="toggleCouponsOnCheckout"
                  />
                )}
                <b className={`toggle-status ${isCouponsEnabled ? 'text-primary' : 'text-faded'}`}>
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
    </>
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
