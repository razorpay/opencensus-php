import { Component } from 'react';
import { connect } from 'react-redux';

import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import fileDownload from 'common/utils/file-download';
import ajax from 'merchant/utils/ajax';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

class ViewCredentials extends Component {
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
      .then((data) => {
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
      <div className="Partner-Dashboard__View-Credentials">
        <ModalHeader title={`Download ${mode} token`} onCloseClick={this.props.closeModal} />

        <div className="modal-body">
          {/* client ID */}
          <div className="form-group">
            <label>Client ID</label>
            <InputField value={credentials.id} disabled className="form-control" />
          </div>

          {/* client Secret */}
          <div className="form-group toggle-password">
            <label>Client Secret</label>
            <InputField
              value={credentials.secret}
              type={this.state.showClientSecret ? 'text' : 'password'}
              disabled
              className="form-control"
            />
            <button className="btn-link btn-show-secret" onClick={this.toggleSecretView}>
              {this.state.showClientSecret ? 'Hide' : 'Show'}
            </button>
          </div>

          <div className="Modal__Actions clearfix">
            <button className="btn btn-primary btn-block" onClick={this.handleDownloadToken}>
              Download Token
            </button>
          </div>
        </div>
      </div>
    );
  }
}

export default connect(null, { closeModal, showNotification })(ViewCredentials);
