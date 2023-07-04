import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import ConfirmationModal, {
  DisplayNotificationTxt,
} from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal } from 'merchant_common/reducers/modals';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { setEditMode } from 'merchant/reducers/magicCheckout/codEngine/action';

import { isBasicCODEngine } from 'merchant/views/MagicCheckout/CODSettings/utils';

import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import {
  COD_ENGINE_TYPES,
  SAVE_MODAL_TEXTS,
} from 'merchant/views/MagicCheckout/CODSettings/constants';

const ConfirmSettings = ({
  settings,
  codEngineConfig,
  updateSettings,
  showNotification,
  setEditMode,
  closeModal,
}) => {
  const { platform, shop_id } = settings;
  const { configs, item_categories } = codEngineConfig;
  const saveSettings = () => {
    const params = {
      platform,
      cod_engine: configs.cod_engine,
    };

    if (platform === PLATFORMS.VALUES.SHOPIFY) {
      params.shop_id = shop_id;
    }
    if (isBasicCODEngine(configs.engine)) {
      if (configs.rate_slabs) {
        params.cod_engine_type = COD_ENGINE_TYPES.SLAB_RATE;
      } else {
        params.cod_engine_type = COD_ENGINE_TYPES.SLAB_ELIGIBILITY;
      }
    } else if (item_categories.length) {
      params.cod_engine_type = COD_ENGINE_TYPES.PRODUCT;
    } else {
      params.cod_engine_type = COD_ENGINE_TYPES.LOCATION;
    }
    updateSettings(params, false)
      .then(() => {
        showNotification({
          type: 'success',
          message: () => (
            <DisplayNotificationTxt notificationTxt="COD Settings saved successfully" />
          ),
        });
        setEditMode(false);
        closeModal();
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: () => (
            <DisplayNotificationTxt
              notificationTxt={`${err?.errors[0] || 'Something went wrong'}`}
            />
          ),
        });
      });
  };
  const { header, desc, primaryCtaLabel, secondaryCtaLabel } = SAVE_MODAL_TEXTS;
  return (
    <ConfirmationModal
      header={header}
      desc={desc}
      affirmativeLabel={primaryCtaLabel}
      abortLabel={secondaryCtaLabel}
      onAffirm={saveSettings}
    />
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  codEngineConfig: state.magicCODEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
      setEditMode,
      showNotification,
      closeModal,
    },
    dispatch,
  );
export default connect(mapStateToProps, mapDispatchToProps)(ConfirmSettings);
