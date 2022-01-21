import React from 'react';

import { connect } from 'react-redux';
import Croppie from 'croppie';
import RTracking from 'react-tracking';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import ModalHeader from 'common/ui/ModalHeader';
import Input, { Label, Description } from 'common/new-ui/Input';
import FileUpload from 'merchant/components/File/Upload';
import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import track from '../../Wysiwyg/track';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { upload80gSignatoryImage, set80gMerchantDetails } from 'merchant/reducers/profile';
import UploadImage from '../../../../../../../css/assets/payment_pages/upload.svg';

const THUMBNAIL_SIZE_LIMIT = 500 * 1024; // 500 KB limit

@connect(null, { closeModal, openModal, showNotification })
@RTracking(() => window.rzpQ.component('Merchant80gDetails'))
export default class Merchant80gDetails extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      text_80g_12a: props.data.text_80g_12a || '',
      signatoryImageFile: null,
      signatoryImageFileUrl: props.data.image_url_80g,
    };
  }

  componentDidMount() {
    track.modal80G.open();
  }

  componentWillUnmount() {
    track.modal80G.close();
  }

  onSubmit = (formData) => {
    track.modal80G.save();

    const reqPayload = {
      text_80g_12a: formData.text_80g_12a || '',
      image_url_80g: this.state.signatoryImageFileUrl || '',
    };

    this.setState({
      isSaving: true,
    });

    set80gMerchantDetails(reqPayload)
      .then((res) => {
        this.setState({
          isSaving: false,
        });

        this.props.set80gDetails({
          text_80g_12a: reqPayload.text_80g_12a,
          image_url_80g: reqPayload.image_url_80g,
        });

        if (res && res.success) {
          this.props.showNotification({
            type: 'success',
            message: '80G details are updated ',
            closeTimeout: 2500,
          });

          this.props.closeModal();
        }
      })
      .catch(({ errors }) => {
        this.setState({
          isSaving: false,
        });

        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  handleOnFileUpload = (fileUrl) => {
    this.setState({
      signatoryImageFileUrl: fileUrl,
    });
  };

  onBiggerFileSize = (_) => {
    this.props.showNotification({
      type: 'error',
      message: `Image too large. Max limit ${THUMBNAIL_SIZE_LIMIT / 1024}KB`,
    });
  };

  addFile = (file) => {
    const self = this;

    if (file) {
      const reader = new FileReader();
      reader.onload = function onload(e) {
        self.setState({
          signatoryImageFile: {
            name: file.name,
            file: e.target.result,
          },
        });
      };

      reader.readAsDataURL(file);

      track.modal80G.uploadStart();
    }
  };

  removeSignatoryImage = () => {
    this.setState({
      signatoryImageFile: null,
      signatoryImageFileUrl: null,
    });

    track.modal80G.removeSignature();
  };

  // This allows to re-upload the file
  closeImageCropperModal = () => {
    this.setState({
      signatoryImageFile: null,
    });
  };

  onSave = () => {
    track.modal80G.uploadSave();
  };

  handle80gTextChange = (e) => {
    this.setState({ text_80g_12a: e.target.value });
  };

  onFileUploadError = (message) => {
    this.props.trackFn('80g_upload_fail', {
      error: message,
    });
  };

  render() {
    return (
      <div className="details-modal-80g">
        <ModalHeader title="80G Details" />

        <div class="modal-body">
          <Form onSubmit={this.onSubmit} onChange={this.onChange}>
            <Input.Textarea
              name="text_80g_12a"
              label={
                <div className="details-modal-80g--label">
                  80G Description
                  <a
                    href="https://razorpay.com/docs/payment-pages/receipt-80g/#pdf-receipt-to-customers"
                    target="_blank"
                    rel="noreferrer"
                  >
                    Sample 80G Receipt
                    <i class="i i-external-link" />
                  </a>
                </div>
              }
              class="Input-description Input--vTop"
              placeholder="All donations made to us are eligible for tax exemption under 80G of IT act ITBA/EXM/S80G/2019-20/1XXXXXXX Dated DD/MM/YYYY.."
              defaultValue={this.state.text_80g_12a}
              value={this.state.text_80g_12a}
              onChange={this.handle80gTextChange}
              validator={validate80gDescription}
              autoFocus
            />

            <br />

            <div class="Input--FileUpload">
              <Label
                text={() => (
                  <div>
                    <b class="m-r">Signature of Authorised Person</b>(Optional)
                  </div>
                )}
              />
              <FileUpload
                accept={['png', 'jpg', 'jpeg']}
                size="large"
                uploadedFileName="Upload Image here"
                maxSize={THUMBNAIL_SIZE_LIMIT} // In bytes
                onBiggerFileSize={this.onBiggerFileSize}
                onFileChange={this.addFile}
                onCloseClick={this.removeSignatoryImage}
                defaultValue={this.state.signatoryImageFileUrl || null}
                files={[]} // Way to control file in Upload component since we're showing img preview
                imgFilePreviewUrl={this.state.signatoryImageFileUrl || null}
                removeFileButtonLabel="Remove Signature"
                showFileSize={false}
                onSave={this.onSave}
                onError={this.onFileUploadError}
              >
                <div className="Dropzone-80g-details">
                  Drag file here or{' '}
                  <span>
                    <img src={UploadImage} /> Upload
                  </span>
                </div>
              </FileUpload>
              <Description text="Upload .png, .jpg or .jpeg file | 500 KB Max" />
            </div>

            <div class="Modal__actions">
              <Button.Transparent
                class="Button--Link"
                type="submit"
                disabled={this.state.isSaving}
                onClick={this.props.closeModal}
              >
                Cancel
              </Button.Transparent>
              <Button.Primary
                class="btn-block"
                type="submit"
                disabled={this.state.text_80g_12a.length === 0 || this.state.isSaving}
              >
                {this.state.isSaving ? 'Saving 80G details' : 'Save 80G details'}
              </Button.Primary>
            </div>
          </Form>
        </div>
        {this.state.signatoryImageFile && !this.state.signatoryImageFileUrl && (
          <ImageCropperModal
            signatoryImageFile={this.state.signatoryImageFile}
            closeModal={this.closeImageCropperModal}
            onUpload={this.handleOnFileUpload}
          />
        )}
      </div>
    );
  }
}

@connect(null, { showNotification })
class ImageCropperModal extends React.Component {
  componentDidMount() {
    this.initImgCropper(this.props.signatoryImageFile.file);
  }

  initImgCropper(img) {
    const {
      viewPort = { width: 180, height: 120, type: 'square' }, // Taking 3:2 aspect ratio
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
    const { name: fileName } = this.props.signatoryImageFile;

    // API takes file instead of blob, hence saving explicitly;
    const file = new File([blob], fileName, {
      type: blob.type,
    });

    const isImageType = /^image\//.test(file.type);

    if (isImageType) {
      this.props.showNotification({
        type: 'success',
        message: 'Uploading image...',
        closeTimeout: 2500,
      });

      upload80gSignatoryImage(file)
        .then((res) => {
          if (res && res.success) {
            const url = res.data[0];

            this.props.onUpload(url);
            this.props.closeModal();
          } else {
            throw new Error({ errors: ['Some network error occurred'] });
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
    }
  }

  onSaveImage = () => {
    const self = this;

    this.vanilla.result('blob').then(function cb(blob) {
      self.handleImageUpload(blob);
    });
  };

  setRef = (el) => (this.cropperAreaEl = el);

  render() {
    return (
      <ModalMask maskClosable={false} class="merchant-80g-details">
        <Modal onClose={this.props.closeModal} class="animate-appear ImageCropper" showCloseBtn>
          <div class="modal-title">Adjust Image</div>
          <div class="modal-description">You can resize, resposition or crop your image here</div>

          <div class="Input-ImageCropper">
            <div class="Cropper-area Cropper-area--enabled" ref={this.setRef} />

            <div class="btn-group pull-right">
              <Button.Transparent onClick={this.props.closeModal}>Cancel</Button.Transparent>

              <Button.Primary onClick={this.onSaveImage}>Save</Button.Primary>
            </div>
          </div>
        </Modal>
      </ModalMask>
    );
  }
}

function validate80gDescription(val) {
  if (val && val.length > 128) {
    return 'Field description cannot be more than 128 characters';
  }
  return '';
}
