import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import isEmpty from '@universe/utils/isEmpty';
import { bindActionCreators } from 'redux';
import { isUrlLenient } from 'common/utils/validators';
import { AsyncBtn } from 'common/new-ui/Button';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import {
  PLATFORMS,
  SHIPPING_SETTINGS,
  FETCH_STATUS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { SettingsInputLabel } from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsInputLabel';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';

const isUrlValid = (value) => {
  if (!isUrlLenient(value)) {
    return 'Please enter a valid URL.';
  }
  return null;
};

const getInputLabel = ({ label, description }) => {
  return <SettingsInputLabel label={label}>{description}</SettingsInputLabel>;
};

const SettingsForm = ({ settings, updateSettings }) => {
  const [shippingUrl, setShippingUrl] = useState('');
  const [formValid, setFormValid] = useState(false);
  const [shippingSettings, setShippingSettings] = useState([]);
  const { platform, shipping_info, nestedTabsStatus } = settings;

  const onChange = useCallback(
    (setStore) => (e) => {
      setStore(e.target.value);
    },
    [],
  );

  useEffect(() => {
    if (isUrlLenient(shippingUrl)) {
      setFormValid(true);
    } else {
      setFormValid(false);
    }
  }, [shippingUrl]);

  useEffect(() => {
    if (platform === PLATFORMS.VALUES.NATIVE) {
      setShippingUrl(shipping_info);
      setShippingSettings((prevSettings) => {
        const tempShippingSettings = isEmpty(prevSettings) ? SHIPPING_SETTINGS : [...prevSettings];
        tempShippingSettings.forEach((settingItem) => {
          settingItem.value = settings[settingItem.key];
        });

        return tempShippingSettings;
      });
    }
  }, [shipping_info]);

  const onSave = useCallback(() => {
    const payload = {
      platform: PLATFORMS.VALUES.NATIVE,
      shipping_info: shippingUrl,
    };
    shippingSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });
    updateSettings(payload, false);
  }, [updateSettings, shippingUrl, shippingSettings]);

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
    <div className="native-coupon-container">
      <Input
        required={true}
        validator={isUrlValid}
        value={shippingUrl}
        onChange={onChange(setShippingUrl)}
        disabled={nestedTabsStatus === FETCH_STATUS.LOADING}
        label={getInputLabel({
          label: 'URL for shipping info',
          description:
            'The API URL to return the shipping serviceability, code serviceability, shipping fee and cod fee for a given list of addresses',
        })}
        name="shipping_info_url"
        placeholder="Enter API URL for shipping info"
        id="shipping_info_url"
        className="Magic-Settings--Input"
      />
      {shippingSettings.map((setting) => (
        <SettingsToggle key={setting.label} setting={setting} onToggle={onToggle} />
      ))}
      <div className="next-cta">
        <AsyncBtn.Primary
          type="button"
          isPending={nestedTabsStatus === FETCH_STATUS.LOADING}
          onClick={onSave}
          disabled={!formValid}
          showLoader={nestedTabsStatus === FETCH_STATUS.LOADING}
        >
          Save Settings
        </AsyncBtn.Primary>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(SettingsForm);
