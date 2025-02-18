import React from 'react';
import Croppie from 'croppie';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import { classList } from 'common/utils/rzp-utils';
import FileUpload from 'merchant/components/File/Upload';
import { showNotification } from 'merchant_common/reducers/notifications';

import { uploadImageInDescription } from '../../../model';
import CreatorModal from '../CreatorModal';

const THUMBNAIL_SIZE_LIMIT = 500 * 1024; // 500 KB limit

class ImageCropperComponent extends React.PureComponent {
  state = { showImgCropper: false };
  fileName;

  componentDidMount() {
    if (this.props.imgUrl) {
      window.setTimeout((_) => this.initImgCropper(this.props.imgUrl));
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
    // API takes file instead of blob, hence saving explicitly;
    const file = new File([blob], this.fileName, {
      type: blob.type,
    });

    const isImageType = /^image\//.test(file.type);

    if (isImageType) {
      this.props.showNotification({
        type: 'success',
        message: 'Uploading image...',
        closeTimeout: 2500,
      });

      uploadImageInDescription(file)
        .then((res) => {
          if (res && res.success) {
            const url = res.data[0];

            this.props.onSave(url);
            this.props.closeCropperModal();
          } else {
            throw { errors: ['Some network error occurred'] };
          }
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors[0],
          });

          this.props.onError && this.props.onError(errors[0]);
        });
    } else {
      this.props.showNotification({
        type: 'error',
        message: 'Select a valid Image',
      });

      this.props.onError && this.props.onError('Select a valid Image');
    }
  }

  onSaveImage = () => {
    const self = this;

    this.vanilla.result('blob').then(function (blob) {
      self.handleImageUpload.call(self, blob);
    });
  };

  addFile = (file) => {
    const self = this;

    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        self.fileName = file.name;
        self.initImgCropper(e.target.result);
      };

      reader.readAsDataURL(file);
    }
  };

  onBiggerFileSize = (_) => {
    this.props.showNotification({
      type: 'error',
      message: `Image too large. Max limit ${THUMBNAIL_SIZE_LIMIT / 1024}KB`,
    });
  };

  setRef = (el) => (this.cropperAreaEl = el);

  render() {
    const { closeCropperModal } = this.props;

    return (
      <div className="Input-ImageCropper">
        <div
          className={classList(
            'Cropper-area',
            this.state.showImgCropper && 'Cropper-area--enabled',
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

        <div className="btn-group pull-right">
          <Button.Transparent onClick={closeCropperModal}>Cancel</Button.Transparent>

          {this.state.showImgCropper && (
            <Button.Primary onClick={this.onSaveImage}>Save</Button.Primary>
          )}
        </div>
      </div>
    );
  }
}

const ImageCropper = connect((state) => ({}), { showNotification })(ImageCropperComponent);

export const ImageCropperModal = ({ imgUrl, onSave, closeCropperModal }) => {
  return (
    <CreatorModal className="ImageCropper" onClose={closeCropperModal}>
      <div className="modal-title">Upload Image</div>
      <div className="modal-description">Add thumbnail image for the item</div>

      <ImageCropper imgUrl={imgUrl} onSave={onSave} closeCropperModal={closeCropperModal} />
    </CreatorModal>
  );
};

export default ImageCropper;
