// Helper function to add padding to images
import ModalHeader from 'common/ui/ModalHeader';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import Image from '../../../../common/ui/Image';

const PaddedImageWithZoom = ({ openModal, closeModal, brandColor, ...rest }) => {
  const onImageClick = () => {
    openModal({
      size: 'xlarge',
      className: 'App-store-app--Modal',
      component: (
        <div className="app-store-setup-image-modal">
          <ModalHeader onCloseClick={closeModal} />
          <div className="modal-body">
            <Image className="content-zoomed-image" {...rest} />
          </div>
        </div>
      ),
    });
  };

  return (
    <div className="content-image-holder" style={{ backgroundColor: brandColor }}>
      <Image className="content-image" {...rest} onClick={onImageClick} />
    </div>
  );
};

function PadImage(brandColor) {
  return connect(
    () => ({
      brandColor,
    }),
    { openModal, closeModal },
  )(PaddedImageWithZoom);
}

export default PadImage;
