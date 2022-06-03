import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { bindActionCreators } from 'redux';
import { isUrlLenient } from 'common/utils/validators';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { FETCH_STATUS, PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { AsyncBtn } from 'common/new-ui/Button';

const isUrlValid = (value) => {
  if (!isUrlLenient(value)) {
    return 'Please enter a valid URL.';
  }
  return '';
};

const WoocSettingsForm = ({ settings, updateSettings }) => {
  const [formValid, setFormValid] = useState(false);
  const [domain, setDomain] = useState('');

  const onChange = useCallback(
    (setStore) => (e) => {
      setStore(e.target.value);
    },
    [],
  );

  useEffect(() => {
    if (isUrlLenient(domain)) {
      setFormValid(true);
    } else {
      setFormValid(false);
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
    }
  }, [settings.platform, settings.status]);

  const onSave = useCallback(() => {
    updateSettings({
      platform: PLATFORMS.VALUES.WOOCOMMERCE,
      list_promotions: `${domain}/wp-json/1cc/v1/coupon/list`,
      apply_promotion: `${domain}/wp-json/1cc/v1/coupon/apply`,
      shipping_info: `${domain}/wp-json/1cc/v1/shipping/shipping-info`,
    });
  }, [updateSettings, domain]);

  return (
    <div>
      <div className="bg-settings platform-input-container woocommerce-container">
        <Input
          required={true}
          validator={isUrlValid}
          value={domain}
          onChange={onChange(setDomain)}
          label={() => (
            <label>
              Domain hyperlink{' '}
              <i className="i i-info-outline font-normal margin-l--4">
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
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(WoocSettingsForm);
