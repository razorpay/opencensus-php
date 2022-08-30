import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';

const VideoModal = ({
  visible = false,
  onClose = () => {},
  width = '',
  height = '',
  src = '',
  title = 'video player',
  frameBorder = 0,
  allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
  allowFullScreen = true,
  fullWidth = false,
  maskClosable = false,
}) => {
  return visible ? (
    <ModalMask className="video-modal" onClose={onClose} maskClosable={maskClosable}>
      <Modal onClose={onClose} fullWidth={fullWidth}>
        <iframe
          id="video-modal"
          width={width}
          height={height}
          src={src}
          title={title}
          frameBorder={frameBorder}
          allow={allow}
          allowFullScreen={allowFullScreen}
        />
      </Modal>
    </ModalMask>
  ) : null;
};

export default VideoModal;
