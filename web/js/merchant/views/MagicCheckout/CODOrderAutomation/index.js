import { useState, useCallback, useEffect } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import ConfirmationModal from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import Spinner from 'common/ui/Spinner';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  fetchAutomationConfigs,
  updateAutomationConfigs,
} from 'merchant/reducers/magicCheckout/codOrderAutomation/action';

import 'merchant/views/MagicCheckout/CODOrderAutomation/CODOrderAutomation.styl';

import {
  RULE_TYPE_OPTIONS,
  RULE_ACTION_OPTIONS,
  RULE_TYPES,
  RULE_ACTIONS,
  CONFIRMATION_MODAL_TEXTS,
} from 'merchant/views/MagicCheckout/CODOrderAutomation/constant';

export const removeConfigsConfirmationModal = (openModal, deleteAllConfigs) => {
  openModal({
    size: 'small',
    className: `remove-configs-confirmation-modal`,
    component: (
      <ConfirmationModal
        header={CONFIRMATION_MODAL_TEXTS.heading}
        desc={CONFIRMATION_MODAL_TEXTS.description}
        affirmativeLabel={CONFIRMATION_MODAL_TEXTS.primaryLabel}
        abortLabel={CONFIRMATION_MODAL_TEXTS.secondaryLabel}
        onAffirm={deleteAllConfigs}
      />
    ),
  });
};

