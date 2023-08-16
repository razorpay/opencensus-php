import { analyticsTrack } from 'common/utils/analytics';
import isEmpty from 'lodash/isEmpty';
import {
  disableMagicCheckout,
  updateMagicSettings,
} from 'merchant/reducers/magicCheckout/magicSettings/actions';
import DisableMagicModal from 'merchant/views/MagicCheckout/MagicSettings/components/common/DisableMagicModal';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import {
  PLATFORMS,
  SHOPIFY_MAGIC_CHECKOUT,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { showNotification } from 'merchant_common/reducers/notifications';
import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

const REASON_TYPE_OTHERS = 'Others';
const DESC_MAX_CHARACTER = 1023;
const MODAL_WITH_FORM = 'modal_with_form';
const MagicCheckoutToggle = ({
  settings,
  merchantId,
  disableMagicCheckout,
  showNotification,
  updateSettings,
}) => {
  const [magicSetting, setMagicSetting] = useState({});
  const [showModal, setShowModal] = useState(false);
  const [reason, setReason] = useState('');
  const [description, setDescription] = useState('');
  const [isFormInValid, setIsFormInValid] = useState(false);
  const showModalWithForm = showModal === MODAL_WITH_FORM;

  const platform =
    settings.platform === 'shopify' ? PLATFORMS.VALUES.SHOPIFY : PLATFORMS.VALUES.WOOCOMMERCE;
  const domain = settings.platform === 'shopify' ? settings?.shop_id : settings?.domain_url;

  const analyticsProperties = {
    platform,
    store_id: domain,
    merchant_id: merchantId,
  };

  useEffect(() => {
    setMagicSetting((prevSettings) => {
      const tempMagicSettings = isEmpty(prevSettings)
        ? SHOPIFY_MAGIC_CHECKOUT
        : { ...prevSettings };
      tempMagicSettings.value = settings?.one_click_checkout;

      return tempMagicSettings;
    });
  }, [settings]);

  const onToggle = useCallback(
    (checked, label, postActionCB) => {
      analyticsTrack({
        objectName: checked
          ? '1ccclickedenablemagiccheckout'
          : '1ccdisablemagiccheckoutconfirmationshown',
        actionName: checked ? 'behav' : 'render',
        screen: 'platform settings l1',
        properties: analyticsProperties,
      });

      if (checked) {
        setMagicSetting((prevSetting) => ({ ...prevSetting, value: checked }));
        updateSettings({
          platform,
          shop_id: domain,
          one_click_checkout: true,
        });
      } else {
        setShowModal(true);
        postActionCB();
      }
    },
    [analyticsProperties, settings, updateSettings],
  );

  const onCloseModal = () => {
    analyticsTrack({
      objectName: showModalWithForm
        ? '1ccclickednoondisablemagiccheckoutfeedback'
        : '1ccclickednoondisablemagiccheckout',
      actionName: 'behav',
      screen: 'platform settings l1',
      properties: analyticsProperties,
    });
    setShowModal(false);
    setIsFormInValid(false);
  };

  const checkFormInValid = (selectedReason = reason, desc = description) => {
    if (selectedReason === REASON_TYPE_OTHERS && !desc) {
      setIsFormInValid(true);
    } else if (selectedReason && desc.length < DESC_MAX_CHARACTER) {
      setIsFormInValid(false);
    }
  };

  const handleReason = (event) => {
    const selectedReason = event.target.value;
    setReason(selectedReason);
    checkFormInValid(selectedReason);
  };

  const handleDescription = (event) => {
    const desc = event.target.value;
    setDescription(desc);
    checkFormInValid(reason, desc);
  };

  const handleSubmit = () => {
    if (showModalWithForm) {
      analyticsTrack({
        objectName: '1ccclickedyesondisablemagiccheckoutfeedback',
        actionName: 'behav',
        screen: 'platform settings l1',
        properties: {
          reason_selected: reason,
          reason_description: description,
          ...analyticsProperties,
        },
      });
      disableMagicCheckout({
        platform,
        shop_id: domain,
        one_click_checkout: false,
        reason,
        additional_reason: description,
      })
        .then(() => {
          showNotification({
            type: 'success',
            message: 'Magic Checkout is disabled',
          });
          analyticsTrack({
            objectName: '1ccmagiccheckoutdisabled',
            actionName: 'behav',
            screen: 'platform settings l1',
            properties: analyticsProperties,
          });
        })
        .catch(({ errors }) => {
          if (Array.isArray(errors)) {
            analyticsTrack({
              objectName: '1ccclickedyesondisablemagiccheckout',
              actionName: 'behav',
              screen: 'platform settings l1',
              properties: analyticsProperties,
            });
            showNotification({
              type: 'error',
              message: errors[0],
            });
          }
        });
      setShowModal(false);
    } else {
      analyticsTrack({
        objectName: '1ccclickedyesondisablemagiccheckout',
        actionName: 'behav',
        screen: 'platform settings l1',
        properties: analyticsProperties,
      });
      setShowModal(MODAL_WITH_FORM);
      analyticsTrack({
        objectName: '1ccdisablemagiccheckoutfeedbackshown',
        actionName: 'render',
        screen: 'platform settings l1',
        properties: analyticsProperties,
      });
      setIsFormInValid(true);
    }
  };

  const handleValidDesc = (value) => {
    if (value.length > 1000) {
      return `Please enter less than ${DESC_MAX_CHARACTER} characters`;
    }
    return null;
  };

  return (
    <div className="display-flex magic-settings-toggle">
      <SettingsToggle setting={magicSetting} onToggle={onToggle} />
      <DisableMagicModal
        showModal={showModal}
        onClose={onCloseModal}
        showForm={showModalWithForm}
        handleReason={handleReason}
        handleDescription={handleDescription}
        handleSubmit={handleSubmit}
        handleValidDesc={handleValidDesc}
        isFormInValid={isFormInValid}
        isDescRequired={reason === REASON_TYPE_OTHERS}
        showError={reason !== REASON_TYPE_OTHERS && !description}
      />
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
      showNotification,
      disableMagicCheckout,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(MagicCheckoutToggle);
