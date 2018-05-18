import React, { Component } from 'react';

import ModalHeader from 'rzp/ui/ModalHeader';
import AsyncButton from 'react-async-button';

export default class EmailReport extends Component {
  // emails map for tracking duplicate entries
  // format {email_id : email@example.com}
  emailsMap = {};

  state = {
    //list of selected email ids
    selectedEmails: [],
  };

  componentWillMount() {
    const { emails } = this.props;

    //create <email_id -> email> map
    emails.forEach(
      (email, index) => (this.emailsMap[`email_${index}`] = email)
    );
  }

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

  handleSend = () => {
    let emails = this.state.selectedEmails
      .map(emailId => this.emailsMap[emailId])
      .join(',');

    //send empty event
    return this.props.onSend(null, emails);
  };

  render() {
    const { selectedEmails } = this.state;
    const { emailsMap } = this;

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
            {Object.keys(emailsMap).map((emailId, index) => (
              <div class="form-group" key={emailId}>
                <div class="checkbox rzpCheckbox next">
                  <input
                    name={emailId}
                    id={emailId}
                    type="checkbox"
                    class="form-control"
                    data-emailid={emailId}
                    checked={selectedEmails.indexOf(emailId) > -1}
                    onChange={this.handleChange}
                  />
                  <label class="icon i-check" for={emailId}>
                    {emailsMap[emailId]} {index === 0 && ' (you)'}
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
