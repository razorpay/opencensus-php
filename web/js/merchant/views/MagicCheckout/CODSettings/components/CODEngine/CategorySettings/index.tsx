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
} from 'merchant/reducers/magicCheckout/codEngine/action';

import {
  POPOVER_CONTENT,
  MODAL_MODES,
  COD_ENGINE_TYPES,
} from 'merchant/views/MagicCheckout/CODSettings/constants';

const ProductsModal = lazy(
  () => import(/* webpackChunkName: "MagicCODCategorySettings" */ './Modal'),
);

const ConfirmationModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicCODCategorySettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
    ),
);

const CategorySettings = ({
  codEngineConfig,
  validateConfig,
  updateEngineConfig,
  deleteCategoryAction,
  openModal,
  closeModal,
}): JSX.Element => {
  const { item_categories, configs, validations, zones } = codEngineConfig;
  const [errorText, setErrorText] = useState('');
  const [canCreateCategories, setCanCreateCategories] = useState(item_categories.length > 0);

  useEffect(() => {
    if (!validations.item_categories) {
      setErrorText('Required');
    } else {
      setErrorText('');
    }
  }, [validations, item_categories]);

  const handleToggleClick = (toggleState) => {
    setCanCreateCategories(toggleState);
    validateConfig('item_categories', true);
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
        required
        errorText={errorText}
        popoverContent={POPOVER_CONTENT.categories}
      />
      <div>
        <div className="cod-settings-toggle">
          <div className="slabs-radio">
            <SettingsToggle setting={{ value: canCreateCategories }} onToggle={handleToggleClick} />
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
                      actions({ onEditClick, handleDeleteClick }),
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
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CategorySettings);
