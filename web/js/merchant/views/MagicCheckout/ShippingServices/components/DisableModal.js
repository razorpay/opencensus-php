import ModalHeader from 'common/ui/ModalHeader';

const TEXTS = {
  serviceability: {
    header: 'Disable serviceability using shiprocket',
    subText: 'Are you sure you want to disable ?',
    desc:
      'Razorpay will stop receiving Pincode serviceablity updates from your Shiprocket account. This will remove your shipping & COD settings.',
    secondaryCtaLabel: "No, don't disable",
    primaryCtaLabel: 'Yes, disable',
  },
  disconnect: {
    header: 'Disconnect Shiprocket',
    subText: 'Are you sure you want to disconnect ?',
    desc:
      'Razorpay will stop receiving order status, Pincode serviceablity updates from your Shiprocket account. This will remove your shipping & COD settings.',
    secondaryCtaLabel: 'No, don’t disconnect',
    primaryCtaLabel: 'Yes, disconnect',
  },
};

const DisableModal = ({ modalType, closeModal, handleDisconnect }) => {
  const { header, subText, desc, secondaryCtaLabel, primaryCtaLabel } = TEXTS[modalType];
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
