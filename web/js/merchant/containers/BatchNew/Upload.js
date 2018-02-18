import { Component } from 'react';

import BatchValidate from './Validate';

/**
 * Container:  Switches between validation or creation of batch.
 */
export default class BatchUpload extends Component {
  state = {
    currentState: 'validate',
    batch: null,
  };

  handleValidation = batch => {
    this.setState({
      batch,
      currentState: 'create',
    });
  };

  render() {
    //TODO: create batch.
    return this.state.currentState === 'validate' ? (
      <BatchValidate onValidation={this.handleValidation} {...this.props} />
    ) : (
      <div>Create Batch</div>
    );
  }
}

const stateMsgMap = {
  processing:
    'The batch file is being processed. Please wait as this may take some time.',
  success: 'The batch file has been processed successfully.',
  error: 'Please upload the file again.',
  retry: 'Please upload file again.',
};

const TRANSITION_TIME_OUT = 2000;
