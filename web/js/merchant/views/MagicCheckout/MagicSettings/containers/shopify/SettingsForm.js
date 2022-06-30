import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { bindActionCreators } from 'redux';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { PLATFORMS, FETCH_STATUS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { AsyncBtn } from 'common/new-ui/Button';
import { analyticsTrack } from 'common/utils/analytics';

const SHOPIFY_ID_REGEX = new RegExp(/([A-Za-z0-9]+)(.myshopify.com)/);

const shopIdLabel = <label className="font-normal">Store ID</label>;

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

const SettingsForm = ({ settings, updateSettings, merchantId }) => {
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
      list_promotions: ``,
      apply_promotion: ``,
      shipping_info: ``,
    });
    analyticsTrack({
      objectName: '1ccclickednextonplatformsettings',
      actionName: 'behav',
      screen: 'platform settings l0',
      properties: {
        platform: PLATFORMS.VALUES.SHOPIFY,
        store_id: `${shopId}.myshopify.com`,
        merchant_id: merchantId,
      },
    });
  }, [updateSettings, shopId]);

  useEffect(() => {
    if (settings.platform === PLATFORMS.VALUES.SHOPIFY && settings.shop_id) {
      setShopId(settings.shop_id.split('.')[0]);
      setFormValid(true);
    }
  }, [settings, setShopId]);

  return (
    <div>
      <div className="shopify-container platform-input-container bg-settings display-flex align-center">
        {shopIdLabel}
        <Input
          required={true}
          validator={validateShopId}
          value={shopId}
          onChange={onShopIdInput}
          name="shop_id"
          placeholder="Enter Store ID"
          id="shop_id"
          className="Input--Shopify"
          addonAfter=".myshopify.com"
        />
      </div>
      <AsyncBtn.Primary
        type="button"
        isPending={settings.status === FETCH_STATUS.LOADING}
        onClick={onSave}
        disabled={!formValid}
        className="settings-cta"
      >
        Next
      </AsyncBtn.Primary>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  merchantId: state.config?.config?.id,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(SettingsForm);
