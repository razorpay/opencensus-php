import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import ValidateModal from 'merchant/components/BatchNew/ValidateModal';
import CreateModal from 'merchant/components/BatchNew/CreateModal';
import SuccessModal from 'merchant/components/BatchNew/SuccessModal';
import { merchantFetch } from 'merchant/utils/ajax';
import Spinner from 'common/ui/Spinner';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';
import { createPaymentsBatch as createBatch } from 'merchant/reducers/batches';
import { showNotification } from 'merchant_common/reducers/notifications';
import { bindActionCreators } from 'redux';

const iframeHost = window.PUBLIC_API_URL;
class BatchUploadContainer extends Component {
  state = {
    fileUploadProgress: 0,
    mode: 'loading',
  };

  onWindowEvent = ({ data: message }) => {
    switch (message.event) {
      case 'load':
        this.setState({ iFrameLoaded: true });
        break;
      case 'upload_init':
        this.setState({
          status: 'process',
          files: [message.data],
        });
        break;
      case 'progress': {
        const fileUploadProgress = message.data.loaded;
        this.setState({ fileUploadProgress });
        break;
      }

      case 'file_uploaded': {
        const { parsed_entries: parsedEntries, file_id: id } = message.data;
        const file = this.state.files[0];
        this.setState({
          mode: 'create',
          files: [{ ...file, id }],
          parsedEntries,
        });
        break;
      }
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

  UNSAFE_componentWillMount() {
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
          //no need to check array's length as this will be clicked only when file is uploaded
          file_id: this.state.files[0].id,
          name,
        })
        .then(() => {
          this.setState({ mode: 'success' });
        })
        .catch((error) => {
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
    // eslint-disable-next-line react/no-this-in-sfc
    const Header = ({ title }) => <ModalHeader title={title} onCloseClick={this.closeModal} />;
    const Loader = () => (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );

    switch (this.state.mode) {
      case 'loading':
        return <Loader />;

      case 'upload':
        return (
          <div className="batch-upload-modal" data-testid="batch-upload-modal">
            {this.state.iFrameLoaded && <Header title="Batch Upload" />}
            <iframe
              src={`${iframeHost}/v1/batches/upload?token=${this.state.ott}`}
              className={`
                batch-payments-iframe
                ${this.state.status === 'process' ? 'disabled' : ''}
                ${this.state.iFrameLoaded ? 'enabled' : ''}
              `}
            />
            {this.state.iFrameLoaded ? (
              <ValidateModal
                maxRows={500}
                iframeHost={iframeHost}
                stagedFileStatus={this.state.status}
                shouldLoadMore
                status={this.state.status}
                notifyMsg={this.state.error}
                docUrl={this.props.docUrl}
                sampleUrl={this.props.sampleUrl}
                {...this.state}
              />
            ) : (
              <Loader />
            )}
          </div>
        );

      case 'create': {
        const initialValues = {
          name: this.state.files[0].name,
        };
        return (
          <div className="batch-upload-modal create">
            <Header title="Batch Upload" />
            <CreateModal
              parsedEntries={this.state.parsedEntries}
              onCreateBatch={this.handleCreateBatch}
              ctaText="Process Payments"
              initialValues={initialValues}
            />
          </div>
        );
      }

      case 'success':
        return (
          <div className="batch-upload-modal success">
            <Header title="" />
            <SuccessModal onModalClose={this.closeModal}>
              <div className="text-center">
                <p>
                  All payments have queued for processing and output file will be available shortly.
                </p>
                <p>
                  You can download the output file to check for processed payments. For the payments
                  that could not be processed due to some issues, please upload a new batch file.
                </p>
              </div>
            </SuccessModal>
          </div>
        );

      default:
        return null;
    }
  }
}

export default withRouter(
  connect(null, (dispatch) =>
    bindActionCreators({ closeModal, createBatch, showNotification }, dispatch),
  )(BatchUploadContainer),
);
