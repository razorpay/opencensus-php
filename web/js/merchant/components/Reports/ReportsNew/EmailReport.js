import React, { Component } from 'react';

import ModalHeader from 'rzp/ui/ModalHeader';
import AsyncButton from 'react-async-button';

export default class EmailReport extends Component {
  state = {
    //list of selected email ids
    selectedEmails: [],
  };

  handleChange = e => {
    const email = e.target.dataset.email;
    let selectedEmails = [...this.state.selectedEmails];

    const foundIndex = selectedEmails.indexOf(email);

    if (foundIndex > -1) {
      selectedEmails.splice(foundIndex, 1);
    } else {
      selectedEmails.push(email);
    }

    this.setState({ selectedEmails });
  };

  handleSend = () => {
    let emails = this.state.selectedEmails;
    //send empty event
    return this.props.onSend(null, emails, this.props.configId);
  };

  render() {
    const { selectedEmails } = this.state;

    return (
      <div>
        <ModalHeader
          title="Email Report"
          onCloseClick={this.props.closeModal}
        />
        <div class="modal-body">
          <p>
            Select email addresses from below to which you want to send the
            reports.
          </p>
          <form>
            <strong>Choose Email:</strong>
            {this.props.emails.map((email, index) => (
              <div class="form-group" key={email}>
                <div class="checkbox rzpCheckbox next">
                  <input
                    name={email}
                    id={email}
                    type="checkbox"
                    class="form-control"
                    data-email={email}
                    checked={selectedEmails.indexOf(email) > -1}
                    onChange={this.handleChange}
                  />
                  <label class="icon i-check" for={email}>
                    {email} {index === 0 && ' (you)'}
                  </label>
                </div>
              </div>
            ))}
            <AsyncButton
              class="btn btn-primary btn-block"
              text="Email Report"
              onClick={this.handleSend}
              disabled={selectedEmails.length < 1}
            />
          </form>
        </div>
      </div>
    );
  }
}
