import Button from 'common/new-ui/Button';
import { Modal } from 'common/new-ui/Modal';
import { checkIsMagicCheckoutField } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';
import 'merchant/views/PaymentPages/PaymentPages/components/Modals/MagicCheckout/style.styl';

const MagicCheckoutEnabledModal = ({ formField, onContinue, closeModal }) => {
  const showAddedMagicFields = () => {
    const listOfFields = [];

    formField.forEach((item) => {
      if (item?.name && checkIsMagicCheckoutField(item.name)) {
        const fieldLabel = item.name;
        const formattedFieldLabel = fieldLabel.charAt(0).toUpperCase() + fieldLabel.slice(1);
        listOfFields.push(<li key={formattedFieldLabel}>{formattedFieldLabel}</li>);
      }
    });

    return listOfFields;
  };

  return (
    <Modal className="modal magic-checkout-enabled-modal" showCloseBtn={false}>
      <div className="header">
        <p className="header-text">Confirm new settings for page?</p>
      </div>
      <div className="content">
        The below fields will be removed from this page and will now be collected during checkout:
      </div>
      <ul className="field-list">{showAddedMagicFields()}</ul>
      <div className="btn-section">
        <Button type="button" onClick={closeModal} className="modal-btn">
          Cancel
        </Button>
        <Button.Primary type="button" onClick={onContinue} className="modal-btn">
          Confirm and save
        </Button.Primary>
      </div>
    </Modal>
  );
};

export default MagicCheckoutEnabledModal;
