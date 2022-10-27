import Button from 'common/new-ui/Button';
import { Modal } from 'common/new-ui/Modal';
import Shiprocket from 'assets/payment_pages/shiprocket.svg';
import { SHIPROCKET_DASHBOARD_LINK } from 'merchant/views/PaymentPages/PaymentPages/constants';
import 'merchant/views/PaymentPages/PaymentPages/components/Modals/MagicCheckout/style.styl';

const MagicShiprocketModal = ({ onClose, isEnabled }) => (
  <Modal className="modal magic-checkout-fields-modal" showCloseBtn={false}>
    <div className="header">
      <p className="header-text">
        <img src={Shiprocket} className="shiprocket-logo" alt="shiprocket-logo" />
        {`Shiprocket has been ${isEnabled ? 'enabled' : 'disabled'}`}
      </p>
    </div>
    {isEnabled && (
      <>
        <p className="order-info">
          Orders that are placed on this page will also be created on your Shiprocket account
        </p>
        <div className="shipping-delivery-info">
          Please add <b>Razorpay Payment pages</b> channel on your{' '}
          <a
            href={SHIPROCKET_DASHBOARD_LINK}
            target="_blank"
            rel="noreferrer noopener"
            className="pointer know-more-label"
          >
            Shiprocket dashboard <i className="i i-external-redirect" />
          </a>
          to complete setup
        </div>
      </>
    )}
    <div className="btn-section">
      <Button.Primary type="button" onClick={onClose} className="modal-btn">
        Okay, got it
      </Button.Primary>
    </div>
  </Modal>
);
export default MagicShiprocketModal;
