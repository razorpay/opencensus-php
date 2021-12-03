import React from 'react';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';
import AddEmailModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import rolesList from 'merchant/helpers/permissions/roles-list';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { compose } from 'redux';
import ChooseEmail from './ChooseEmail';

class EmailReport extends React.Component {
  state = {
    selectedEmails: [],
  };

  onChange = ({ target }) => {
    const { name: clickedEmail, checked } = target;
    const { selectedEmails } = this.state;

    analyticsTrack({
      objectName: 'email selection',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        emailSelected: checked,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    let newSelectedEmails;
    if (checked) {
      newSelectedEmails = [...selectedEmails, clickedEmail];
    } else {
      newSelectedEmails = selectedEmails.filter((email) => email !== clickedEmail);
    }

    this.setState({ selectedEmails: newSelectedEmails });
  };

  getValue = () => {
    return this.state.selectedEmails;
  };

  openChooseEmailModal = () => {
    const { openModal } = this.props;

    openModal({
      size: 'small',
      component: (
        <ChooseEmail
          closeModal={this.props.closeModal}
          selectedEmails={this.state.selectedEmails}
          emails={this.props.emails}
          onChange={this.onChange}
        />
      ),
    });
  };
  openAddEmailModal = () => {
    const { openModal } = this.props;

    analyticsTrack({
      objectName: 'add email',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    openModal({
      size: 'small',
      component: <AddEmailModal screen="reports" />,
    });
  };

  render() {
    const { emails, user, isFormDisabled } = this.props;
    const { selectedEmails } = this.state;
    return (
      <div class="Input EmailReport">
        <div class="Input-content">
          <div class="Input">
            <div class={classList('Input-label', isFormDisabled && 'Input--disabled')}>
              Email Report To
            </div>
            {emails.length && !emails.every((email) => email === null) ? (
              selectedEmails.length > 1 ? (
                <NoOfEmailsSelected noOfEmails={selectedEmails.length} />
              ) : (
                <SelectEmailCheckBox
                  selectedEmails={selectedEmails}
                  onChange={this.onChange}
                  defaultEmail={emails[0]}
                  isFormDisabled={isFormDisabled}
                />
              )
            ) : null}
            {(!emails.length || emails.every((email) => email === null)) &&
            user &&
            user.role === rolesList.OWNER &&
            !user.user?.signup_via_email ? (
              <Button.Transparent
                type="button"
                class="Btn--link"
                onClick={this.openAddEmailModal}
                disabled={isFormDisabled}
              >
                Add Email
              </Button.Transparent>
            ) : null}
          </div>
          {emails.length && !emails.every((email) => email === null) ? (
            <div class="Input ChooseEmail">
              <Button.Transparent
                type="button"
                class="Btn--Link"
                onClick={this.openChooseEmailModal}
                disabled={isFormDisabled}
              >
                Choose email
              </Button.Transparent>
            </div>
          ) : null}
        </div>
      </div>
    );
  }
}

function NoOfEmailsSelected({ noOfEmails }) {
  return <div class="NoOfEmailsSelected">{noOfEmails} Emails selected</div>;
}

function SelectEmailCheckBox({ selectedEmails, onChange, defaultEmail, isFormDisabled }) {
  const singleSelectedEmail = selectedEmails[0];
  return (
    <Input.Check
      fieldLabel={singleSelectedEmail || defaultEmail}
      name={singleSelectedEmail || defaultEmail}
      defaultValue={!!singleSelectedEmail}
      onChange={onChange}
      disabled={isFormDisabled}
      autoRender
    />
  );
}

export default compose(
  connect(
    (state) => ({ user: state.session.user }),
    { openModal: fnOpenModal, closeModal: fnCloseModal },
    null,
    {
      withRef: true,
    },
  ),
)(EmailReport);
