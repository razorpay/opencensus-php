import React, { Component } from 'react';

import ModalHeader from 'rzp/ui/ModalHeader';
import AsyncButton from 'react-async-button';

export default class EmailReport extends Component {
  state = {
    selectedEmails: [],
  };

  //TODO: check for duplicate emails
  handleChange = e => {
    const email = e.target.dataset.emailid;
    let selectedEmails = [...this.state.selectedEmails];

    const foundIndex = selectedEmails.indexOf(email);

    if (foundIndex > -1) {
      selectedEmails.splice(foundIndex, 1);
    } else {
      selectedEmails.push(email);
    }

    this.setState({ selectedEmails });
  };

  //TODO: integrate submit api

  render() {
    const { emails, closeModal } = this.props;
    const { selectedEmails } = this.state;

    return (
      <div>
        <ModalHeader title="Email Report" onCloseClick={closeModal} />
        <div class="modal-body">
          <p>
            Select email addresses from below to which you want to send the
            reports.
          </p>
          <form>
            <strong>Choose Email:</strong>
            {emails.map((email, index) => (
              <div class="form-group" key={index}>
                <div class="checkbox rzpCheckbox next">
                  <input
                    name={`email_${index}`}
                    id={`email_${index}`}
                    type="checkbox"
                    class="form-control"
                    data-emailid={email}
                    checked={selectedEmails.indexOf(email) > -1}
                    onChange={this.handleChange}
                  />
                  <label class="icon i-check" for={`email_${index}`}>
                    {email}
                  </label>
                </div>
              </div>
            ))}
            <AsyncButton
              class="btn btn-primary btn-block"
              text="Email Report"
            />
          </form>
        </div>
      </div>
    );
  }
}
