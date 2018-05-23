import React, { Component } from 'react';

import ModalHeader from 'rzp/ui/ModalHeader';
import AsyncButton from 'react-async-button';

import { trackReportGenericActions } from 'merchant/containers/Reports/ReportsNew/ga';

export default class EmailReport extends Component {
  state = {
    //list of selected email ids
    selectedEmails: [],
  };

  allEmails = [];

  componentWillMount() {
    const { emailsMap } = this.props;
    let allEmails = [];

    Object.keys(emailsMap).forEach(emailType => {
      allEmails = [...allEmails, ...emailsMap[emailType]];
    });

    //unique emails
    this.allEmails = Array.from(new Set(allEmails));
  }

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
    const { selectedEmails } = this.state;
    const { emailsMap } = this.props;

    let trackLabel = [];

    // for ga track email event with following precedence
    // contact > transaction > account
    selectedEmails.forEach((email, index) => {
      if (emailsMap['account'] && emailsMap['account'].indexOf(email) > -1) {
        trackLabel[index] = 'account';
      }

      if (
        emailsMap['transaction'] &&
        emailsMap['transaction'].indexOf(email) > -1
      ) {
        trackLabel[index] = 'transaction';
      }

      if (emailsMap['contact'] && emailsMap['contact'].indexOf(email) > -1) {
        trackLabel[index] = 'contact';
      }
    });

    trackLabel = Array.from(new Set(trackLabel)).join(' | ');

    trackReportGenericActions('Click - Email Report || Email To', trackLabel);
    //send empty event
    return this.props.onSend(null, selectedEmails, this.props.configId);
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
          <form class="m-t">
            <strong>Choose Email:</strong>
            {this.allEmails.map((email, index) => (
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
              class="btn btn-primary btn-block m-t"
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