const CODOrderAutomation = ({
  openModal,
  codOrdersAutomation,
  fetchConfigs,
  updateConfigs,
  showNotification,
  closeModal,
}) => {
  const { isLoading, ruleConfigs, error, isPending } = codOrdersAutomation;

  const [configs, setConfigs] = useState([
    { type: '', action: '', disabled: false, options: RULE_TYPE_OPTIONS },
  ]);

  const [hasSavedConfigs, setHaveSavedConfigs] = useState(false);

  const [isRequired, setIsRequired] = useState(false);

  useEffect(() => {
    if (fetchConfigs && !ruleConfigs) fetchConfigs();
  }, [fetchConfigs, ruleConfigs]);

  useEffect(() => {
    if (error)
      showNotification({
        type: 'error',
        message: 'Something went wrong, please try again after sometime.',
      });
  }, [error]);

  useEffect(() => {
    if (!ruleConfigs || !ruleConfigs?.data) {
      setConfigs([{ type: '', action: '', disabled: false, options: RULE_TYPE_OPTIONS }]);
      return;
    }

    setHaveSavedConfigs(true);

    let newOptions = [...RULE_TYPE_OPTIONS];

    const newConfigs = ruleConfigs.data.map((config, index) => {
      const createConfig = {
        type: config.rule_value,
        action: config.rule_action,
        disabled: index !== ruleConfigs.data.length - 1,
        options: newOptions,
      };

      newOptions = newOptions.filter((option) => option.name !== config.rule_value);

      return createConfig;
    });

    setConfigs(newConfigs);
  }, [ruleConfigs]);

  const setFields = useCallback(
    (e) => {
      const { name, value } = e.target;

      const newConfigs = [...configs];
      newConfigs[newConfigs.length - 1][name] = value;

      setIsRequired(false);
      setConfigs(newConfigs);
    },
    [configs],
  );

  const deleteAllConfigs = useCallback(() => {
    updateConfigs();
    closeModal();
  }, [updateConfigs, closeModal]);

  const addMoreConfigs = useCallback(() => {
    const currentInd = configs.length - 1;

    if (
      configs.length >= 3 ||
      configs[currentInd].type === '' ||
      configs[currentInd].action === ''
    ) {
      setIsRequired(true);
      return;
    }

    setIsRequired(false);
    configs[currentInd].disabled = true;

    let newOptions = [...RULE_TYPE_OPTIONS];

    configs.forEach((config) => {
      newOptions = newOptions.filter((option) => option.name !== config.type);
    });

    const newConfigs = [...configs, { type: '', action: '', disabled: false, options: newOptions }];

    setConfigs(newConfigs);
  }, [configs]);

  const removeConfigs = useCallback(
    (e) => {
      const { arrInd } = e.target.dataset;
      const parsedArrInd = parseInt(arrInd, 10);

      const currentInd = configs.length - 1;

      const newConfigs = [...configs];

      if (parsedArrInd === 0 && currentInd === parsedArrInd) {
        removeConfigsConfirmationModal(openModal, deleteAllConfigs);
      } else if (
        parsedArrInd !== currentInd &&
        configs[currentInd].type === '' &&
        configs[currentInd].action === ''
      ) {
        setIsRequired(true);
      } else {
        newConfigs.splice(parsedArrInd, 1);

        const newCurrInd = newConfigs.length - 1;
        newConfigs[newCurrInd].disabled = false;

        if (parsedArrInd !== currentInd) {
          let newOptions = [...RULE_TYPE_OPTIONS];

          newConfigs.forEach((config) => {
            newOptions = newOptions.filter(
              (option) =>
                option.name === '' || config.disabled === false || option.name !== config.type,
            );
          });

          newConfigs[newCurrInd].options = newOptions;
        }

        setConfigs(newConfigs);
      }
    },
    [configs, openModal, updateConfigs],
  );

  const saveConfigs = useCallback(() => {
    const currInd = configs.length - 1;

    if (configs[currInd].action === '' || configs[currInd].type === '') {
      setIsRequired(true);
      return;
    }

    updateConfigs(configs);
  }, [configs]);

  const getConfigValue = useCallback(
    (riskType) => {
      const config = ruleConfigs?.data?.filter((config) => config.rule_value === riskType);

      return config?.length ? RULE_ACTIONS[config[0].rule_action] : 'NA';
    },
    [ruleConfigs],
  );

  if (isLoading) {
    return (
      <div className="spinner-container">
        <Spinner />
      </div>
    );
  }

  const onEdit = () => setHaveSavedConfigs(false);

  const getInputClassName = (value) => {
    return value === '' ? (isRequired ? 'required Input--empty' : 'Input--empty') : '';
  };

  return (
    <div className="cod-order-automation-container">
      <div className="tab-content">
        <h3>COD Review Workflow</h3>
        <p>
          Configure workflows to automatically approve/hold/cancel your COD orders based on RTO
          risk.
        </p>
        <hr />
        {!hasSavedConfigs ? (
          <>
            <div className="config-container">
              <div className="config-labels-container display-flex">
                <div className="config-input-header col-md-3">Type of RTO risk</div>
                <div className="config-input-header col-md-5">Take action</div>
              </div>
              {configs.map((item, index) => (
                <div className="config-inputs-wrapper display-flex" key={index}>
                  <div className="config-input col-md-3">
                    <Input.Select
                      name="type"
                      value={item.type}
                      onChange={setFields}
                      options={item.options}
                      disabled={item.disabled}
                      className={getInputClassName(item.type)}
                      data-testid="type-combobox"
                    />
                    {isRequired && item.type === '' ? (
                      <p className="required-text">Required</p>
                    ) : null}
                  </div>
                  <div className="config-input col-md-5">
                    <Input.Select
                      name="action"
                      value={item.action}
                      onChange={setFields}
                      options={RULE_ACTION_OPTIONS}
                      disabled={item.disabled}
                      className={getInputClassName(item.action)}
                      data-testid="action-combobox"
                    />
                    {isRequired && item.action === '' ? (
                      <p className="required-text">Required</p>
                    ) : null}
                  </div>
                  {((index === 0 && item.type !== '' && item.action !== '') || index !== 0) && (
                    <div className="display-flex pointer config-close flex-center">
                      <i
                        className="i i-close"
                        onClick={removeConfigs}
                        data-arr-ind={index}
                        data-testid="remove-cta"
                      />
                    </div>
                  )}
                </div>
              ))}
              {configs.length !== 3 ? (
                <div
                  className="add-configs-cta font-12 font-bold pointer display-inline"
                  onClick={addMoreConfigs}
                >
                  + Add more conditions
                </div>
              ) : null}
            </div>
            <div className="save-settings-cta-container">
              <AsyncBtn.Primary
                type="button"
                isPending={isPending}
                className="btn btn-primary"
                onClick={saveConfigs}
                showLoader={isPending}
              >
                Save settings
              </AsyncBtn.Primary>
            </div>
          </>
        ) : (
          <>
            <div className="saved-configs-wrapper">
              <div className="saved-configs-container">
                <div className="saved-configs-header">
                  <p>Workflow conditions</p>
                  <div className="configs-edit pointer" onClick={onEdit}>
                    <i className="i i-edit_board configs-edit-icon" />
                    Edit
                  </div>
                </div>
                <hr />
                <div className="saved-configs-values-container">
                  {RULE_TYPES.map((item) => (
                    <div className="saved-config display-flex flex--column gap--4" key={item}>
                      <p className="saved-config-condition">{`If RTO risk is ${item}`}</p>
                      <p className="saved-config-action">{getConfigValue(item)}</p>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  codOrdersAutomation: state.magicCODOrdersAutomation,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      fetchConfigs: fetchAutomationConfigs,
      updateConfigs: updateAutomationConfigs,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CODOrderAutomation);
