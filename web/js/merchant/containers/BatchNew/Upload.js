import { Component } from 'react';
import { connect } from 'react-redux';
import BatchValidate from './Validate';
import BatchCreate from './Create';
import { closeModal } from 'rzp/modules/modals';
/**
 * Container:  Switches between validation or creation of batch.
 */

@connect(null, { closeModal })
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

  handleCreation = batch => {
    this.props.closeModal();

    this.setState({
      batch,
      currentState: 'success',
    });
  };

  render() {
    //TODO: create batch.
    return this.state.currentState === 'validate' ? (
      <BatchValidate onValidation={this.handleValidation} {...this.props} />
    ) : (
      <BatchCreate
        onCreation={this.handleCreation}
        batch={this.state.batch}
        batchType={this.props.batchType}
      />
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
