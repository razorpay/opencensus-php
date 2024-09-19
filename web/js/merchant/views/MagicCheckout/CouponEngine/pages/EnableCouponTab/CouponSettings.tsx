import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { Heading, Spinner, Switch } from '@razorpay/blade/components';

import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { showNotification } from 'merchant_common/reducers/notifications';

import { GenericRecord } from 'merchant/views/MagicCheckout/types';

import rzpLogo from 'assets/logo-dark.png';
import autoApplyCouponIcon from 'assets/magic_checkout/auto-apply-coupons.svg';
import multiCouponIcon from 'assets/magic_checkout/multi-coupons.svg';

import { Section, Card, CardHeader, ToggleWrapper } from './styled';

const COUPON_ENGINE = 'coupon_engine';
const AUTO_APPLY_COUPONS = 'auto_apply_coupons';
const MULTI_COUPONS = 'multi_coupons';

type LoadingProperty =
  | typeof COUPON_ENGINE
  | typeof AUTO_APPLY_COUPONS
  | typeof MULTI_COUPONS
  | null;

interface CouponSettingsProps {
  settings: GenericRecord;
  updateSettings: (params: GenericRecord, showLoader: boolean) => Promise<unknown>;
  showNotification: (payload: GenericRecord) => void;
}

const CouponSettings: React.FC<CouponSettingsProps> = ({
  settings,
  updateSettings,
  showNotification,
}) => {
  const [isCouponsEnabled, setIsCouponsEnabled] = useState<boolean>(
    Boolean(settings.one_cc_coupon_engine),
  );
  const [isAutoApplyEnabled, setIsAutoApplyEnabled] = useState<boolean>(
    Boolean(settings.one_cc_auto_apply_coupons),
  );
  const [isMultiCouponsEnabled, setIsMultiCouponsEnabled] = useState<boolean>(
    Boolean(settings.one_cc_multi_coupons as boolean),
  );
  /*
   * Note that multi_coupons and auto_apply_coupons properties are children/dependents
   * of the coupon_engine property. They can only be changed when the coupon engine is turned
   * ON. If the coupon engine feature is switched to OFF, they go OFF as well.
   * For the same reason, we show a spinner in place of all three switches when coupon_engine
   * is being changed (i.e. a request in-progress).
   */
  const [loadingProperty, setLoadingProperty] = useState<LoadingProperty>(null);
  const { platform, shop_id } = settings;

  // utility callbacks
  const notifySuccess = () => {
    showNotification({
      type: 'success',
      message: 'Settings saved successfully.',
    });
  };
  const notifyError = () => {
    showNotification({
      type: 'error',
      message: 'Something went wrong. Please try again.',
    });
  };
  const onRequestComplete = () => {
    setLoadingProperty(null);
  };

  // switch handlers
  const handleEnableCouponsSwitch = (e) => {
    const couponsEnabled = e.isChecked;

    setLoadingProperty(COUPON_ENGINE);
    const params = {
      platform,
      shop_id,
      one_cc_coupon_engine: couponsEnabled,
      one_cc_auto_apply_coupons: false,
      one_cc_multi_coupons: false,
    };

    updateSettings(params, false)
      .then(() => {
        setIsCouponsEnabled(couponsEnabled);
        setIsAutoApplyEnabled(false);
        setIsMultiCouponsEnabled(false);
        notifySuccess();
      })
      .catch(notifyError)
      .finally(onRequestComplete);
  };

  const handleAutoApplyCouponsSwitch = (e) => {
    const autoCouponsEnabled = e.isChecked;

    setLoadingProperty(AUTO_APPLY_COUPONS);
    const params = {
      platform,
      shop_id,
      one_cc_auto_apply_coupons: autoCouponsEnabled,
    };

    updateSettings(params, false)
      .then(() => {
        setIsAutoApplyEnabled(autoCouponsEnabled);
        notifySuccess();
      })
      .catch(notifyError)
      .finally(onRequestComplete);
  };

  const handleMultiCouponsSwitch = (e) => {
    const multiCouponsEnabled = e.isChecked;

    setLoadingProperty(MULTI_COUPONS);
    const params = {
      platform,
      shop_id,
      one_cc_multi_coupons: multiCouponsEnabled,
    };

    updateSettings(params, false)
      .then(() => {
        setIsMultiCouponsEnabled(multiCouponsEnabled);
        notifySuccess();
      })
      .catch(notifyError)
      .finally(onRequestComplete);
  };

  return (
    <Section>
      <Heading as="h3" size="large" weight="semibold" color="surface.text.gray.normal">
        Coupon Settings
      </Heading>
      <Card>
        <CardHeader>
          <CardHeader.Icon src={rzpLogo} alt="" role="presentation" />
          <CardHeader.Title as="h4">Enable Coupons</CardHeader.Title>
          <ToggleWrapper>
            {loadingProperty === COUPON_ENGINE ? (
              <Spinner accessibilityLabel="loading" />
            ) : (
              <Switch
                isChecked={isCouponsEnabled}
                onChange={handleEnableCouponsSwitch}
                accessibilityLabel="Toggle Coupons"
              />
            )}
            <b className={`toggle-status ${isCouponsEnabled ? 'text-primary' : 'text-faded'}`}>
              {isCouponsEnabled ? 'Enabled' : 'Disabled'}
            </b>
          </ToggleWrapper>
        </CardHeader>
        <p>
          Enable your coupons to make them accessible at Checkout and ready for your customers to
          use
        </p>
      </Card>
      <Card>
        <CardHeader>
          <CardHeader.Icon src={autoApplyCouponIcon} alt="" role="presentation" />
          <CardHeader.Title as="h4">Auto-apply best coupon</CardHeader.Title>
          <ToggleWrapper>
            {loadingProperty === COUPON_ENGINE || loadingProperty === AUTO_APPLY_COUPONS ? (
              <Spinner accessibilityLabel="loading" />
            ) : (
              <Switch
                isChecked={isAutoApplyEnabled}
                onChange={handleAutoApplyCouponsSwitch}
                isDisabled={!isCouponsEnabled}
                accessibilityLabel="Toggle Auto-apply Coupons"
              />
            )}
          </ToggleWrapper>
        </CardHeader>
        <p>The best value coupon out of the included ones will be auto-applied</p>
      </Card>
      <Card>
        <CardHeader>
          <CardHeader.Icon src={multiCouponIcon} alt="" role="presentation" />
          <CardHeader.Title as="h4">Let users combine coupons</CardHeader.Title>
          <ToggleWrapper>
            {loadingProperty === COUPON_ENGINE || loadingProperty === MULTI_COUPONS ? (
              <Spinner accessibilityLabel="loading" />
            ) : (
              <Switch
                isChecked={isMultiCouponsEnabled}
                onChange={handleMultiCouponsSwitch}
                isDisabled={!isCouponsEnabled}
                accessibilityLabel="Toggle Multi Coupons"
              />
            )}
          </ToggleWrapper>
        </CardHeader>
        <p>
          Allows customers to apply multiple coupons at once in checkout. The rules can be
          configured for each coupon.
        </p>
      </Card>
    </Section>
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

export default connect(mapStateToProps, mapDispatchToProps)(CouponSettings);
