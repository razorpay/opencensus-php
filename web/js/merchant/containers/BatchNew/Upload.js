import { Component } from 'react';
import { connect } from 'react-redux';

import BatchUploadModal from 'merchant/components/BatchNew/UploadModal';

import { validatePaymentLinkBatch as validateBatch } from 'merchant/modules/batches';

@connect(state => state.session, { validateBatch })
export default class BatchUpload extends Component {
  state = {
    shouldLoadMore: false,
    notification: null,
    //TODO: remove after file component is added
    file: null,
  };

  //TODO: remove after file component is added
  handleFileChange = e => {
    this.setState({ file: e.target.files[0] }, this.handleBatchValidation);
  };

  handleBatchValidation = () => {
    this.handleNotification('upload');
    this.props
      .validateBatch(this.state.file, this.props.mode)
      .then(response => {
        this.handleNotification('success');
        console.log('Response: ', response);
      })
      .catch(({ errors }) => {
        this.handleNotification('error', errors[0]);
        console.log('Errors: ', errors);
      });
  };

  handleLoadMore = () => {
    this.setState({
      shouldLoadMore: !this.state.loadMore,
    });
  };

  handleNotification = (status = null, error) => {
    const msg = (error ? `${error}. ` : '') + notificationMsgs[status];
    const notification = status ? { status: status, msg: msg } : null;
    this.setState({ notification: notification });
  };

  render() {
    return (
      <BatchUploadModal
        shouldLoadMore={this.state.shouldLoadMore}
        notification={this.state.notification}
        onLoadMore={this.handleLoadMore}
        onFileChange={this.handleFileChange}
        {...this.props}
      />
    );
  }
}

const notificationMsgs = {
  upload:
    'The batch file is being processed. Please wait as this may take some time.',
  success: 'The batch file has been processed successfully.',
  error: 'Please upload the file again.',
  retry: 'Please upload file again.',
};
