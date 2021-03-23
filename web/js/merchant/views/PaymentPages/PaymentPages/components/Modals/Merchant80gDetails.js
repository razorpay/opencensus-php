import { connect } from 'react-redux';
import Croppie from 'croppie';
import RTracking from 'react-tracking';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import ModalHeader from 'common/ui/ModalHeader';
import Banner from 'common/ui/Banner';
import Input, { Label, Description } from 'common/new-ui/Input';
import FileUpload from 'merchant/components/File/Upload';
import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import Spinner from 'common/ui/Spinner';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  get80gMerchantDetails,
  upload80gSignatoryImage,
  set80gMerchantDetails,
} from 'merchant/reducers/profile';

const THUMBNAIL_SIZE_LIMIT = 500 * 1024; // 500 KB limit

@connect(null, { closeModal, openModal, showNotification })
@RTracking(() => window.rzpQ.component('Merchant80gDetails'))
export default class Merchant80gDetails extends React.Component {
  state = {
    isLoading: true,
    text_80g_12a: '',
    signatoryImageFile: null,
    signatoryImageFileUrl: null,
  };

  componentDidMount() {
    // Fetch 80G details of merchant
    get80gMerchantDetails()
      .then((res) => {
        if (res && res.data) {
          this.setState({
            isLoading: false,
            text80g: res.data.text_80g_12a || '',
            signatoryImageFileUrl: res.data.image_url_80g,
          });

          this.props.get80gDetails({
            text_80g_12a: res.data.text_80g_12a,
          });
        } else {
          throw new Error();
        }
      })
      .catch((err) => {
        this.props.closeModal();

        this.props.showNotification({
          type: 'error',
          message: 'Some network error has occurred',
        });
      });

    this.props.trackFn('80g_details_start');
  }

  componentWillUnmount() {
    this.props.trackFn('80g_details_close');
  }

  onSubmit = (formData) => {
    analyticsTrack({
      objectName: 'receipts 80-G',
      actionName: 'saved',
      screen: 'create payment page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
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

        this.props.get80gDetails({
          text_80g_12a: reqPayload.text_80g_12a,
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
      reader.onload = function (e) {
        self.setState({
          signatoryImageFile: {
            name: file.name,
            file: e.target.result,
          },
        });
      };

      reader.readAsDataURL(file);

      this.props.trackFn('80g_upload_start');
    }
  };

  removeSignatoryImage = () => {
    this.setState({
      signatoryImageFile: null,
      signatoryImageFileUrl: null,
    });

    this.props.trackFn('80g_upload_remove');
  };

  // This allows to re-upload the file
  closeImageCropperModal = () => {
    this.setState({
      signatoryImageFile: null,
    });
  };

  onSave = () => {
    this.props.trackFn('80g_upload_save');
  };

  render() {
    const props = this.props;

    return (
      <div class="80g-details-modal">
        <ModalHeader title="80G Details" onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          <Banner>
            These details are required to issue 80G receipts for Payment Page transactions.
          </Banner>

          {this.state.isLoading ? (
            <div class="page-spinner-container">
              <Spinner />
            </div>
          ) : (
            <Form onSubmit={this.onSubmit} onChange={this.onChange}>
              <Input.Textarea
                name="text_80g_12a"
                label="80G Description"
                class="Input-description Input--vTop"
                description="This 80G description will be shown on receipts"
                placeholder="All donations made to us are eligible for tax exemption under 80G of IT act ITBA/EXM/S80G/2019-20/1XXXXXXX Dated DD/MM/YYYY.."
                defaultValue={this.state.text80g}
                validator={(val) => {
                  if (val && val.length > 128) {
                    return 'Field description cannot be more than 128 characters';
                  }
                }}
                autoFocus
              />

              <br />

              <div class="Input--FileUpload">
                <Label
                  text={() => (
                    <div>
                      <b class="m-r">Signature of Authorised Signatory</b> (Optional)
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
                  onError={(message) => {
                    this.props.trackFn('80g_upload_fail', {
                      error: message,
                    });
                  }}
                />
                <Description text="For best results take the signature on a white paper and then scan it" />
              </div>

              <div class="Modal__actions">
                <Button.Primary class="btn-block" type="submit" disabled={this.state.isSaving}>
                  {this.state.isSaving ? 'Updating..' : 'Update'}
                </Button.Primary>
              </div>
            </Form>
          )}
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

    this.vanilla.result('blob').then(function (blob) {
      self.handleImageUpload.call(self, blob);
    });
  };

  setRef = (el) => (this.cropperAreaEl = el);

  render() {
    const { closeModal } = this.props;

    return (
      <ModalMask maskClosable={false} class="merchant-80g-details">
        <Modal onClose={closeModal} class="animate-appear ImageCropper" showCloseBtn>
          <div class="modal-title">Adjust Image</div>
          <div class="modal-description">You can resize, resposition or crop your image here</div>

          <div class="Input-ImageCropper">
            <div class="Cropper-area Cropper-area--enabled" ref={this.setRef} />

            <div class="btn-group pull-right">
              <Button.Transparent onClick={closeModal}>Cancel</Button.Transparent>

              <Button.Primary onClick={this.onSaveImage}>Save</Button.Primary>
            </div>
          </div>
        </Modal>
      </ModalMask>
    );
  }
}
