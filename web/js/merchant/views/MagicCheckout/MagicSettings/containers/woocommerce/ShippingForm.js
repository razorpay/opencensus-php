import { useCallback, useEffect, useState } from 'react';
import { Box, Heading } from '@razorpay/blade/components';
import isEmpty from 'lodash/isEmpty';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import {
  FETCH_STATUS,
  PLATFORMS,
  SHIPPING_SETTINGS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import FeeConfiguration from 'merchant/views/MagicCheckout/common/components/FeeConfiguration';
import { isFeeRuleValid } from 'merchant/views/MagicCheckout/common/feeUtils';
import {
  DEFAULT_RULE,
  FEE_RULES,
  MAGIC_DASHBOARD_REVAMP_EXPERIMENT,
} from 'merchant/views/MagicCheckout/constants';

import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

const ShippingForm = ({ settings, updateSettings }) => {
  const [formValid, setFormValid] = useState(false);
  const [feeRule, setFeeRule] = useState(DEFAULT_RULE);
  const [shippingUrl, setShippingUrl] = useState();
  const [shippingSettings, setShippingSettings] = useState([]);

  const updateRule = useCallback((_, value) => {
    const rule = {
      ...feeRule,
      ...value,
    };
    setFeeRule(rule);
  }, []);

  useEffect(() => {
    if (isFeeRuleValid(feeRule)) {
      setFormValid(true);
    }
  }, [feeRule]);

  useEffect(() => {
    if (
      settings.platform === PLATFORMS.VALUES.WOOCOMMERCE &&
      settings.status === FETCH_STATUS.IDLE
    ) {
      if (settings.cod_slabs) {
        setFeeRule(settings.cod_slabs);
      }
      if (settings.shipping_info) {
        setShippingUrl(settings.shipping_info);
      }
      setShippingSettings((prevSettings) => {
        const tempShippingSettings = isEmpty(prevSettings) ? SHIPPING_SETTINGS : [...prevSettings];
        tempShippingSettings.forEach((settingItem) => {
          settingItem.value = settings[settingItem.key];
        });

        return tempShippingSettings;
      });
    }
  }, [
    settings.platform,
    settings.status,
    settings.shipping_info,
    settings.one_cc_international_shipping,
    settings.one_cc_capture_billing_address,
  ]);

  const onSave = useCallback(() => {
    const payload = {
      platform: PLATFORMS.VALUES.WOOCOMMERCE,
      cod_slabs: feeRule,
    };
    shippingSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });
    updateSettings(payload, false);
  }, [updateSettings, feeRule, shippingSettings]);

  const onToggle = useCallback((checked, label) => {
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
    <>
      <Box
        padding="spacing.6"
        paddingLeft="spacing.0"
        paddingRight="spacing.0"
        backgroundColor="surface.background.gray.intense"
      >
        <Heading size="medium">Shipping Settings</Heading>
      </Box>
      <div className="woocommerce-shipping-container">
        <div className="padding-16">
          <div className="display-flex align-center woo-url-wrapper">
            <label className="wooc-url-label">URL for shipping info</label>
            <Input
              disabled={true}
              required={true}
              value={shippingUrl}
              name="shipping_info_url"
              placeholder="Enter API URL for shipping info"
              id="shipping_info_url"
              className="Magic-Settings--Input"
            />
          </div>
          <FeeConfiguration
            required={false}
            type={FEE_RULES.COD_FEE_RULE}
            updateUserFeeRule={updateRule}
            feeRule={feeRule}
          />
          {!useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT) &&
            shippingSettings.map((setting) => (
              <SettingsToggle key={setting.label} setting={setting} onToggle={onToggle} />
            ))}
        </div>
        <AsyncBtn.Primary
          type="button"
          isPending={settings.nestedTabsStatus === FETCH_STATUS.LOADING}
          showLoader={settings.nestedTabsStatus === FETCH_STATUS.LOADING}
          onClick={onSave}
          disabled={!formValid}
          className="settings-cta"
        >
          Save Settings
        </AsyncBtn.Primary>
      </div>
    </>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(ShippingForm);
