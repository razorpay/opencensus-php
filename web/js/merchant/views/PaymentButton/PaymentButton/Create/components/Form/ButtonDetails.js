import React from 'react';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import InputCurrencyAmount from './components/InputCurrencyAmount';
import InputDropdown from './components/InputDropdown';

import {
  buttonThemesList,
  orgButtonThemes,
} from 'merchant/views/PaymentButton/PaymentButton/Create/constants/buttonThemes';
import { getBaseFieldForAmountFieldType } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import FIELD_TYPES from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import META, {
  templateTypes,
} from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';
import {
  updatePaymentButtonData,
  updateAmountField,
  updateStepReviewProgress,
} from 'merchant/reducers/paymentbuttons/create';
import track from '../../track';

export const maxLengthForButtonLabel = 20;

@connect(
  (state) => ({
    user: state.session.user,
  }),
  {
    updatePaymentButtonData,
    updateAmountField,
    updateStepReviewProgress,
  },
)
export default class ButtonDetails extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);

    this.state = {
      disableSubmit: false,
    };

    this.buttonTypesList = META.map((templateType) => ({
      label: templateType.card.title,
      value: templateType.key,
    }));
  }

  componentDidMount() {
    this.toggleDisableSubmit();

    // Update default button theme in store if first-time creation mode
    const { user, isEditExistingId } = this.props;
    if (!isEditExistingId && user.isOrgAxis) {
      this.updateButtonTheme(orgButtonThemes.axis);
    }
  }

  handleSubmit = (formData) => {
    const { title, button_text, currency, amount } = formData;

    const { paymentButtonEntity } = this.props;
    const buttonTheme = paymentButtonEntity.settings.payment_button_theme; // Reusing value from store as it was updated when button theme was changed

    const data = {
      title,
      settings: {
        payment_button_theme: buttonTheme,
        payment_button_text: button_text,
      },
    };

    if (this.isQuickPayTemplate) {
      data.currency = currency;

      const fixedAmountItem = this.amountFieldForQuickPayTemplate;
      fixedAmountItem.item.amount = amount;

      this.props.updateAmountField(fixedAmountItem, 0);
    }

    this.props.updatePaymentButtonData(data);

    this.props.goNext();

    this.markReviewDone(true);

    track.buttonScreenNextSuccess();
  };

  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  get amountFieldForQuickPayTemplate() {
    const { amountFields } = this.props;
    let amountField;

    if (amountFields && amountFields.length) {
      amountField = amountFields[0];
    } else {
      amountField = getBaseFieldForAmountFieldType(FIELD_TYPES.fixed_price.key);

      amountField.item = {
        name: 'Amount',
      };
    }

    return amountField;
  }

  handleChangeButtonType = (option) => {
    const { paymentButtonEntity } = this.props;
    const currentTemplateType = paymentButtonEntity.settings.payment_button_template_type;

    if (option.value === currentTemplateType) {
      return;
    }

    track.buttonTypeChange();

    this.context.confirm({
      header: 'Change Button Type?',
      message: () => (
        <div class="text-semi-muted">
          <p>All changes would be lost. Do you want to continue?</p>
        </div>
      ),
      affirmativeLabel: 'Yes',
      abortLabel: 'Cancel',
      action: () => {
        this.props.onChangeButtonTemplate(option.value);

        track.buttonTypeDiscardYes(option.value);
      },
      abort: () => {
        track.buttonTypeDiscardNo();
      },
    });
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

  // This is needed so that button preview can be in sync
  handleChangeButtonTheme = (option) => {
    this.updateButtonTheme(option.value);

    this.markReviewDone(false);

    track.buttonTheme(option?.value);
  };

  updateButtonTheme(themeValue) {
    const data = {
      settings: {
        payment_button_theme: themeValue,
      },
    };

    // NOTE: This action fn. does deep merge. So, the new data won't replace the existing data in store
    this.props.updatePaymentButtonData(data);
  }

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
    const { user, paymentButtonId, paymentButtonEntity, isEditExistingId } = this.props;

    const templateType = paymentButtonEntity.settings.payment_button_template_type;
    const currency = paymentButtonEntity.currency;

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
            defaultValue={paymentButtonEntity.title}
            description="For dashboard use, not visible to customers"
            validator={(val) => {
              if (!val) {
                return 'Please fill out this field';
              }

              if (val.length < 3) {
                return 'Title must be atleast 3 characters';
              } else if (val.length > 40) {
                return 'Title cannot be more than 40 characters';
              }
              return '';
            }}
            autoFocus={!paymentButtonId}
            required
          />

          <InputDropdown
            label="Button Type"
            description="Change button type"
            class="Input--vTop"
            dropdownElementClass="Input-el-PaymentButtonForm"
            placeholder="Select Button Type"
            options={this.buttonTypesList}
            optionLabelPath="label"
            optionValuePath="value"
            value={templateType}
            onChange={this.handleChangeButtonType}
            disabled={isEditExistingId}
          />

          {this.isQuickPayTemplate && (
            <InputCurrencyAmount
              label="Amount"
              description="Your customers will pay this amount"
              name="amount"
              class="Input--vTop Input--CurrencyAmount"
              placeholder="Add your amount"
              defaultValueCurrency={currency}
              defaultValueAmount={this.amountFieldForQuickPayTemplate.item.amount || ''}
              required
              disabledCurrency={isEditExistingId}
              onBlur={track.buttonAmount}
            />
          )}

          <Input
            label="Button Label"
            name="button_text"
            class="Input--vTop"
            description="This label is shown to your customers"
            defaultValue={paymentButtonEntity.settings.payment_button_text}
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
            onBlur={track.buttonLabel}
          />

          {!user.isOrgAxis && (
            <InputDropdown
              label="Button Theme"
              name="button_theme"
              class="Input--vTop"
              dropdownElementClass="Input-el-PaymentButtonForm"
              placeholder="Select Button Theme"
              options={buttonThemesList}
              optionLabelPath="label"
              optionValuePath="value"
              defaultValue={paymentButtonEntity.settings.payment_button_theme}
              onChange={this.handleChangeButtonTheme}
            />
          )}
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
