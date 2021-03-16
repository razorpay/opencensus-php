import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';

import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';

import { openModal, closeModal } from 'merchant_common/reducers/modals';

import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import ChooseEmail from './ChooseEmail';

@connect(null, { openModal, closeModal }, null, { withRef: true })
export default class EmailReport extends React.Component {
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
    this.props.openModal({
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

  render() {
    const { emails, isFormDisabled } = this.props;
    const { selectedEmails } = this.state;
    return (
      <div class="Input EmailReport">
        <div class="Input-content">
          <div class="Input">
            <div class={classList('Input-label', isFormDisabled && 'Input--disabled')}>
              Email Report To
            </div>
            {selectedEmails.length > 1 ? (
              <NoOfEmailsSelected noOfEmails={selectedEmails.length} />
            ) : (
              <SelectEmailCheckBox
                selectedEmails={selectedEmails}
                onChange={this.onChange}
                defaultEmail={emails[0]}
                isFormDisabled={isFormDisabled}
              />
            )}
          </div>
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
