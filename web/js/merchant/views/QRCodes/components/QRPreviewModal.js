import { connect } from 'react-redux';
import { classList } from 'common/utils/rzp-utils';
import Button from 'common/new-ui/Button';

import useLocalStorageCheck from 'merchant/hooks/localStorageCheck';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

const QRCodePreviewModal = React.memo(({ url }) => {
  function downloadQRCode() {}

  function toToDashboard() {}

  return (
    <ModalMask>
      <Modal
        class={classList('QRCode--Preview', 'animate-down')}
        showCloseBtn={false}
        onClose={toggleIsHidden}
      >
        <ModalContent>
          <div class="content">
            <img src={url} alt="qr-code" />
          </div>
          <div class="footer">
            <Button onClick={toToDashboard}>Back to Dashboard</Button>
            <Button.Primary onClick={downloadQRCode}>Download</Button.Primary>
          </div>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
});

export default QRCodePreviewModal;
