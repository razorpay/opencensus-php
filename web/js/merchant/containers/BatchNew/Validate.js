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
  };

  handleStateChange = (status = null, errorMsg = null, fileUrl = null) => {
    const newState = {};

    newState.status = status;
    newState.notifyMsg =
      (errorMsg ? `${errorMsg} ` : '') + notificationMsgs[status];
    newState.fileUrl = fileUrl;

    this.setState(newState);
  };

  //TODO: remove after file component is added
  handleFileChange = e => {
    this.setState({ file: e.target.files[0] }, this.handleBatchValidation);
  };

  handleBatchValidation = () => {
    this.handleStateChange('process');
    this.props
      .validateBatch(this.state.file, this.props.mode)
      .then(response => {
        this.handleStateChange('success');
        this.props.onValidation(response.data);
      })
      .catch(error => {
        // Pass file URL if the user made a mistake in certain fields of batch file
        const fileUrl = error.fileUrl || null;

        this.handleStateChange('error', error.errors[0], fileUrl);
        console.log('Errors: ', error);
      });
  };

  handleLoadMore = () => {
    this.setState({
      shouldLoadMore: !this.state.loadMore,
    });
  };

  render() {
    return (
      <BatchValidateModal
        onLoadMore={this.handleLoadMore}
        onFileChange={this.handleFileChange}
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
};
