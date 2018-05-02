import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import ValidateModal from 'merchant/components/BatchNew/ValidateModal';
import CreateModal from 'merchant/components/BatchNew/CreateModal';
import SuccessModal from 'merchant/components/BatchNew/SuccessModal';
import { merchantFetch } from 'rzp/utils/ajax';
import Spinner from 'rzp/ui/Spinner';
import TableSlider from 'rzp/ui/TableSlider';
import ModalHeader from 'rzp/ui/ModalHeader';
import { closeModal } from 'rzp/modules/modals';
import { createPaymentsBatch as createBatch } from 'merchant/modules/batches';
import { showNotification } from 'rzp/modules/notifications';

const hostToIframeHost = {
  'dashboard.razorpay.in': 'http://api.razorpay.in',
  'dashboard.razorpay.com': 'https://api.razorpay.com',
  'beta-dashboard.razorpay.com': 'https://beta-api.razorpay.com',
  'beta-dashboard.razorpay.in': 'https://beta-api.razorpay.com',
};

const iframeHost = hostToIframeHost[location.hostname];

@withRouter
@connect(null, { closeModal, createBatch, showNotification })
export default class BatchUploadContainer extends Component {
  state = {
    fileUploadProgress: 0,
    mode: 'loading',
  };

  onWindowEvent = ({ data: message }) => {
    if (event.origin !== iframeHost) return;

    switch (message.event) {
      case 'load':
        this.setState({ iFrameLoaded: true });
        break;
      case 'upload_init':
        this.setState({
          status: 'process',
          uploadedFile: { ...message.data },
        });
        break;
      case 'progress':
        const { loaded, total } = message.data;
        const fileUploadProgress = loaded / total * 100;
        this.setState({ fileUploadProgress });
        break;
      case 'file_uploaded':
        const { parsed_entries: parsedEntries, file_id: id } = message.data;
        this.setState({
          mode: 'create',
          uploadedFile: { ...this.state.uploadedFile, id },
          parsedEntries,
        });
        break;
      case 'error':
        this.setState({
          fileUploadProgress: 0,
          error: `${message.data.error.description}. Please upload file again.`,
          status: 'error',
        });
        break;
      default:
        break;
    }
  };

  componentWillMount() {
    merchantFetch({
      url: 'merchant/token',
      method: 'post',
    }).then(({ data }) => {
      this.setState({ mode: 'upload', ott: data.token });
      window.addEventListener('message', this.onWindowEvent);
    });
  }

  componentWillUnmount() {
    window.removeEventListener('message', this.onWindowEvent);
  }

  handleCreateBatch = ({ name }) => {
    this.setState({ mode: 'loading' }, () => {
      this.props
        .createBatch({
          file_id: this.state.uploadedFile.id,
          name,
        })
        .then(() => {
          this.setState({ mode: 'success' });
        })
        .catch(error => {
          this.props.showNotification({
            type: 'error',
            message: error.errors,
          });
          this.closeModal();
        });
    });
  };

  closeModal = () => {
    this.props.closeModal();
    this.props.history.push('/payments/batchuploads');
  };

  render() {
    const Header = () => (
      <ModalHeader title="Batch Upload" onCloseClick={this.closeModal} />
    );
    const Loader = () => (
      <div class="page-spinner-container">
        <Spinner />
      </div>
    );

    switch (this.state.mode) {
      case 'loading':
        return <Loader />;

      case 'upload':
        return (
          <div class="batch-upload-modal">
            {this.state.iFrameLoaded && <Header />}
            <iframe
              src={`${iframeHost}/v1/batches/upload?token=${this.state.ott}`}
              class={`
                batch-payments-iframe
                ${this.state.status === 'process' ? 'disabled' : ''}
                ${this.state.iFrameLoaded ? 'enabled' : ''}
              `}
            />
            {this.state.iFrameLoaded ? (
              <ValidateModal
                batchType="direct_debit"
                iframeHost={iframeHost}
                showUpload={!this.state.uploadedFile}
                showStaged={!!this.state.uploadedFile}
                shouldLoadMore
                status={this.state.status}
                notifyMsg={this.state.error}
                {...this.state}
              />
            ) : (
              <Loader />
            )}
          </div>
        );

      case 'create':
        const initialValues = {
          name: this.state.uploadedFile.name,
        };
        return (
          <div class="batch-upload-modal create">
            <Header />
            <CreateModal
              parsedEntries={this.state.parsedEntries}
              onCreateBatch={this.handleCreateBatch}
              ctaText="Process Payments"
              initialValues={initialValues}
            />
          </div>
        );
      case 'success':
        return (
          <div class="batch-upload-modal success">
            <SuccessModal onModalClose={this.closeModal} />
          </div>
        );
      default:
        return null;
    }
  }
}
