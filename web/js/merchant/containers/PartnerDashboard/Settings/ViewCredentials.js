import { Component } from 'react';
import { connect } from 'react-redux';

import { closeModal } from 'merchant_common/reducers/modals';

import InputField from 'common/ui/Forms/InputField';

import ModalHeader from 'common/ui/ModalHeader';

@connect(null, { closeModal })
export default class ViewCredentials extends Component {
  state = {
    showClientSecret: false,
  };

  toggleSecretView = () => {
    this.setState({
      showClientSecret: !this.state.showClientSecret,
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
            <a
              class="btn btn-primary btn-block"
              href={`/keys/csv/?id=${credentials.id}&secret=${
                credentials.secret
              }`}
            >
              Download Token
            </a>
          </div>
        </div>
      </div>
    );
  }
}
