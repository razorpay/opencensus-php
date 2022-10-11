import Pointer from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/common/Pointer';
import DemoVideo from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce/container/Demovideo';
import { MANUAL_REVIEW_MODAL } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const InfoContainer = ({ platform }) => (
  <>
    <div className="modal-ellipse modal-ellipse-right" />
    <div className="woocommerce-modal-info">
      <Pointer />
      <p className="info-point">How to generate WooCommerce API credentials?</p>
    </div>
    <div className="modal-ellipse modal-ellipse-left" />
    <DemoVideo platform={platform} />
    <div className="modal-square-icon" />
    <img
      src={MANUAL_REVIEW_MODAL[platform].icon}
      alt="woocommerce-icon"
      className="woocommerce-modal-divider-icon"
    />
  </>
);

export default InfoContainer;
