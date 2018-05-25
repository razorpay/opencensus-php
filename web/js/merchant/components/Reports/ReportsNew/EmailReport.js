import React, { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';

import ModalHeader from 'rzp/ui/ModalHeader';
import * as NotificationsActions from 'rzp/modules/notifications';

import { emailReportV2 } from 'merchant/modules/reports';
import { marketplaceConfigTypes } from 'merchant/containers/Reports/ReportsNew/data';
import {
  trackTimeLapse,
  trackReportActions,
  trackReportGenericActions,
} from 'merchant/containers/Reports/ReportsNew/ga';

@connect(
  state => {
    return {
      user: state.session.user,
      currentReportList: state.reports.currentReportList,
    };
  },
  { ...NotificationsActions }
)
export default class EmailReport extends Component {
  state = {
    //list of selected email ids
    selectedEmails: [],
  };

  allEmails = [];

  //used for ga tracking
  reportList = this.props.currentReportList || {};

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
    return this.emailReport(selectedEmails, this.props.reportId);
  };

  emailToSentence = emails => {
    if (emails.length === 1) {
      return emails[0];
    } else {
      return `${emails[0]} and ${emails.length - 1} others`;
    }
  };

  emailReport = (selectedEmails, reportId = null) => {
    const {
      user,
      selectedType,
      selectedDate,
      selectedMonth,
      selectedConfig,
      selectedAccount,
      defaultAccount,
    } = this.props;

    let reqData = null,
      shouldUpdate = false;

    const selectedAccountId = (selectedConfig.type in marketplaceConfigTypes
      ? selectedAccount.id
      : defaultAccount.id
    ).replace('acc_', '');

    const isMerchantAccount = selectedAccountId === user.current;

    const timeInterval =
      selectedType === 'daily' ? selectedDate.date() : selectedDate.month() + 1;

    if (reportId) {
      const timeLapse =
        new Date().getTime() - this.reportList[reportId]['created_at'] * 1000;

      reqData = { emails: selectedEmails, id: reportId };
      shouldUpdate = true;

      trackTimeLapse('Click - Download to Email Time', timeLapse);
      trackReportActions(
        'Click - Email Report (while downloading)',
        selectedType,
        timeInterval,
        selectedConfig.label
      );
    } else {
      const timeFactor = selectedType === 'daily' ? 'day' : 'month',
        startTime = selectedDate
          .clone()
          .startOf(timeFactor)
          .unix(),
        endTime = selectedDate
          .clone()
          .endOf(timeFactor)
          .unix();

      reqData = {
        config_id: selectedConfig._item.id,
        generated_by: selectedAccountId,
        start_time: startTime,
        end_time: endTime,
        emails: selectedEmails,
      };

      trackReportActions(
        'Click - Email Report',
        selectedType,
        timeInterval,
        selectedConfig.label
      );
    }

    return emailReportV2(reqData, isMerchantAccount, shouldUpdate)
      .then(data => {
        if (data.error) {
          return this.props.showNotification({
            type: 'error',
            message: data.error,
          });
        }

        this.props.closeModal();

        //update the store
        if (reportId) {
          this.props.updateStore(data.data);
        }

        return this.props.showNotification({
          type: 'success',
          message: `Report will be emailed to ${this.emailToSentence(
            data.data.emails
          )} shortly`,
        });
      })
      .catch(err => {
        console.error(err);
        this.props.showNotification({
          type: 'error',
          message: 'Oops! Unable to email reports.',
        });
      });
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
