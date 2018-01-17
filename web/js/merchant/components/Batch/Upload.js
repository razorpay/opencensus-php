import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';
import { titleCase } from 'rzp/utils/rzp-utils';
import ProceedFormFields from 'merchant/containers/PaymentLinks/ProceedModal';
import * as ModalActions from 'rzp/modules/modals';

@connect(state => state.session, ModalActions)
export default class BatchUpload extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    file: null,
  };

  handleChange = event => {
    this.setState({ file: event.target.files[0] });
  };

  save = () => {
    this.context.confirm({
      message: 'Please ensure the amounts in your file are in paise.',
      affirmativeLabel: 'Submit',
      affirmativePendingLabel: 'Submitting...',
      action: () =>
        this.props
          .uploadBatch(this.state.file, this.props.mode)
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: 'Successful',
            });
            this.props.history.push(this.props.closeUrl);
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          }),
    });
  };

  proceed = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <ProceedFormFields
          closeUrl={this.props.closeUrl}
          submitUploadBatch={this.props.uploadBatch.bind(
            null,
            this.state.file,
            this.props.mode
          )}
        />
      ),
    });
  };

  render() {
    return (
      <div class="content-wrapper content-sm upload-container">
        <div class="panel panel-default">
          <div class="panel-heading">
            {titleCase(this.props.title)} File Upload -{' '}
            {this.props.modeFormatted} Mode
            <small class="pull-right">
              <a href={this.props.docUrl} target="_blank">
                DOCUMENTATION &nbsp;
                <i class="icon icon-new-tab-link" />
              </a>
            </small>
          </div>

          <div class="panel-body">
            <div class="help-block">
              This is a simple way to process <b>{this.props.title}</b> in
              batches. For a quick reference,{' '}
              <a class="highlight" href={this.props.sampleUrl} target="_blank">
                click here
              </a>{' '}
              to download a sample file.
            </div>

            <div class="form-group">
              <div class="row">
                <div class="col-md-12">
                  <FileUploadInputButton onChange={this.handleChange} />
                </div>
              </div>
            </div>

            <div class="text-center">
              {this.props.isProceedDialogType ? (
                <button
                  class="btn btn-primary"
                  onClick={this.proceed}
                  disabled={!this.state.file}
                >
                  Proceed
                </button>
              ) : (
                <AsyncButton
                  class="btn btn-primary"
                  type="button"
                  text="Submit"
                  pendingText="Submitting..."
                  disabled={!this.state.file}
                  onClick={this.save}
                />
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}
