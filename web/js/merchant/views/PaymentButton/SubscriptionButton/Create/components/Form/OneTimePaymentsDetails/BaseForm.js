import React from 'react';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import EditorModal from 'merchant/views/PaymentButton/SubscriptionButton/Create/components/Form/components/EditorModal';

import { getCurrency } from 'common/ui/Amount';
import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import { validateAmount } from 'common/utils/validators';

// eslint-disable-next-line import/no-named-as-default
import FieldOptionsDropdown, {
  OptionsItem,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldOptionsDropdown';
import Button from 'common/new-ui/Button';

// import track from '../../../track';

export default class BaseForm extends React.Component {
  constructor(props) {
    super(props);

    const { field } = this.props;

    this.state = {
      disableSubmit: !field,
    };
  }

  toggleSubmitBtn = () => {
    const disableSubmit = !!this.formEl.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  handleSubmit = (formData) => {
    const { name, description, amount } = formData;

    // Normalize data as per amount field's blueprint
    const baseFormData = {
      item: {
        name,
        description,
        amount,
      },
      mandatory: false,
    };

    // Combine data from advanced form
    const newField = {
      ...this.props.field,
      ...baseFormData,
    };

    this.props.onSubmit(newField);

    this.props.handleClose();
  };

  handleChange = () => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  get currencySymbol() {
    const currency = this.props.currency;
    const _currencySymbol = getCurrency(currency).symbol;

    return _currencySymbol;
  }

  get additionalOptionsButton() {
    const { indexInOrder, handleDeleteField } = this.props;

    const showDeleteOption = typeof indexInOrder !== 'undefined' && handleDeleteField;

    return (
      showDeleteOption && (
        <FieldOptionsDropdown
          trigger={
            <Button.Transparent>
              <i class="i i-ellipsis-v" />
            </Button.Transparent>
          }
        >
          <OptionsItem>
            <div class="OptionsDropdown-item--delete" onClick={handleDeleteField}>
              <i class="i i-delete" />
              <div>Delete Field</div>
            </div>
          </OptionsItem>
        </FieldOptionsDropdown>
      )
    );
  }

  get formFooter() {
    const { disableSubmit } = this.state;

    return (
      <div class="CreatorModal-BaseForm-footer">
        <button
          type="button"
          class="cancel-btn Button--transparent Button"
          onClick={() => {
            this.props.handleClose();

            // track.lj.trackCustomerScreenCancelFieldChanges();
          }}
        >
          <span>&times;</span>
          Cancel
        </button>

        <button type="submit" class="save-btn Button--transparent Button" disabled={disableSubmit}>
          <span class="icon i-check" />
          Save
        </button>
      </div>
    );
  }

  setRefForm = (el) => (this.formEl = el);

  render() {
    const { field, indexInOrder, validateSameTitleExists, currency } = this.props;

    const minAmountAllowed = i18CurrencyConversionFromMinorUnitToCommonUnit(
      getCurrency(currency).min_value,
      currency,
    );

    return (
      <EditorModal class="CreatorModal-BaseForm" overElement allowScroll>
        <Form onSubmit={this.handleSubmit} onChange={this.handleChange} setRef={this.setRefForm}>
          <Input
            name="name"
            label="Label"
            required
            class="Input--vTop"
            placeholder="Enter field label"
            defaultValue={field ? field.item.name : ''}
            autoFocus
            validator={(val) => {
              if (!val) {
                return 'Field label is required';
              }

              const regex = new RegExp(`^[0-9a-zA-Z ]+$`, 'i');

              if (!regex.test(val)) {
                return 'Please enter valid value';
              }

              if (!isNaN(val)) {
                return 'Field label must have at least 1 character';
              }

              if (validateSameTitleExists(val, indexInOrder)) {
                return 'Field label cannot be same as other field';
              }
              return '';
            }}
          />

          <Input.Group class="Input--vTop">
            <Input
              name="amount"
              label="Value"
              class="Input--vTop"
              placeholder="Enter Amount"
              defaultValue={field ? field.item.amount : ''}
              required
              validator={(val) => validateAmount(val, minAmountAllowed, currency)}
            />

            <Input.TextareaAutoResize
              class="Input--description"
              name="description"
              placeholder="Enter field description"
              defaultValue={field ? field.item.description : ''}
              validator={(val) => {
                if (val && val.length > 128) {
                  return 'Field description cannot be more than 128 characters';
                }
                return '';
              }}
            />
          </Input.Group>

          {this.formFooter}
        </Form>

        {this.additionalOptionsButton}
      </EditorModal>
    );
  }
}
