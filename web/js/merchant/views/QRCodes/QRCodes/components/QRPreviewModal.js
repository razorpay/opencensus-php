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
      <div className="heading">
        <img src={DoneImage} className="m-r" /> QR Code Created Successfully
        <button type="button" className="close" onClick={toToDashboard}>
          <i className="i i-close" />
        </button>
      </div>
      <div className="content">
        <img src={previewImg} alt="qr-code" download />
      </div>
      <div className="footer">
        <Button.Secondary onClick={toToDashboard}>Back to Dashboard</Button.Secondary>
        <Button.Primary onClick={downloadQRCode}>Download QR Code</Button.Primary>
      </div>
    </div>
  );
});

export default QRCodePreviewModal;
