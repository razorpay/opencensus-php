import { Component } from 'react';
import { connect } from 'react-redux';

import BatchValidateModal from 'merchant/components/BatchNew/ValidateModal';

import { validatePaymentLinkBatch as validateBatch } from 'merchant/modules/batches';

import {
  trackUploadBatchFile,
  trackSampleFileDownload,
  trackDownloadErrorReport,
} from './ga';
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

  handleBatchValidation = file => {
    this.handleStateChange('process');
    setTimeout(() => {
      trackUploadBatchFile();
      this.props
        .validateBatch(file, this.props.mode)
        .then(response => {
          if (response.data.error_count) {
            this.handleStateChange(
              'error',
              'Some fields have invalid entries',
              response.data.signed_url
            );
          } else {
            this.handleStateChange('success');
            setTimeout(() => {
              this.props.onValidation(
                response.data,
                file.name.replace(/\.[^/.]+$/, '')
              );
            }, 1000);
          }
        })
        .catch(error => {
          this.handleStateChange('error', error.errors[0]);
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

  handleSampleFileDownload = () => {
    trackSampleFileDownload();
  };

  handleErrorReportDownload = () => {
    trackDownloadErrorReport();
  };

  render() {
    return (
      <BatchValidateModal
        onLoadMore={this.handleLoadMore}
        onFileChange={this.handleBatchValidation}
        onBiggerFileSize={this.handleBiggerFileSize}
        onCloseClick={this.handleStateChange}
        onSampleFileDownload={this.handleSampleFileDownload}
        onErrorReportDownload={this.handleErrorReportDownload}
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
