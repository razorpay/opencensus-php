import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  fetchShippingMethods,
  createShippingMethods,
  updateUserShippingMethod,
  updateShippingMethods,
  resetUserShippingMethods,
} from 'merchant/reducers/magicCheckout/shipping_services/actions';
import { useState, useCallback } from 'react';
import * as ModalActions from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';
import ShipRocketIcon from 'merchant/views/MagicCheckout/ShippingServices/assets/shiprocket.svg';
import FeeConfiguration from 'merchant/views/MagicCheckout/common/components/FeeConfiguration';
import { validate } from 'merchant/views/MagicCheckout/utils/shippingSettingValidation';
import Input from 'common/new-ui/Input';
import { FEE_RULES } from 'merchant/views/MagicCheckout/constants';

const options = [
  { label: 'YES', name: 'true', value: true },
  { label: 'NO', name: 'false', value: false },
];

const ServiceabilitySettingsModal = ({
  closeModal,
  updateUserMethods,
  createMethods,
  updateMethods,
  id,
  userShippingMethods,
  method = 'post',
  resetUserMethods,
}) => {
  const [validationError, setValidationError] = useState({
    error: false,
    errorField: '',
  });

  const { error, errorField } = validationError;

  const { warehouse_pincode, enable_cod, shipping_fee_rule, cod_fee_rule } = userShippingMethods;

  const removeError = () => {
    setValidationError({
      error: false,
      errorField: '',
    });
  };

  const handleSaveServiceabilityClick = useCallback(() => {
    if (error) return;
    const payload = userShippingMethods;
    payload.shipping_provider_id = id;
    const vError = validate(payload);
    setValidationError(vError);
    if (!vError?.error) {
      method !== 'put' ? createMethods(payload) : updateMethods(payload);
    }
  }, [setValidationError, createMethods, userShippingMethods, error]);

  const handleCODAvailabilityChange = useCallback(
    (e) => {
      if (error) removeError();
      const { type } = e?.target?.dataset;
      const val = type === 'enable_cod' ? JSON.parse(e?.target?.value) : e?.target?.value;
      updateUserMethods(type, val);
    },
    [updateUserMethods, error],
  );

  const handleClose = () => {
    resetUserMethods();
    closeModal();
  };

  return (
    <div className="row">
      <div className="serviceability-setting-container">
        <div className="display-flex align-center justify-space-between">
          <div className="display-flex flex-center">
            <img alt="shiprocket-logo" className="connect-container-logo" src={ShipRocketIcon} />
            <span className="serviceability-setting-header font-bold font-header">
              SERVICEABILTY SETTING
            </span>
          </div>
          <ModalHeader onCloseClick={handleClose} />
        </div>
        <div>
          <div className="filter-item link-account-instruction">
            <label className="serviceability-setting-label font-bold" for="warehouse-pincode">
              Warehouse Pincode<sup className="magic-checkout-color-red">*</sup>
            </label>
            <Input
              id="warehouse-pincode"
              value={warehouse_pincode}
              placeholder=" Enter warehouse pincode from where you ship orders"
              data-type="warehouse_pincode"
              className={`serviceability-setting-input 
              ${error && errorField === 'warehouse_pincode' ? ' input-invalid' : ''}`}
              onChange={handleCODAvailabilityChange}
            />
          </div>
          <div className="filter-item link-account-instruction">
            <label className="serviceability-setting-label" for="cod-availability">
              COD Availablity <sup className="magic-checkout-color-red">*</sup>
            </label>
            <Input.Select
              required
              options={options}
              value={enable_cod}
              data-type="enable_cod"
              className="serviceability-setting-input serviceability-setting-select"
              // Parsed e.target.value to covert the value to bool
              onChange={handleCODAvailabilityChange}
            />
          </div>
          <div className="filter-item link-account-instruction c-shiprocket-serviceability">
            <FeeConfiguration
              type={FEE_RULES.SHIPPING_FEE_RULE}
              feeRule={{ ...shipping_fee_rule }}
              updateUserFeeRule={updateUserMethods}
              validationError={
                validationError.errorField === FEE_RULES.SHIPPING_FEE_RULE ? validationError : {}
              }
              removeError={removeError}
            />
          </div>
          {enable_cod ? (
            <div className="filter-item link-account-instruction c-shiprocket-serviceability">
              <FeeConfiguration
                type={FEE_RULES.COD_FEE_RULE}
                feeRule={{ ...cod_fee_rule }}
                updateUserFeeRule={updateUserMethods}
                validationError={
                  validationError.errorField === FEE_RULES.COD_FEE_RULE ? validationError : {}
                }
                removeError={removeError}
              />
            </div>
          ) : null}
        </div>
      </div>
      <div className="serviceability-settings-modal-cta-container bg-white">
        <div
          className={`serviceability-settings-modal-cta font-bold pointer
          ${error ? ' serviceability-settings-cta-disabled' : ''}`}
          onClick={handleSaveServiceabilityClick}
        >
          Save Serviceability Settings
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  userShippingMethods: state.shippingService.userShippingMethods,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      fetch: fetchShippingMethods,
      createMethods: createShippingMethods,
      updateUserMethods: updateUserShippingMethod,
      updateMethods: updateShippingMethods,
      resetUserMethods: resetUserShippingMethods,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(ServiceabilitySettingsModal);
