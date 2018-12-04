import Input from 'component/Input';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import Button from 'component/Button';
import RemoveBtn from '../RemoveBtn';
import { isEmail } from 'rzp/utils/validators';

const phoneIcon = (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M0 0h24v24H0z" fill="none" />
    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
  </svg>
);

const emailIcon = (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
    <path d="M0 0h24v24H0z" fill="none" />
  </svg>
);

export default class extends React.PureComponent {
  state = { isEditable: false };

  toggleEditMode = _ => {
    const isOpen = !this.state.isEditable;
    if (isOpen) {
      this.openCountField = 2;
    }

    this.setState({ isEditable: isOpen });
  };

  removeField = fieldName => {
    const removeEle = document.querySelector(
      `#support-details input[name="${fieldName}"]`
    );

    this.props.updateData({
      target: {
        name: fieldName,
        value: '',
      },
    });

    this.openCountField--;

    if (this.openCountField === 0) {
      this.toggleEditMode();
    }
  };

  render() {
    let { support_email, support_contact } = this.props,
      hasSupportInfo = support_email || support_contact,
      isEditable = this.state.isEditable;

    let content = '';

    if (hasSupportInfo || isEditable) {
      content = (
        <React.Fragment>
          <label>Contact Us:</label>
          <SupportSubField
            name="support_email"
            placeholder="Enter support email"
            icon={emailIcon}
            defaultValue={support_email}
            autoFocus={!support_email}
            onBlur={this.props.updateData}
            removeField={this.removeField}
            addButtonLabel="Add Support Email"
            validator={val => {
              if (!val) {
                return;
              } else if (!isEmail(val)) {
                return 'Invalid Email';
              }
            }}
          />

          <SupportSubField
            name="support_contact"
            placeholder="Enter support phone"
            icon={phoneIcon}
            defaultValue={support_contact}
            autoFocus={support_email && !support_contact}
            onBlur={this.props.updateData}
            removeField={this.removeField}
            addButtonLabel="Add Support Phone"
          />
        </React.Fragment>
      );
    } else {
      content = (
        <span class="help-content">
          <Button.Transparent class="btn-link" onClick={this.toggleEditMode}>
            + Add your contact information
          </Button.Transparent>
          <Popover align="right" theme="dark">
            <PopoverBody>
              Provide your contact information so your customers can reach out
            </PopoverBody>
          </Popover>
        </span>
      );
    }

    return <div id="support-details">{content}</div>;
  }
}

class SupportSubField extends React.PureComponent {
  state = { isEditable: true };

  toggleEditMode = () => {
    const isOpen = !this.state.isEditable;
    if (!isOpen) {
      this.props.removeField(this.props.name);
    }

    this.setState({ isEditable: isOpen });
  };

  render() {
    const { icon, removeField, addButtonLabel, ...rest } = this.props;

    return (
      <div class="sub-detail">
        {this.state.isEditable ? (
          <React.Fragment>
            {icon}
            <Input name={name} {...rest} />
            <RemoveBtn onClick={this.toggleEditMode} />
          </React.Fragment>
        ) : (
          <Button.Transparent class="btn-link" onClick={this.toggleEditMode}>
            + {addButtonLabel}
          </Button.Transparent>
        )}
      </div>
    );
  }
}
