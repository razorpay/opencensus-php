import React, { useEffect } from 'react';
import {
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Button,
  Box,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  createShippingMethod,
  updateShippingMethod,
} from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import { MODAL_MODES } from 'merchant/views/MagicCheckout/common/components/SettingsModal/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import Form from './Form';
import { useFormContext } from './FormContext';
import { ShippingMethodsModalProps } from './types';
import { buildShippingMethodsPayload, validateInputs } from './helpers';

const ShippingModal = ({
  isOpen,
  shippingEngine,
  disableConfirmButton,
  closeModal,
  zone,
  mode,
  createShippingMethod,
  updateShippingMethod,
  showNotification,
}: ShippingMethodsModalProps): JSX.Element => {
  const { values, selectedMethod, resetForm } = useFormContext();
  const { isLoading } = shippingEngine;
  const isEditMode = mode === MODAL_MODES.EDIT;
  const handleConfirm = () => {
    const isValid = validateInputs(values);
    if (!isValid) {
      showNotification({
        type: 'error',
        message: () => (
          <DisplayNotificationTxt notificationTxt="Please enter valid values for the form" />
        ),
      });
      return;
    }
    if (shippingEngine.selected_profile) {
      const payload = buildShippingMethodsPayload(values);
      if (isEditMode && selectedMethod?.id) {
        payload.id = selectedMethod.id;
      }
      const actionFn = isEditMode ? updateShippingMethod : createShippingMethod;
      actionFn({
        ...payload,
        zone_id: zone.id,
        item_category_id: shippingEngine.shipping_profiles[shippingEngine.selected_profile].id,
      })
        .then(() => {
          showNotification({
            type: 'success',
            message: () => (
              <DisplayNotificationTxt notificationTxt="Shipping method added successfully" />
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
    }
  };

  useEffect(() => {
    if (mode === MODAL_MODES.CREATE) {
      resetForm();
    }
  }, [mode]);

  return (
    <Modal isOpen={isOpen} onDismiss={closeModal} size="medium">
      <ModalHeader title={`Shipping Methods - ${zone.name}`} />
      <ModalBody>
        <Form />
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            isDisabled={isLoading.shipping_methods || disableConfirmButton}
            onClick={closeModal}
            variant="secondary"
          >
            Cancel
          </Button>
          <Button
            testID="confirm-button"
            isLoading={isLoading.shipping_methods}
            isDisabled={isLoading.shipping_methods || disableConfirmButton}
            onClick={handleConfirm}
          >
            Confirm
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapStateToProps = (state) => ({
  shippingEngine: state.magicShippingEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      createShippingMethod,
      updateShippingMethod,
      showNotification,
    },
    dispatch,
  );

const Component: ({
  isOpen,
  isLoading,
  disableConfirmButton,
  closeModal,
  zone,
}: Omit<
  ShippingMethodsModalProps,
  'showNotification' | 'createShippingMethod' | 'updateShippingMethod' | 'shippingEngine'
>) => JSX.Element = connect(mapStateToProps, mapDispatchToProps)(ShippingModal);

export default Component;
