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

const REMOVE_EXTENSION_REGEX = /\.[^/.]+$/;

class BatchValidate extends Component {
  state = {
    status: null,
    notifyMsg: null,
    fileUrl: null,
    stagedFileStatus: null,
  };

  getMsg = (status) => {
    const { displayMsgs } = this.props;
    return displayMsgs ? displayMsgs[status] : notificationMsgs[status];
  };

  getErrorMsg = (errorMsg, status) => {
    if (!errorMsg) {
      return this.getMsg(status);
    }

    const { generateCustomErrorMessage } = this.props;
    let customErrorMsg = errorMsg;
    if (generateCustomErrorMessage) {
      customErrorMsg = generateCustomErrorMessage(errorMsg) || errorMsg;
    }
    return `${customErrorMsg}. ${this.getMsg(status)}`;
  };

  changeBatchState = (status = null, errorMsg = null, fileUrl = null) => {
    const newState = {
      status,
      stagedFileStatus: status,
      fileUrl,
    };
    newState.notifyMsg = status ? this.getErrorMsg(errorMsg, status) : null;
    // handle server error 500 message
    if (newState.notifyMsg && newState.notifyMsg.indexOf('Server error response') > -1) {
      newState.notifyMsg =
        'There was an error while processing the file. Please try again after some time.';
    }
    this.setState(newState);
  };

  handleBatchValidation = (file, progressTracker) => {
    this.changeBatchState('process');

    let secondsSinceStart = 0;
    const t = setInterval(() => {
      secondsSinceStart++;
    }, 1000);

    const { validateBatch, gaEvents, processFile, onValidation, onValidationFail } = this.props;
    const status = processFile ? 'validate' : 'create';

    return validateBatch(file, progressTracker)
      .then((response) => {
        clearInterval(t);
        if (response.data.error_count) {
          this.changeBatchState(
            'error',
            'Some fields have invalid entries',
            response.data.signed_url,
          );
          gaEvents?.trackUploadBatchFile(
            'error',
            'Some fields have invalid entries',
            secondsSinceStart,
          );
        } else {
          this.changeBatchState('success');
          gaEvents?.trackUploadBatchFile('success', undefined, secondsSinceStart);
          onValidation(response.data, file.name.replace(REMOVE_EXTENSION_REGEX, ''), status);
        }
        return response;
      })
      .catch((error) => {
        const errorMsg = error.errors[0] ?? '';
        this.changeBatchState('error', errorMsg);
        if (onValidationFail) onValidationFail(errorMsg);
        clearInterval(t);
        gaEvents?.trackUploadBatchFile('error', errorMsg, secondsSinceStart);
        return error;
      });
  };

  handleBiggerFileSize = () => {
    this.changeBatchState('exceed');
  };

  handleErrorReportDownload = () => {
    this.props.gaEvents?.trackDownloadErrorReport();
  };

  handleCloseClick = () => {
    if (this.props.onFileRemove) this.props.onFileRemove();
    this.changeBatchState();
  };

  onSampleFileDownload = () => {
    const { gaEvents, sampleFileDownloadAnalytics } = this.props;
    gaEvents?.trackSampleFileDownload('From New Modal');
    if (sampleFileDownloadAnalytics) {
      sampleFileDownloadAnalytics();
    }
  };

  onClickUpload = () => {
    if (this.props.clickToUploadAnalytics) {
      this.props.clickToUploadAnalytics();
    }
  };

  render() {
    const { component, modalActions } = this.props;
    const { status } = this.state;
    return (
      <>
        <div className="validate-body">
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
          {!status && component}
        </div>
        {modalActions ?? null}
      </>
    );
  }
}

export default connect((state) => state.session, null)(BatchValidate);
