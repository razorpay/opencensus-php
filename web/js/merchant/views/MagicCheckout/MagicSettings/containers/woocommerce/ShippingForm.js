import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { bindActionCreators } from 'redux';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { FETCH_STATUS, PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import FeeConfiguration from 'merchant/views/MagicCheckout/common/components/FeeConfiguration';
import { FEE_RULES, DEFAULT_RULE } from 'merchant/views/MagicCheckout/constants';
import { isFeeRuleValid } from 'merchant/views/MagicCheckout/common/feeUtils';
import { AsyncBtn } from 'common/new-ui/Button';

const WoocSettingsForm = ({ settings, updateSettings }) => {
  const [formValid, setFormValid] = useState(false);
  const [feeRule, setFeeRule] = useState(DEFAULT_RULE);
  const [shippingUrl, setShippingUrl] = useState();

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
    }
  }, [settings.platform, settings.status, settings.shipping_info]);

  const onSave = useCallback(() => {
    updateSettings(
      {
        platform: PLATFORMS.VALUES.WOOCOMMERCE,
        cod_slabs: feeRule,
      },
      false,
    );
  }, [updateSettings, shippingUrl, feeRule]);

  return (
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
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(WoocSettingsForm);
