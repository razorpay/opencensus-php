import ModalHeader from 'common/ui/ModalHeader';
import { DISCONNECT_TEXTS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

const DisableModal = ({ modalType, closeModal, handleDisconnect }) => {
  const { header, subText, desc, secondaryCtaLabel, primaryCtaLabel } = DISCONNECT_TEXTS[modalType];
  return (
    <div className="disable-modal">
      <ModalHeader
        title={header}
        extraClass={`${modalType === 'serviceability' ? ' font-heading' : ''} no-padding`}
        onCloseClick={closeModal}
      />
      <div className="font-bold disable-modal-subtext">{subText}</div>
      <div>{desc}</div>
      <div className="disable-modal-ctas-container">
        <div className="pointer disable-modal-secondary-cta display-inline" onClick={closeModal}>
          {secondaryCtaLabel}
        </div>
        <div
          className="pointer color-white disable-modal-primary-cta display-inline"
          onClick={handleDisconnect}
        >
          {primaryCtaLabel}
        </div>
      </div>
    </div>
  );
};

export default DisableModal;
