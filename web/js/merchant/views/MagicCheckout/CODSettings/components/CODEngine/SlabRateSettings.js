import React, { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { EditComposeIcon, Link } from '@razorpay/blade/components';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Input from 'common/new-ui/Input';
import DataTable from 'common/ui/Table/DataTable';

import ConfirmationModal, {
  DisplayNotificationTxt,
} from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import {
  slabRange,
  slatRate,
  actions,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/cellItem';
import SettingsLabel from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SettingsLabel';
import PreventDeleteModal from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/PreventDeleteModal';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  updateEngineConfig,
  deleteFeeRule,
} from 'merchant/reducers/magicCheckout/codEngine/action';

import {
  SLAB_RATE_RADIO_INPUT,
  POPOVER_CONTENT,
  MODAL_MODES,
  COD_ENGINE_TYPES,
} from 'merchant/views/MagicCheckout/CODSettings/constants';

const MAX_FEE_RULES = 20;

const SlabModal = lazy(() =>
  import(
    /* webpackChunkName: "SlabModal" */ 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SlabModal'
  ),
);

function SlabRateSettings({
  codEngineConfig,
  openModal,
  closeModal,
  showNotification,
  updateEngineConfig,
  deleteFeeRule,
}) {
  const { fee_rules, configs, validations } = codEngineConfig;
  const [errorText, setErrorText] = useState('');

  useEffect(() => {
    if (!validations.fee_rules) {
      setErrorText('Required');
    } else {
      setErrorText('');
    }
  }, [validations, fee_rules]);

  const handleRuleTypeChange = (e) => {
    const val = e?.target?.value;
    if (!val) return;
    const currentEngineHasRate = configs.cod_engine_type !== COD_ENGINE_TYPES.SLAB_ELIGIBILITY;
    const payload = {};
    payload.rate_slabs = val == 'true';
    payload.cod_engine_type = currentEngineHasRate
      ? configs.cod_engine_type
      : COD_ENGINE_TYPES.SLAB_RATE;
    updateEngineConfig(payload);
  };

  const openSlabModal = (mode = MODAL_MODES.CREATE, id = null) => {
    openModal({
      size: 'medium',
      className: `codSettingModal`,
      component: (
        <SuspenseWithLoader type="center">
          <SlabModal mode={mode} id={id} />
        </SuspenseWithLoader>
      ),
    });
  };

  const deleteSlab = (id) => {
    deleteFeeRule(id)
      .then(() => {
        showNotification({
          type: 'success',
          message: () => <DisplayNotificationTxt notificationTxt="Slab deleted successfully" />,
        });
      })
      .finally(() => {
        closeModal();
      });
  };

  const onDeleteClick = useCallback(
    (id) => () => {
      if (fee_rules.length === 1) {
        openModal({
          size: 'small',
          className: `magicToggleConfirmationModal`,
          component: <PreventDeleteModal />,
        });
      } else {
        openModal({
          size: 'small',
          className: `magicToggleConfirmationModal`,
          component: (
            <ConfirmationModal
              header="Delete slab?"
              desc="Are you sure you want to delete this slab"
              affirmativeLabel="Yes"
              abortLabel="No"
              onAffirm={() => deleteSlab(id)}
            />
          ),
        });
      }
    },
    [],
  );
  const onEditClick = () => openSlabModal(MODAL_MODES.EDIT);

  const createMoreSlabs = () => {
    openSlabModal(MODAL_MODES.ADD);
  };

  const TABLE_COLUMNS = [slabRange, actions({ onDeleteClick })];
  if (configs.rate_slabs) {
    TABLE_COLUMNS.splice(1, 0, slatRate);
  }

  return (
    <div className="cod-setting-item">
      <SettingsLabel
        value="Cart order value"
        required
        errorText={errorText}
        popoverContent={POPOVER_CONTENT.slabs}
      />

      <div className="cod-settings-toggle">
        <div className="slabs-radio">
          <Input.Radio
            name="slabs"
            defaultValue={configs.rate_slabs}
            options={SLAB_RATE_RADIO_INPUT}
            onChange={handleRuleTypeChange}
          />
        </div>
        <div className="cod-options-container">
          {fee_rules.length === 0 ? (
            <button className="add-slab-button" onClick={openSlabModal}>
              + Add slabs
            </button>
          ) : (
            <div className="cod-table-wrapper">
              <div className="edit-icon">
                <Link
                  onClick={onEditClick}
                  icon={EditComposeIcon}
                  iconPosition="left"
                  variant="button"
                >
                  Edit
                </Link>
              </div>

              <DataTable customClass="settings-table" items={fee_rules} columns={TABLE_COLUMNS} />
              {fee_rules.length <= MAX_FEE_RULES && (
                <p onClick={createMoreSlabs} className="add-more-button">
                  + Create more slabs
                </p>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  codEngineConfig: state.magicCODEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification,
      updateEngineConfig,
      deleteFeeRule,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(SlabRateSettings);
