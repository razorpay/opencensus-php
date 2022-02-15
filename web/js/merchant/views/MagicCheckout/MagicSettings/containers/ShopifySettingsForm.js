import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { bindActionCreators } from 'redux';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import EditSettings from 'merchant/views/MagicCheckout/MagicSettings/components/EditSettings';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const SHOPIFY_ID_REGEX = new RegExp(/([A-Za-z0-9]+)(.myshopify.com)/);

const shopIdLabel = <label>Store ID</label>;

const isValidShopifyId = (input = '') => {
  if (!SHOPIFY_ID_REGEX.test(input)) {
    if (!SHOPIFY_ID_REGEX.test(`${input}.myshopify.com`)) {
      return { valid: false, input };
    } else {
      return { valid: true, input };
    }
  }
  return { valid: true, input: input.split('.')?.[0] };
};

const validateShopId = (input) => {
  const result = isValidShopifyId(input);
  if (!result.valid) {
    return 'Please enter a valid store ID';
  }
  return null;
};

const ShopifySettingsForm = ({ settings, updateSettings }) => {
  const [formValid, setFormValid] = useState(false);
  const [shopId, setShopId] = useState('');

  const onShopIdInput = useCallback(
    (e) => {
      const result = isValidShopifyId(e?.target?.value);
      setShopId(result.input);
      setFormValid(result.valid);
    },
    [setShopId, setFormValid],
  );

  const onSave = useCallback(() => {
    updateSettings({
      platform: PLATFORMS.VALUES.SHOPIFY,
      shop_id: `${shopId}.myshopify.com`,
    });
  }, [updateSettings, shopId]);

  useEffect(() => {
    if (settings.platform === PLATFORMS.VALUES.SHOPIFY && settings.shop_id) {
      setShopId(settings.shop_id.split('.')[0]);
    }
  }, [settings, setShopId]);

  return (
    <EditSettings
      settings={settings}
      platform={PLATFORMS.VALUES.SHOPIFY}
      onSave={onSave}
      valid={formValid}
    >
      <Input
        required={true}
        validator={validateShopId}
        value={shopId}
        onChange={onShopIdInput}
        label={shopIdLabel}
        name="shop_id"
        placeholder="Enter Store ID"
        id="shop_id"
        className="Input--Shopify"
        addonAfter=".myshopify.com"
      />
    </EditSettings>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(ShopifySettingsForm);
