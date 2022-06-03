import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { bindActionCreators } from 'redux';
import { isUrlLenient } from 'common/utils/validators';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { AsyncBtn } from 'common/new-ui/Button';
import { PLATFORMS, FETCH_STATUS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { SettingsInputLabel } from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsInputLabel';

const isUrlValid = (value) => {
  if (!isUrlLenient(value)) {
    return 'Please enter a valid URL.';
  }
  return null;
};

const getInputLabel = ({ label, description }) => {
  return <SettingsInputLabel label={label}>{description}</SettingsInputLabel>;
};

const NativeCouponsForm = ({
  settings: { nestedTabsStatus, list_promotions, apply_promotion, platform },
  updateSettings,
}) => {
  const [formValid, setFormValid] = useState(false);
  const [listPromotionsUrl, setListPromotionsUrl] = useState('');
  const [applyPromotionUrl, setApplyPromotionUrl] = useState('');

  const onChange = useCallback(
    (setStore) => (e) => {
      setStore(e.target.value);
    },
    [],
  );

  useEffect(() => {
    if (isUrlLenient(listPromotionsUrl) && isUrlLenient(applyPromotionUrl)) {
      setFormValid(true);
    } else {
      setFormValid(false);
    }
  }, [listPromotionsUrl, applyPromotionUrl]);

  useEffect(() => {
    if (platform === PLATFORMS.VALUES.NATIVE) {
      setListPromotionsUrl(list_promotions);
      setApplyPromotionUrl(apply_promotion);
    }
  }, [platform, list_promotions, apply_promotion]);

  const onSave = useCallback(() => {
    updateSettings(
      {
        platform: PLATFORMS.VALUES.NATIVE,
        list_promotions: listPromotionsUrl,
        apply_promotion: applyPromotionUrl,
      },
      false,
    );
  }, [updateSettings, listPromotionsUrl, applyPromotionUrl]);

  return (
    <>
      <div className="native-coupon-container padding-16">
        <Input
          required={true}
          validator={isUrlValid}
          onChange={onChange(setListPromotionsUrl)}
          value={listPromotionsUrl}
          disabled={nestedTabsStatus === FETCH_STATUS.LOADING}
          label={getInputLabel({
            label: 'URL for get promotions',
            description:
              'The API URL to return the list of promotions applicable for given order_id and customer',
          })}
          name="displayPrmotionsUrl"
          placeholder="Enter API URL for displaying promotion"
          id="displayPrmotionsUrl"
          className="display-flex"
        />
        <Input
          required={true}
          validator={isUrlValid}
          value={applyPromotionUrl}
          onChange={onChange(setApplyPromotionUrl)}
          disabled={nestedTabsStatus === FETCH_STATUS.LOADING}
          label={getInputLabel({
            label: 'URL for apply promotions',
            description:
              'The API URL to validate the promotion code applied by the user and return the discount amount',
          })}
          name="applyPromotionUrl"
          placeholder=" Enter API URL for apply promotion"
          id="applyPromotionUrl"
          className="display-flex"
        />
      </div>
      <AsyncBtn.Primary
        type="button"
        onClick={onSave}
        disabled={!formValid}
        isPending={nestedTabsStatus === FETCH_STATUS.LOADING}
        showLoader={nestedTabsStatus === FETCH_STATUS.LOADING}
        className="settings-cta"
      >
        Save Settings
      </AsyncBtn.Primary>
    </>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(NativeCouponsForm);
