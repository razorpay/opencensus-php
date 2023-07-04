import { useEffect, useMemo, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { closeModal } from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';
import { Button } from '@razorpay/blade/components';
import RateSlabs from './RateSlabs';
import { showNotification } from 'merchant_common/reducers/notifications';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import { MODAL_MODES, COD_ENGINES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { upsertFeeRules, updateFeeRule } from 'merchant/reducers/magicCheckout/codEngine/action';
import {
  formatRulesToSlabs,
  formatSlabsToFeeRules,
  getRangedRules,
} from 'merchant/views/MagicCheckout/CODSettings/utils';
import { paiseToRupees } from 'common/utils/rzp-utils';

function SlabModal({ cod_engine_config, closeModal, mode, id, upsertFeeRules, showNotification }) {
  const editMode = mode === MODAL_MODES.EDIT;
  const { fee_rules, configs, loading } = cod_engine_config;
  const hasRates = configs.rate_slabs || configs.engine === COD_ENGINES.ADVANCED;
  const [slabs, setSlabs] = useState([]);
  const MODAL_HEADER = `${editMode ? 'Edit' : 'Create'} COD eligibility slabs`;
  useEffect(() => {
    if (fee_rules.length) {
      const slabs = formatRulesToSlabs(fee_rules);
      if (!editMode) {
        const last_rule = fee_rules[fee_rules.length - 1];
        slabs.push({
          fee: 0,
          gte: paiseToRupees(last_rule.rule.order_amount.lte) + 1,
          lte: '',
          error: {
            lte: '',
            gte: '',
            fee: '',
          },
        });
      }

      setSlabs(slabs);
    }
  }, [fee_rules, id, mode, editMode]);
  const updateSlabs = (newSlabs) => {
    setSlabs(newSlabs);
  };

  const disabled = useMemo(() => {
    return (
      slabs.length === 0 ||
      slabs?.some(
        (slab) => slab?.error?.lte?.length || slab?.error?.gte?.length || slab?.error?.fee?.length,
      )
    );
  }, [slabs]);

  const confirmSlabs = () => {
    const rules = formatSlabsToFeeRules(slabs, hasRates);
    const payload = {};
    payload.fee_rules = getRangedRules(rules, fee_rules, configs.engine === COD_ENGINES.ADVANCED);
    upsertFeeRules(payload)
      .then(() => {
        showNotification({
          type: 'success',
          message: () => (
            <DisplayNotificationTxt
              notificationTxt={`${editMode ? 'Slab updated' : 'Slabs create'} created successfully`}
            />
          ),
        });
        closeModal();
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err?.errors[0] || 'Something went wrong',
        });
      });
  };

  return (
    <div className="slab-modal cod-settings-modal" data-testid="slab-modal">
      <ModalHeader title={MODAL_HEADER} extraClass="no-padding" onCloseClick={closeModal} />
      <div className="items-container">
        <RateSlabs slabs={slabs} updateSlabs={updateSlabs} editMode={editMode} />
      </div>
      <div className="actions-container">
        <div className="actions-text" />
        <div className="actions">
          <Button onClick={closeModal} isDisabled={loading.fee_rules} variant="secondary">
            Cancel
          </Button>
          <Button
            testID="save-slab"
            isDisabled={disabled || loading.fee_rules}
            isLoading={loading.fee_rules}
            onClick={confirmSlabs}
          >
            {slabs.length <= 1 ? 'Save slab' : 'Save slabs'}
          </Button>
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  cod_engine_config: state.magicCODEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
      upsertFeeRules,
      updateFeeRule,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(SlabModal);
