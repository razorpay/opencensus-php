import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { bindActionCreators } from 'redux';
import { isUrlLenient } from 'common/utils/validators';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import EditSettings from 'merchant/views/MagicCheckout/MagicSettings/components/EditSettings';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { SettingsInputLabel } from 'merchant/views/MagicCheckout/MagicSettings/components/SettingsInputLabel';
import CodIntelligenceToggle from 'merchant/views/MagicCheckout/MagicSettings/components/CodIntelligenceToggle';

const isUrlValid = (value) => {
  if (!isUrlLenient(value)) {
    return 'Please enter a valid URL.';
  }
  return null;
};

const getInputLabel = ({ label, description }) => {
  return <SettingsInputLabel label={label}>{description}</SettingsInputLabel>;
};

const NativeSettingsForm = ({ settings, updateSettings }) => {
  const [formValid, setFormValid] = useState(false);
  const [shippingUrl, setShippingUrl] = useState('');
  const [listPromotionsUrl, setListPromotionsUrl] = useState('');
  const [applyPromotionUrl, setApplyPromotionUrl] = useState('');
  const [codIntelligence, setCodIntelligence] = useState(false);

  const switchMode = () => {
    setCodIntelligence((prevState) => !prevState);
  };

  const onChange = useCallback(
    (setStore) => (e) => {
      setStore(e.target.value);
    },
    [],
  );

  useEffect(() => {
    if (
      isUrlLenient(shippingUrl) &&
      isUrlLenient(listPromotionsUrl) &&
      isUrlLenient(applyPromotionUrl)
    ) {
      setFormValid(true);
    } else {
      setFormValid(false);
    }
  }, [shippingUrl, listPromotionsUrl, applyPromotionUrl]);

  useEffect(() => {
    if (settings.platform === PLATFORMS.VALUES.NATIVE) {
      setShippingUrl(settings.shipping_info);
      setListPromotionsUrl(settings.list_promotions);
      setApplyPromotionUrl(settings.apply_promotion);
      setCodIntelligence(settings.cod_intelligence);
    }
  }, [settings]);

  const onSave = useCallback(() => {
    updateSettings({
      platform: PLATFORMS.VALUES.NATIVE,
      shipping_info: shippingUrl,
      list_promotions: listPromotionsUrl,
      apply_promotion: applyPromotionUrl,
      cod_intelligence: codIntelligence,
    });
  }, [updateSettings, shippingUrl, listPromotionsUrl, applyPromotionUrl, codIntelligence]);

  return (
    <EditSettings
      platform={PLATFORMS.VALUES.NATIVE}
      settings={settings}
      onSave={onSave}
      valid={formValid}
    >
      <Input
        required={true}
        validator={isUrlValid}
        onChange={onChange(setListPromotionsUrl)}
        value={listPromotionsUrl}
        label={getInputLabel({
          label: 'API URL for display promotions',
          description:
            'The API URL to return the list of promotions applicable for given order_id and customer',
        })}
        name="display_prmotions_url"
        placeholder="Enter API URL for displaying promotion"
        id="display_prmotions_url"
        className="Magic-Settings--Input"
      />
      <Input
        required={true}
        validator={isUrlValid}
        value={applyPromotionUrl}
        onChange={onChange(setApplyPromotionUrl)}
        label={getInputLabel({
          label: 'API URL for apply promotions',
          description:
            'The API URL to validate the promotion code applied by the user and return the discount amount',
        })}
        name="apply_promotion_url"
        placeholder=" Enter API URL for apply promotion"
        id="apply_promotion_url"
        className="Magic-Settings--Input"
      />
      <Input
        required={true}
        validator={isUrlValid}
        value={shippingUrl}
        onChange={onChange(setShippingUrl)}
        label={getInputLabel({
          label: 'API URL for shipping info',
          description:
            'The API URL to return the shipping serviceability, code serviceability, shipping fee and cod fee for a given list of addresses',
        })}
        name="shipping_info_url"
        placeholder="Enter API URL for shipping info"
        id="shipping_info_url"
        className="Magic-Settings--Input"
      />
      <CodIntelligenceToggle
        platform={PLATFORMS.VALUES.NATIVE}
        checked={codIntelligence}
        switchMode={switchMode}
      />
    </EditSettings>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(NativeSettingsForm);
