import React from 'react';
import Button from 'component/Button';
import CreatorModal from '../CreatorModal';
import Croppie from 'croppie';

export default class ImageCropper extends React.PureComponent {
  componentDidMount() {
    const {
      viewPort = { width: 100, height: 100, type: 'square' },
      boundary = { width: '100%', height: 200 },
    } = this.props;

    this.vanilla = new Croppie(this.cropperAreaEl, {
      viewport: viewPort,
      boundary,
      showZoomer: true,
      enableOrientation: false,
    });

    this.vanilla.bind({
      url: 'https://cdn.razorpay.com/logos/D3JjREAG8erHB7_large.jpg',
    });
  }

  onSaveImage = () => {
    this.vanilla.result('blob').then(function(blob) {
      console.log('.....UPLOAD THE CROPPED BLOB.....');
    });
  };

  setRef = el => (this.cropperAreaEl = el);

  render() {
    const { onClose } = this.props;

    return (
      <div class="Input-ImageCropper">
        <div class="Cropper-area" ref={this.setRef} />

        <div class="btn-group pull-right">
          <Button.Transparent onClick={onClose}>Cancel</Button.Transparent>
          <Button.Primary onClick={this.onSaveImage}>Save</Button.Primary>
        </div>
      </div>
    );
  }
}

export const ImageCropperModal = ({ onClose }) => {
  return (
    <CreatorModal class="ImageCropper" onClose={onClose}>
      <div class="modal-title">Upload Image</div>
      <div class="modal-description">Add display image for the Price item</div>

      <ImageCropper onClose={onClose} />
    </CreatorModal>
  );
};
