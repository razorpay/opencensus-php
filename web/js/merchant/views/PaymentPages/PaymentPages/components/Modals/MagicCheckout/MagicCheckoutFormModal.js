import { useState } from 'react';
import Button from 'common/new-ui/Button';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import 'merchant/views/PaymentPages/PaymentPages/components/Modals/MagicCheckout/style.styl';

const MagicCheckoutFormModal = ({ onClose }) => {
  const [expanded, setExpanded] = useState(false);

  const handleExpand = () => {
    setExpanded(!expanded);
  };
  return (
    <ModalMask>
      <Modal className="modal magic-checkout-fields-modal" showCloseBtn={false}>
        <div className="header">
          <p className="header-text">
            <i className="i i-triangle-alert" /> This input field cannot be added
          </p>
        </div>
        <div className="content">
          Your customers’ delivery details are already being collected during checkout along with
          the payment for this page.
        </div>
        <div onClick={handleExpand} className={`field-list-header ${expanded ? 'expanded' : ''}`}>
          Details collected <i className={`i i-${expanded ? 'chevron-up' : 'chevron-down'}`} />
        </div>
        {expanded && (
          <ul className="field-list">
            <li>Email</li>
            <li>Phone</li>
            <li>Address</li>
            <li>Pincode</li>
            <li>City</li>
            <li>State</li>
          </ul>
        )}
        <p className="disable-info">To turn off Magic checkout, go to Magic Checkout Settings</p>
        <div className="btn-section">
          <Button.Primary type="button" onClick={onClose} className="modal-btn">
            Okay, got it
          </Button.Primary>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default MagicCheckoutFormModal;
