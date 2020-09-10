import { Component } from 'react';
import { connect } from 'react-redux';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import ajax from 'merchant/utils/ajax';
import fileDownload from 'common/utils/file-download';

@connect(null, { closeModal, showNotification })
export default class ViewCredentials extends Component {
  state = {
    showClientSecret: false,
  };

  toggleSecretView = () => {
    this.setState({
      showClientSecret: !this.state.showClientSecret,
    });
  };

  handleDownloadToken = () => {
    const { credentials } = this.props;

    return ajax({
      url: '/keys/csv',
      method: 'post',
      appendModeInQueryParam: true,
      data: {
        id: credentials.id,
        secret: credentials.secret,
      },
    })
      .then(data => {
        fileDownload(data, 'rzp.csv');
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  render() {
    const { mode, credentials } = this.props;

    return (
      <div class="Partner-Dashboard__View-Credentials">
        <ModalHeader
          title={`Download ${mode} token`}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          {/* client ID */}
          <div class="form-group">
            <label>Client ID</label>
            <InputField value={credentials.id} disabled class="form-control" />
          </div>

          {/* client Secret */}
          <div class="form-group toggle-password">
            <label>Client Secret</label>
            <InputField
              value={credentials.secret}
              type={this.state.showClientSecret ? 'text' : 'password'}
              disabled
              class="form-control"
            />
            <button
              class="btn-link btn-show-secret"
              onClick={this.toggleSecretView}
            >
              {this.state.showClientSecret ? 'Hide' : 'Show'}
            </button>
          </div>

          <div class="Modal__Actions clearfix">
            <button
              class="btn btn-primary btn-block"
              onClick={this.handleDownloadToken}
            >
              Download Token
            </button>
          </div>
        </div>
      </div>
    );
  }
}
