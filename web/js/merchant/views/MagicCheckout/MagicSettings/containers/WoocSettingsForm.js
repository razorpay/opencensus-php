import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { bindActionCreators } from 'redux';
import { isUrlLenient } from 'common/utils/validators';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import EditSettings from 'merchant/views/MagicCheckout/MagicSettings/components/EditSettings';
import { FETCH_STATUS, PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import FeeConfiguration from 'merchant/views/MagicCheckout/common/components/FeeConfiguration';
import { FEE_RULES, DEFAULT_RULE } from 'merchant/views/MagicCheckout/constants';
import { isFeeRuleValid } from 'merchant/views/MagicCheckout/common/feeUtils';
import Popover, { PopoverBody } from 'common/ui/Popover';

const isUrlValid = (value) => {
  if (!isUrlLenient(value)) {
    return 'Please enter a valid URL.';
  }
  return '';
};

const getLabel = (label) => <label>{label}</label>;

const WoocSettingsForm = ({ settings, updateSettings }) => {
  const [formValid, setFormValid] = useState(false);
  const [domain, setDomain] = useState('');
  const [shippingUrl, setShippingUrl] = useState('');
  const [listPromotionsUrl, setListPromotionsUrl] = useState('');
  const [applyPromotionUrl, setApplyPromotionUrl] = useState('');
  const [feeRule, setFeeRule] = useState(DEFAULT_RULE);

  const updateRule = useCallback((_, value) => {
    const rule = {
      ...feeRule,
      ...value,
    };
    setFeeRule(rule);
  }, []);

  const onChange = useCallback(
    (setStore) => (e) => {
      setStore(e.target.value);
    },
    [],
  );

  useEffect(() => {
    if (isUrlLenient(domain) && isFeeRuleValid(feeRule)) {
      setFormValid(true);
      setListPromotionsUrl(`${domain}/wp-json/1cc/v1/coupon/list`);
      setApplyPromotionUrl(`${domain}/wp-json/1cc/v1/coupon/apply`);
      setShippingUrl(`${domain}/wp-json/1cc/v1/shipping/shipping-info`);
    }
  }, [domain]);

  useEffect(() => {
    if (
      settings.platform === PLATFORMS.VALUES.WOOCOMMERCE &&
      settings.status === FETCH_STATUS.IDLE
    ) {
      if (settings.list_promotions) {
        const url = new URL(settings.list_promotions);
        setDomain(url.origin);
      }
      if (settings.cod_slabs) {
        setFeeRule(settings.cod_slabs);
      }
    }
  }, [settings.platform, settings.status]);

  const onSave = useCallback(() => {
    updateSettings({
      platform: PLATFORMS.VALUES.WOOCOMMERCE,
      shipping_info: shippingUrl,
      list_promotions: listPromotionsUrl,
      apply_promotion: applyPromotionUrl,
      cod_slabs: feeRule,
    });
  }, [updateSettings, shippingUrl, listPromotionsUrl, applyPromotionUrl, feeRule]);

  return (
    <EditSettings
      settings={settings}
      platform={PLATFORMS.VALUES.WOOCOMMERCE}
      onSave={onSave}
      valid={formValid}
    >
      <Input
        required={true}
        validator={isUrlValid}
        value={domain}
        onChange={onChange(setDomain)}
        label={() => (
          <label>
            Domain hyperlink{' '}
            <i className="i i-info-outline">
              <Popover persistent={false} theme="dark">
                <PopoverBody>
                  <p>The URL of your WooCommerce store, e.g. https://myecomstore.com</p>
                </PopoverBody>
              </Popover>
            </i>
          </label>
        )}
        name="domain"
        placeholder="Enter domain hyperlink"
        id="domain"
        className="Magic-Settings--Input"
      />
      <Input
        disabled={true}
        required={true}
        value={listPromotionsUrl}
        label={getLabel('API URL for display promotions')}
        name="display_prmotions_url"
        placeholder="Enter API URL for displaying promotion"
        id="display_prmotions_url"
        className="Magic-Settings--Input"
      />
      <Input
        disabled={true}
        required={true}
        value={applyPromotionUrl}
        label={getLabel('URL for apply promotions')}
        name="apply_promotion_url"
        placeholder=" Enter API URL for apply promotion"
        id="apply_promotion_url"
        className="Magic-Settings--Input"
      />
      <Input
        disabled={true}
        required={true}
        value={shippingUrl}
        label={getLabel('URL for shipping info')}
        name="shipping_info_url"
        placeholder="Enter API URL for shipping info"
        id="shipping_info_url"
        className="Magic-Settings--Input"
      />
      <FeeConfiguration
        required={false}
        type={FEE_RULES.COD_FEE_RULE}
        updateUserFeeRule={updateRule}
        feeRule={feeRule}
      />
    </EditSettings>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(WoocSettingsForm);
