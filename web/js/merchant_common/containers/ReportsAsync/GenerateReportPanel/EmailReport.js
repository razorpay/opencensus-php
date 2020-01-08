import { connect } from 'react-redux';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';

import ChooseEmail from './ChooseEmail';

@connect(null, { openModal, closeModal }, null, { withRef: true })
export default class EmailReport extends React.Component {
  state = {
    selectedEmails: [],
  };

  onChange = ({ target }) => {
    const { name: clickedEmail, checked } = target;
    const { selectedEmails } = this.state;

    let newSelectedEmails;
    if (checked) {
      newSelectedEmails = [...selectedEmails, clickedEmail];
    } else {
      newSelectedEmails = selectedEmails.filter(
        email => email !== clickedEmail
      );
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
    const { emails } = this.props;
    const { selectedEmails } = this.state;
    return (
      <div class="Input EmailReport">
        <div class="Input-content">
          <div class="Input">
            <div class="Input-label">Email Report To</div>
            {selectedEmails.length > 1 ? (
              <NoOfEmailsSelected noOfEmails={selectedEmails.length} />
            ) : (
              <SelectEmailCheckBox
                selectedEmails={selectedEmails}
                onChange={this.onChange}
                defaultEmail={emails[0]}
              />
            )}
          </div>
          <div class="Input ChooseEmail">
            <Button.Transparent
              type="button"
              class="Btn--Link"
              onClick={this.openChooseEmailModal}
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

function SelectEmailCheckBox({ selectedEmails, onChange, defaultEmail }) {
  const singleSelectedEmail = selectedEmails[0];
  return (
    <Input.Check
      fieldLabel={singleSelectedEmail || defaultEmail}
      name={singleSelectedEmail || defaultEmail}
      defaultValue={!!singleSelectedEmail}
      onChange={onChange}
    />
  );
}
