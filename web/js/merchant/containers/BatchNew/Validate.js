import { Component } from 'react';
import { connect } from 'react-redux';

import BatchValidateModal from 'merchant/components/BatchNew/ValidateModal';

import { validatePaymentLinkBatch as validateBatch } from 'merchant/modules/batches';

@connect(state => state.session, { validateBatch })
export default class BatchValidate extends Component {
  state = {
    status: null,
    shouldLoadMore: false,
    notifyMsg: null,
    fileUrl: null,
    stagedFileStatus: null,
  };

  changeBatchState = (status = null, errorMsg = null, fileUrl = null) => {
    const newState = {};
    newState.status = status;
    newState.stagedFileStatus = status;
    newState.notifyMsg = status
      ? (errorMsg ? `${errorMsg}. ` : '') + notificationMsgs[status]
      : null;
    // handle server error 500 message
    if (
      newState.notifyMsg &&
      newState.notifyMsg.indexOf('Server error response') > -1
    ) {
      newState.notifyMsg =
        'There was an error while processing the file. Please try again after some time.';
    }
    newState.fileUrl = fileUrl;
    this.setState(newState);
  };

  handleBatchValidation = (file, progressTracker) => {
    this.changeBatchState('process');
    this.props.gaEvents.trackUploadBatchFile();
    return this.props
      .validateBatch(file, progressTracker)
      .then(response => {
        if (response.data.error_count) {
          this.changeBatchState(
            'error',
            'Some fields have invalid entries',
            response.data.signed_url
          );
        } else {
          this.changeBatchState('success');
          this.props.onValidation(
            response.data,
            file.name.replace(/\.[^/.]+$/, '')
          );
        }
        return response;
      })
      .catch(error => {
        this.changeBatchState('error', error.errors[0]);

        return error;
      });
  };

  handleLoadMore = () => {
    this.setState({
      shouldLoadMore: !this.state.loadMore,
    });
  };

  handleBiggerFileSize = () => {
    this.changeBatchState('exceed');
  };

  handleErrorReportDownload = () => {
    this.props.gaEvents.trackDownloadErrorReport();
  };

  render() {
    return (
      <BatchValidateModal
        onLoadMore={this.handleLoadMore}
        onFileChange={this.handleBatchValidation}
        onBiggerFileSize={this.handleBiggerFileSize}
        onCloseClick={this.changeBatchState}
        onSampleFileDownload={this.props.gaEvents.trackSampleFileDownload('From New Modal')}
        onErrorReportDownload={this.handleErrorReportDownload}
        maxRows={5000}
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
  error: 'Please correct them and upload the file again',
  exceed: 'The file size exceeds the 1MB limit. Please upload a smaller file.',
};
