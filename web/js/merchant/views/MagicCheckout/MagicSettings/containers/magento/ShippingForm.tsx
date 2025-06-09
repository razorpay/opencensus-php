import React, { useCallback, useEffect, useState } from 'react';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';
import { connect } from 'react-redux';
import isEmpty from 'lodash/isEmpty';

import { Heading } from '@razorpay/blade/components';
import { AsyncBtn } from 'common/new-ui/Button';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';

import {
  FETCH_STATUS,
  PLATFORMS,
  SHIPPING_SETTINGS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';

const ShippingForm = ({ settings, updateSettings }) => {
  const [shippingSettings, setShippingSettings] = useState<typeof SHIPPING_SETTINGS>([]);

  useEffect(() => {
    if (settings.platform === PLATFORMS.VALUES.MAGENTO && settings.status === FETCH_STATUS.IDLE) {
      setShippingSettings((prevSettings) => {
        const updatedSettings = (isEmpty(prevSettings) ? SHIPPING_SETTINGS : [...prevSettings]).map(
          (setting) => ({
            ...setting,
            value: settings[setting.key],
          }),
        );

        return updatedSettings;
      });
    }
  }, [
    settings.platform,
    settings.status,
    settings.shipping_info,
    settings.one_cc_international_shipping,
    settings.one_cc_capture_billing_address,
  ]);

  const handleSave = useCallback(() => {
    const payload = {
      platform: PLATFORMS.VALUES.MAGENTO,
    };
    shippingSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });
    updateSettings(payload, false);
  }, [updateSettings, shippingSettings]);

  const handleToggle = useCallback((checked, label) => {
    setShippingSettings((prevSetting) => {
      const tempShippingSettings = [...prevSetting];
      tempShippingSettings.forEach((setting) => {
        if (setting.label === label) {
          setting.value = checked;
        }
      });
      return tempShippingSettings;
    });
  }, []);

  return (
    <div className="magento-shipping-container">
      <Heading size="medium" marginX="spacing.3">
        Shipping Settings
      </Heading>
      <div className="padding-16">
        {shippingSettings.map((setting) => (
          <SettingsToggle key={setting.label} setting={setting} onToggle={handleToggle} />
        ))}
      </div>
      <AsyncBtn.Primary
        type="button"
        isPending={settings.nestedTabsStatus === FETCH_STATUS.LOADING}
        showLoader={settings.nestedTabsStatus === FETCH_STATUS.LOADING}
        onClick={handleSave}
        className="settings-cta"
      >
        Save Settings
      </AsyncBtn.Primary>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(ShippingForm);
