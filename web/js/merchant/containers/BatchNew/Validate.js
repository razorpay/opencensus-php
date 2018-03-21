import { Component } from 'react';
import { connect } from 'react-redux';

import BatchValidateModal from 'merchant/components/BatchNew/ValidateModal';

import { validatePaymentLinkBatch as validateBatch } from 'merchant/modules/batches';

import { trackUploadBatchFile } from './ga';
@connect(state => state.session, { validateBatch })
export default class BatchValidate extends Component {
  state = {
    status: null,
    shouldLoadMore: false,
    notifyMsg: null,
    fileUrl: null,
    stagedFileStatus: null,
  };

  handleStateChange = (status = null, errorMsg = null, fileUrl = null) => {
    const newState = {};
    newState.status = status;
    newState.stagedFileStatus = status;
    newState.notifyMsg = status
      ? (errorMsg ? `${errorMsg}. ` : '') + notificationMsgs[status]
      : null;
    newState.fileUrl = fileUrl;
    this.setState(newState);
  };

  handleBatchValidation = file => {
    this.handleStateChange('process');
    setTimeout(() => {
      trackUploadBatchFile();
      this.props
        .validateBatch(file, this.props.mode)
        .then(response => {
          this.handleStateChange('success');
          setTimeout(() => {
            this.props.onValidation(
              response.data,
              file.name.replace(/\.[^/.]+$/, '')
            );
          }, 1000);
        })
        .catch(error => {
          // Pass file URL if the user made a mistake in certain fields of batch file
          const fileUrl = error.fileUrl || null;

          this.handleStateChange('error', error.errors[0], fileUrl);
        });
    }, 1000);
  };

  handleLoadMore = () => {
    this.setState({
      shouldLoadMore: !this.state.loadMore,
    });
  };

  handleBiggerFileSize = () => {
    this.handleStateChange('exceed');
  };

  render() {
    return (
      <BatchValidateModal
        onLoadMore={this.handleLoadMore}
        onFileChange={this.handleBatchValidation}
        onBiggerFileSize={this.handleBiggerFileSize}
        onCloseClick={this.handleStateChange}
        {...this.state}
        {...this.props}
      />
    );
  }
}

/**
 * Notification Map: change nofitication msg based on current validation state.
 */
const notificationMsgs = {
  process:
    'The batch file is being processed. Please wait as this may take some time.',
  success: 'The batch file has been processed successfully.',
  error: 'Please upload the file again.',
  exceed: 'The file size exceeds the 1MB limit. Please upload a smaller file.',
};
