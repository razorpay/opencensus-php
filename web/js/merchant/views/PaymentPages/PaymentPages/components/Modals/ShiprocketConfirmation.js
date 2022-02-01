import React from 'react';
import { Modal, ModalMask } from 'common/new-ui/Modal';
import Alert from 'common/new-ui/Alert';
import UDFDisplayField from '../../Wysiwyg/FormSection/UDF/UDFDisplayField';
import { SHIPROCKET_FORM_ITEMS } from '../../Wysiwyg/FormSection/UDF/helpers';

const ShiprocketConfirmation = ({ handleClose, handleConfirm }) => {
  return (
    <ModalMask maskClosable={false}>
      <Modal class="Paymentpage-shiprocket-confirm" onClose={handleClose} showCloseBtn={false}>
        <div class="modal-header">
          <h3 class="modal-title">Address fields will be added to this page</h3>
        </div>
        <div class="modal-body">
          <p>
            <div>
              A few fields will be added to this page to collect customer’s shipping address. This
              is required to create orders on Shiprocket.
            </div>
            <Alert.Warning>
              Please remove any duplicate address fields that were previously added
            </Alert.Warning>
          </p>
          <div class="Modal__actions">
            <button type="button" class="btn btn-outline" onClick={handleClose}>
              Cancel
            </button>
            <button type="button" class="btn btn-primary" onClick={handleConfirm}>
              Continue
            </button>
          </div>
        </div>
      </Modal>
      <Modal class="Paymentpage-shiprocket-fields-preview" showCloseBtn={false}>
        <div className="UI-form">
          {SHIPROCKET_FORM_ITEMS.map((fi, idx) => (
            <UDFDisplayField
              key={fi.name}
              index={idx}
              indexInRenderOrder={idx}
              field={fi}
              isListSorting={true}
              checkoutOptions={{}}
              onDeleteFormItem={noop}
              onSubmitUDFField={noop}
              validateSameTitleExists={noop}
              isPreview
            />
          ))}
        </div>
      </Modal>
    </ModalMask>
  );
};

function noop() {}

export default ShiprocketConfirmation;
