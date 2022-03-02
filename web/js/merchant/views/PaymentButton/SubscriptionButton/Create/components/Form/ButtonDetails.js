import React from 'react';
import { connect } from 'react-redux';

import PropTypes from 'prop-types';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import InputDropdown from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form/components/InputDropdown';

import { buttonThemesList } from 'merchant/views/PaymentButton/PaymentButton/Create/constants/buttonThemes';
import {
  updatePaymentButtonData,
  updatePaymentField,
  updateStepReviewProgress,
} from 'merchant/reducers/subscriptionButtons/create';
import track from '../../track';

export const maxLengthForButtonLabel = 16;

@connect(null, {
  updatePaymentButtonData,
  updatePaymentField,
  updateStepReviewProgress,
})
export default class ButtonDetails extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);

    this.state = {
      disableSubmit: false,
    };
  }

  componentDidMount() {
    this.toggleDisableSubmit();
  }

  handleSubmit = (formData) => {
    const { title, button_text, button_theme } = formData;

    const data = {
      title,
      settings: {
        payment_button_theme: button_theme,
        payment_button_text: button_text,
      },
    };

    this.props.updatePaymentButtonData(data);

    this.props.goNext();

    this.markReviewDone(true);

    track.lj.trackButtonScreenNextSuccess();
  };

  handleChangeButtonLabel = (e) => {
    const data = {
      settings: {
        payment_button_text: e.target.value,
      },
    };

    // NOTE: This action fn. does deep merge. So, the new data won't replace the existing data in store
    this.props.updatePaymentButtonData(data);
  };

  handleChangeButtonTheme = (option) => {
    const data = {
      settings: {
        payment_button_theme: option.value,
      },
    };

    // NOTE: This action fn. does deep merge. So, the new data won't replace the existing data in store
    this.props.updatePaymentButtonData(data);

    this.markReviewDone(false);

    track.lj.trackButtonTheme(option);
  };

  toggleDisableSubmit = (e) => {
    const isFormChanged = e && e.hasOwnProperty('type');
    // Not required in first time bcoz it's already marked as per in store. Otherwise, behavior would be unexpexted in Edit Mode
    if (isFormChanged) {
      this.markReviewDone(false);
    }

    setTimeout(() => {
      const form = this.formEl;
      const disableSubmit = form && !!form.querySelectorAll('.is-invalid').length;

      if (this.state.disableSubmit !== disableSubmit) {
        this.setState({ disableSubmit });
      }
    });
  };

  markReviewDone = (isDone) => {
    this.props.updateStepReviewProgress({
      isButtonDetailsReviewed: isDone,
    });
  };

  setRefFormEl = (el) => (this.formEl = el);

  render() {
    const { subscriptionButtonId, subscriptionButtonEntity } = this.props;

    return (
      <Form
        onSubmit={this.handleSubmit}
        onChange={this.toggleDisableSubmit}
        setRef={this.setRefFormEl}
        style={{ display: this.props.isHidden ? 'none' : '' }}
      >
        <div class="PaymentButtonForm-ButtonDetails Form-content">
          <Input
            label="Title"
            name="title"
            class="Input--vTop"
            defaultValue={subscriptionButtonEntity.title}
            description="For dashboard use, not visible to customers"
            validator={(val) => {
              if (!val) {
                return 'Please fill out this field';
              }
              if (val.length < 3) {
                return 'Title must be atleast 3 characters';
              }
              if (val.length > 40) {
                return 'Title cannot be more than 40 characters';
              }
              return '';
            }}
            autoFocus={!subscriptionButtonId}
            required
          />

          <Input
            label="Button Label"
            name="button_text"
            class="Input--vTop"
            placeholder="Subscribe"
            description="This label is shown to your customers"
            defaultValue={subscriptionButtonEntity.settings.payment_button_text}
            required
            onChange={this.handleChangeButtonLabel}
            validator={(val) => {
              if (!val) {
                return 'Please fill out this field';
              }

              if (val.length > maxLengthForButtonLabel) {
                return `Maximum ${maxLengthForButtonLabel} characters are allowed`;
              }
              return '';
            }}
            onBlur={track.lj.trackButtonLabel}
          />

          <InputDropdown
            label="Button Theme"
            name="button_theme"
            class="Input--vTop"
            dropdownElementClass="Input-el-PaymentButtonForm"
            placeholder="Select Button Theme"
            options={buttonThemesList}
            optionLabelPath="label"
            optionValuePath="value"
            defaultValue={subscriptionButtonEntity.settings.payment_button_theme}
            onChange={this.handleChangeButtonTheme}
          />
        </div>

        <div class="Form-controls">
          <Button.Primary type="submit" disabled={this.state.disableSubmit}>
            Next <i class="i i-chevron-right" />
          </Button.Primary>
        </div>
      </Form>
    );
  }
}
