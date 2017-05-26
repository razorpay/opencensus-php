import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import AsyncButton from 'react-async-button';
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';
import { showNotification } from 'rzp/modules/notifications';
import { uploadBatchRefunds } from 'merchant/modules/refunds/batchuploads';

@withRouter
@connect(state => state.session, { uploadBatchRefunds, showNotification })
export default class BatchUpload extends Component {
  static contextTypes = {
    ngRouter: PropTypes.object,
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
          .uploadBatchRefunds(this.state.file)
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: 'Successful',
            });
            this.props.history.push('/app/refunds/batchuploads');
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          }),
    });
  };

  render() {
    return (
      <div class="content-wrapper content-sm">
        <div class="panel panel-default">
          <div class="panel-heading">
            Refunds File Upload - {this.props.modeFormatted} Mode

            <small class="pull-right">
              <a
                href="https://docs.razorpay.com/v1/page/batch-refunds"
                target="_blank"
              >
                DOCUMENTATION &nbsp;
                <i class="icon icon-new-tab-link" />
              </a>
            </small>
          </div>

          <div class="panel-body">
            <form>
              <div class="help-block">
                This is a simple way to process
                {' '}
                <b>refunds</b>
                {' '}
                in batches. For a quick reference,
                {' '}
                <a
                  class="highlight"
                  href="https://dashboard.razorpay.com/files/sample_batch_refund.xlsx"
                  target="_blank"
                >
                  click here
                </a>
                {' '}
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
                <AsyncButton
                  class="btn btn-primary"
                  type="button"
                  text="Submit"
                  pendingText="Submitting..."
                  disabled={!this.state.file}
                  onClick={this.save}
                />
              </div>
            </form>
          </div>
        </div>
      </div>
    );
  }
}
