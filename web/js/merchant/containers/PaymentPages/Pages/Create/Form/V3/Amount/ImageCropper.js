import React from 'react';
import { connect } from 'react-redux';
import Button from 'component/Button';
import CreatorModal from '../CreatorModal';
import Croppie from 'croppie';
import { uploadImageInDescription } from '../../../../model';

import { showNotification } from 'rzp/modules/notifications';

const THUMBNAIL_SIZE_LIMIT = 500; // 500KB limit

@connect(state => ({}), { showNotification })
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

  handleImageUpload(blob) {
    const fileSizeMB = blob.size / 1024;

    if (fileSizeMB > THUMBNAIL_SIZE_LIMIT) {
      this.props.showNotification({
        type: 'error',
        message: `Image too large. Max limit ${THUMBNAIL_SIZE_LIMIT}MB`,
      });

      return;
    }

    const isImageType = /^image\//.test(blob.type);

    if (isImageType) {
      this.props.showNotification({
        type: 'success',
        message: 'Uploading image...',
        closeTimeout: 2500,
      });

      uploadImageInDescription(blob)
        .then(res => {
          if (res && res.success) {
            const url = res.data[0];

            // URL
          } else {
            throw { errors: ['Some network error occurred'] };
          }
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors[0],
          });
        });
    } else {
      this.props.showNotification({
        type: 'error',
        message: 'Select a valid Image',
      });

      return;
    }
  }

  onSaveImage = () => {
    const self = this;

    this.vanilla.result('blob').then(function(blob) {
      self.handleImageUpload(blob);
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
      <div class="modal-description">Add thumbnail image for the item</div>

      <ImageCropper onClose={onClose} />
    </CreatorModal>
  );
};
