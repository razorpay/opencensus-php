import React from 'react';
import { connect } from 'react-redux';
import Croppie from 'croppie';
import Button from 'component/Button';
import CreatorModal from '../CreatorModal';
import FileUpload from 'merchant/components/File/Upload';
import { uploadImageInDescription } from '../../../../model';
import { showNotification } from 'rzp/modules/notifications';
import { classList } from 'common/util';

const THUMBNAIL_SIZE_LIMIT = 500 * 1024; // 500 KB limit

@connect(state => ({}), { showNotification })
export default class ImageCropper extends React.PureComponent {
  state = { showImgCropper: false };

  componentDidMount() {
    if (this.props.imgUrl) {
      this.initImgCropper(this.props.imgUrl);
    }
  }

  initImgCropper(img) {
    this.setState({
      showImgCropper: true,
    });

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
      url: img,
    });
  }

  handleImageUpload(blob) {
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

  addFile = file => {
    const self = this;

    if (file) {
      const reader = new FileReader();

      reader.onload = function(e) {
        self.initImgCropper(e.target.result);
      };

      reader.readAsDataURL(file);
    }
  };

  onBiggerFileSize = _ => {
    this.props.showNotification({
      type: 'error',
      message: `Image too large. Max limit ${THUMBNAIL_SIZE_LIMIT / 1024}KB`,
    });
  };

  setRef = el => (this.cropperAreaEl = el);

  render() {
    const { onClose } = this.props;

    return (
      <div class="Input-ImageCropper">
        <div
          class={classList(
            'Cropper-area',
            this.state.showImgCropper && 'Cropper-area--enabled'
          )}
          ref={this.setRef}
        >
          {!this.state.showImgCropper && (
            <FileUpload
              accept={['png', 'jpg', 'jpeg', 'gif']}
              size="large"
              uploadedFileName="Upload Image here"
              maxSize={THUMBNAIL_SIZE_LIMIT} // In bytes
              onBiggerFileSize={this.onBiggerFileSize}
              onFileChange={this.addFile}
              showFileSize={false}
            />
          )}
        </div>

        <div class="btn-group pull-right">
          <Button.Transparent onClick={onClose}>Cancel</Button.Transparent>

          {this.state.showImgCropper && (
            <Button.Primary onClick={this.onSaveImage}>Save</Button.Primary>
          )}
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
