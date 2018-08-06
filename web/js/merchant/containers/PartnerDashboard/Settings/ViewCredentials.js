import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';

import { closeModal } from 'rzp/modules/modals';

import InputField from 'rzp/ui/Forms/InputField';

import ModalHeader from 'rzp/ui/ModalHeader';

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
    const { mode } = this.props;
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
            <InputField
              value="rzp_live_cN6iQyowfqwWF4"
              disabled
              class="form-control"
            />
          </div>

          {/* client Secret */}
          <div class="form-group toggle-password">
            <label>Client Secret</label>
            <InputField
              value="rzp_live_cN6iQyowfqwWF4"
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
              href={`/keys/csv/?id=rzp_live_cN6iQyowfqwWF4&secret=rzp_live_cN6iQyowfqwWF4`}
            >
              Download Token
            </a>
          </div>
        </div>
      </div>
    );
  }
}
