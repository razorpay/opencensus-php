import { Component } from 'react';
import { connect } from 'react-redux';
import BatchValidateModal from 'merchant/components/BatchNew/ValidateModal';

/**
 * Notification Map: change nofitication msg based on current validation state.
 */
const notificationMsgs = {
  process: 'The batch file is being processed. Please wait as this may take some time.',
  success: 'The batch file has been processed successfully.',
  error: 'Please correct them and upload the file again',
  exceed: 'The file size exceeds the maximum size limit. Please upload a smaller file.',
};

class BatchValidate extends Component {
  state = {
    status: null,
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
    if (newState.notifyMsg && newState.notifyMsg.indexOf('Server error response') > -1) {
      newState.notifyMsg =
        'There was an error while processing the file. Please try again after some time.';
    }
    newState.fileUrl = fileUrl;
    this.setState(newState);
  };

  handleBatchValidation = (file, progressTracker) => {
    this.changeBatchState('process');

    let secondsSinceStart = 0;
    const t = setInterval(() => {
      secondsSinceStart++;
    }, 1000);

    return this.props
      .validateBatch(file, progressTracker)
      .then((response) => {
        clearInterval(t);
        if (response.data.error_count) {
          this.changeBatchState(
            'error',
            'Some fields have invalid entries',
            response.data.signed_url,
          );
          this.props.gaEvents.trackUploadBatchFile(
            'error',
            'Some fields have invalid entries',
            secondsSinceStart,
          );
        } else {
          this.changeBatchState('success');
          this.props.gaEvents.trackUploadBatchFile('success', undefined, secondsSinceStart);
          this.props.onValidation(response.data, file.name.replace(/\.[^/.]+$/, ''));
        }
        return response;
      })
      .catch((error) => {
        this.changeBatchState('error', error.errors[0]);
        if (this.props.onValidationFail) this.props.onValidationFail(error.errors[0]);
        clearInterval(t);
        this.props.gaEvents.trackUploadBatchFile('error', error.errors[0], secondsSinceStart);
        return error;
      });
  };

  handleBiggerFileSize = () => {
    this.changeBatchState('exceed');
  };

  handleErrorReportDownload = () => {
    this.props.gaEvents.trackDownloadErrorReport();
  };

  handleCloseClick = () => {
    if (this.props.onFileRemove) this.props.onFileRemove();
    this.changeBatchState();
  };

  onSampleFileDownload = () => {
    this.props.gaEvents.trackSampleFileDownload('From New Modal');
    if (this.props.sampleFileDownloadAnalytics) {
      this.props.sampleFileDownloadAnalytics();
    }
  };

  onClickUpload = () => {
    if (this.props.clickToUploadAnalytics) {
      this.props.clickToUploadAnalytics();
    }
  };

  render() {
    return (
      <BatchValidateModal
        onLoadMore={this.handleLoadMore}
        onFileChange={this.handleBatchValidation}
        onBiggerFileSize={this.handleBiggerFileSize}
        onCloseClick={this.handleCloseClick}
        onSampleFileDownload={this.onSampleFileDownload}
        onErrorReportDownload={this.handleErrorReportDownload}
        onClickUpload={this.onClickUpload}
        {...this.state}
        {...this.props}
      />
    );
  }
}

export default connect((state) => state.session, null)(BatchValidate);
