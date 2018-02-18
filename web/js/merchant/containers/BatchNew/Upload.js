import { Component } from 'react';
import { connect } from 'react-redux';
import BatchValidate from './Validate';
import BatchCreate from './Create';
import { closeModal, openModal } from 'rzp/modules/modals';
/**
 * Container:  Switches between validation or creation of batch.
 */

@connect(null, { closeModal, openModal })
export default class BatchUpload extends Component {
  state = {
    currentState: 'validate',
    batch: null,
  };

  handleSuccess = () => {};

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
      <BatchValidate
        onValidation={this.handleValidation}
        batchType={this.props.batchType}
        sampleUrl={this.props.sampleUrl}
        docUrl={this.props.docUrl}
      />
    ) : (
      <BatchCreate
        onCreation={this.handleCreation}
        batch={this.state.batch}
        batchType={this.props.batchType}
      />
    );
  }
}
