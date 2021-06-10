import React from 'react';
import { classList } from 'common/utils/rzp-utils';
import Button from 'common/new-ui/Button';
import Image from 'common/ui/Image';
import Spinner from 'common/ui/Spinner';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

const QRCodePreviewModal = React.memo(({ id, previewImg, closeModal, history, onDownloadQRCode }) => {
  function downloadQRCode() {
    window.open(previewImg);

    onDownloadQRCode && onDownloadQRCode();
  }

  function toToDashboard() {
    closeModal();

    if (history) {
      history.push('/qr_codes');
    }
  }

  return (
    <div>
      <div class="heading">
        <img src="/dist/css/assets/onboarding/done.png" class="m-r" /> QR Code Created Successfully
      </div>
      <div class="content">
        <img src={previewImg} alt="qr-code" download />
      </div>
      <div class="footer">
        <Button.Secondary onClick={toToDashboard}>Back to Dashboard</Button.Secondary>
        <Button.Primary onClick={downloadQRCode}>Download QR Code</Button.Primary>
      </div>
    </div>
  );
});

export default QRCodePreviewModal;
