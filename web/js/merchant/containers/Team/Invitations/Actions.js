import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';

export default class InvitationsActions extends Component {
  update = () => {
    // code for update invitation
  };

  cancel = () => {
    // code for cancel invitation
  };

  resend = () => {
    // code for resend invitation
  };

  render() {
    return (
      <>
        <AsyncButton
          class="btn btn-primary m-r"
          onClick={this.resend}
          text="Resend"
          pendingText="Resending..."
        />
        <button className="btn btn-primary m-r" onClick={this.update}>
          Update
        </button>

        <AsyncButton
          class="btn btn-default"
          text="Cancel"
          pendingText="Cancelling"
          onClick={this.cancel}
        />
      </>
    );
  }
}
