import React, { useCallback, useEffect, useState } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import DataTable from 'common/ui/Table/DataTable';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';

import SettingsLabel from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SettingsLabel';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import {
  categoryName,
  productCount,
  actions,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/cellItem';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  validateConfig,
  updateEngineConfig,
  deleteCategory,
  createCategory,
} from 'merchant/reducers/magicCheckout/codEngine/action';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';

import {
  POPOVER_CONTENT,
  MODAL_MODES,
  COD_ENGINE_TYPES,
  NOTIFICATION_MSGS,
} from 'merchant/views/MagicCheckout/CODSettings/constants';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const ProductsModal = lazy(
  () => import(/* webpackChunkName: "MagicCODCategorySettings" */ './Modal'),
);

const ConfirmationModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicCODCategorySettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
    ),
);

const CredentialsModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicCODCategorySettings" */ 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce'
    ),
);

const CategorySettings = ({
  codEngineConfig,
  validateConfig,
  updateEngineConfig,
  deleteCategoryAction,
  createCategoryAction,
  showNotification,
  openModal,
  closeModal,
  isManualReviewOpted,
  isPrepayCODOpted,
  updateSettings,
  platform,
}): JSX.Element => {
  const { item_categories, configs, validations, zones } = codEngineConfig;
  const { cod_engine_type: codEngineType } = configs;

  const [errorText, setErrorText] = useState('');
  const [canCreateCategories, setCanCreateCategories] = useState(
    configs.cod_engine_type === COD_ENGINE_TYPES.PRODUCT && item_categories.length > 0,
  );

  const hasWoocommerceCredentials = isManualReviewOpted || isPrepayCODOpted;

  useEffect(() => {
    if (!validations.item_categories) {
      setErrorText('Required');
    } else {
      setErrorText('');
    }
  }, [validations, item_categories]);

  useEffect(() => {
    setCanCreateCategories(
      codEngineType === COD_ENGINE_TYPES.PRODUCT && item_categories.length > 0,
    );
  }, [item_categories, codEngineType]);

  const updateEngineType = (toggleState) => {
    validateConfig('item_categories', true);
    validateConfig('mapping', true);
    let payload = {};
    if (toggleState) {
      payload = {
        cod_engine_type: COD_ENGINE_TYPES.PRODUCT,
      };
    } else if (zones.length > 0) {
      payload = {
        cod_engine_type: COD_ENGINE_TYPES.LOCATION,
      };
    } else if (configs.rate_slabs) {
      payload = {
        cod_engine_type: COD_ENGINE_TYPES.SLAB_RATE,
      };
    } else {
      payload = {
        cod_engine_type: COD_ENGINE_TYPES.SLAB_ELIGIBILITY,
      };
    }
    updateEngineConfig(payload);
  };

  const showAlertNotification = () => {
    showNotification({
      type: 'neutral',
      message: NOTIFICATION_MSGS.credentialsModalClose,
      closeTimeout: 10000,
      className: 'magic-notification',
    });
    closeModal();
  };

  const handleToggleClick = (toggleState) => {
    if (item_categories.length === 0 && toggleState) {
      createCategoryAction({
        is_default: true,
        items: [],
      })
        .then(() => {
          updateEngineType(toggleState);
        })
        .catch(() => {
          showNotification({
            type: 'error',
            message: () => (
              <DisplayNotificationTxt notificationTxt="Something went wrong, please try again" />
            ),
          });
        });
    } else {
      updateEngineType(toggleState);
    }
  };

  const updateConfiguration = (payload: Record<string, any>, toggleState: boolean) => {
    const params = {
      platform,
      ...payload,
    };
    updateSettings(params, false).then(() => {
      showNotification({
        type: 'success',
        message: 'Credentials saved successfully.',
      });
      handleToggleClick(toggleState);
      closeModal();
    });
  };

  const openCredsModal = (toggleState: boolean) => {
    openModal({
      size: 'large',
      className: `woocommerceManualSettingModal`,
      component: (
        <SuspenseWithLoader type="center">
          <CredentialsModal
            platform="woocommerce"
            submitCredentials={(payload: Record<string, any>) =>
              updateConfiguration(payload, toggleState)
            }
            modalDesc="Magic checkout needs your Woocommerce credentials to create product categories."
            customCloseModal={showAlertNotification}
          />
        </SuspenseWithLoader>
      ),
    });
  };

  const handleSwitchMode = (toggleState: boolean) => {
    if (toggleState && platform === PLATFORMS.VALUES.WOOCOMMERCE && !hasWoocommerceCredentials) {
      openCredsModal(toggleState);
    } else {
      handleToggleClick(toggleState);
    }
  };

  const deleteCategory = useCallback((id) => {
    deleteCategoryAction(id)
      .then(() => {
        showNotification({
          type: 'success',
          message: () => <DisplayNotificationTxt notificationTxt="Category deleted successfully" />,
        });
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err?.errors[0] || 'Something went wrong',
        });
      })
      .finally(() => {
        closeModal();
      });
  }, []);

  const openProductsModal = (mode = MODAL_MODES.CREATE, id = null) => {
    openModal({
      size: 'medium',
      className: `codSettingModal`,
      component: (
        <SuspenseWithLoader type="center">
          <ProductsModal mode={mode} id={id} />
        </SuspenseWithLoader>
      ),
    });
  };

  const createMoreCategories = () => {
    openProductsModal(MODAL_MODES.ADD);
  };

  const handleDeleteClick = useCallback(
    (id) => () => {
      openModal({
        size: 'small',
        className: `magicToggleConfirmationModal`,
        component: (
          <SuspenseWithLoader type="center">
            <ConfirmationModal
              header="Delete category?"
              desc="Are you sure you want to delete this category"
              affirmativeLabel="Yes"
              abortLabel="No"
              onAffirm={() => deleteCategory(id)}
            />
          </SuspenseWithLoader>
        ),
      });
    },
    [],
  );

  const onEditClick = (id) => () => {
    openProductsModal(MODAL_MODES.EDIT, id);
  };

  return (
    <div className="cod-setting-item">
      <SettingsLabel
        value="Product categories"
        required={canCreateCategories}
        errorText={errorText}
        popoverContent={POPOVER_CONTENT.categories}
      />
      <div>
        <div className="cod-settings-toggle">
          <div className="slabs-radio">
            <SettingsToggle setting={{ value: canCreateCategories }} onToggle={handleSwitchMode} />
          </div>
          {canCreateCategories ? (
            <div className="cod-options-container">
              {item_categories.length === 0 ? (
                <button className="add-config-button" onClick={openProductsModal}>
                  + Add categories
                </button>
              ) : (
                <div className="cod-table-wrapper">
                  <DataTable
                    customClass="settings-table"
                    items={item_categories}
                    columns={[
                      categoryName,
                      productCount,
                      actions({ onEditClick, onDeleteClick: handleDeleteClick }),
                    ]}
                  />
                  <p onClick={createMoreCategories} className="add-more-button">
                    + Create more categories
                  </p>
                </div>
              )}
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  codEngineConfig: state.magicCODEngine,
  isManualReviewOpted: state.magicCheckout.cod_order_control,
  isPrepayCODOpted: state.magicCheckout.one_cc_prepay_cod_conversion,
  platform: state.magic_settings.platform,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification,
      validateConfig,
      updateEngineConfig,
      deleteCategoryAction: deleteCategory,
      createCategoryAction: createCategory,
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CategorySettings);
