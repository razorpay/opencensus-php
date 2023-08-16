import isEmpty from 'lodash/isEmpty';
import FormWrapper from 'merchant/views/MagicCheckout/common/components/FormWrapper';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import {
  GIFT_CARD_FEATURE,
  GIFT_CARD_SETTINGS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { getInitialSettings } from 'merchant/views/MagicCheckout/MagicSettings/containers/helpers';
import { useCallback, useEffect } from 'react';

const GCForm = ({ settings, giftCard, giftCardSettings, setGiftCard, setGiftCardSettings }) => {
  const {
    one_cc_gift_card,
    one_cc_multiple_gift_card,
    one_cc_gift_card_restrict_coupon,
    one_cc_buy_gift_card,
    one_cc_gift_card_cod_restrict,
  } = settings;

  useEffect(() => {
    setGiftCard((prevSettings) => {
      const tempGC = isEmpty(prevSettings) ? GIFT_CARD_FEATURE : { ...prevSettings };
      tempGC.value = one_cc_gift_card;

      return tempGC;
    });
  }, [one_cc_gift_card]);

  useEffect(() => {
    setGiftCardSettings((prevSettings) => {
      const tempGCSettings = getInitialSettings(GIFT_CARD_SETTINGS, prevSettings);
      tempGCSettings.forEach((settingItem) => {
        settingItem.value = settings[settingItem.key];
      });

      return tempGCSettings;
    });
  }, [
    one_cc_multiple_gift_card,
    one_cc_gift_card_restrict_coupon,
    one_cc_buy_gift_card,
    one_cc_gift_card_cod_restrict,
  ]);

  const onToggleGC = useCallback((checked) => {
    setGiftCard((prevSetting) => ({ ...prevSetting, value: checked }));
  }, []);

  const onToggleGCSettings = useCallback((checked, label) => {
    setGiftCardSettings((prevSettings) => {
      const tempGCSettings = [...prevSettings];
      tempGCSettings.forEach((setting) => {
        if (setting.label === label) {
          setting.value = checked;
        }
      });

      return tempGCSettings;
    });
  }, []);

  return (
    <FormWrapper formTitle="Gift Card Settings" extraClass="padding-16">
      <SettingsToggle key={giftCard.label} setting={giftCard} onToggle={onToggleGC} />
      {giftCard?.value &&
        giftCardSettings.map((setting) => (
          <SettingsToggle key={setting.label} setting={setting} onToggle={onToggleGCSettings} />
        ))}
    </FormWrapper>
  );
};

export default GCForm;
