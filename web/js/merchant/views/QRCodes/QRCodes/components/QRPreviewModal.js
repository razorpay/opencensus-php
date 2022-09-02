import React from 'react';
import Button from 'common/new-ui/Button';

import DoneImage from 'assets/onboarding/done.png';

const QRCodePreviewModal = React.memo(({ previewImg, closeModal, history, onDownloadQRCode }) => {
  function downloadQRCode() {
    window.open(previewImg);

    if (onDownloadQRCode) onDownloadQRCode();
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
        <img src={DoneImage} class="m-r" /> QR Code Created Successfully
        <button type="button" class="close" onClick={toToDashboard}>
          <i class="i i-close" />
        </button>
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
