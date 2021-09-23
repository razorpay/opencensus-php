import React from 'react';
import { connect } from 'react-redux';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';
import EditorModal from '../components/EditorModal';
import FieldOptionsDropdown, {
  OptionsItem,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldOptionsDropdown';
import AdvancedForm from './AdvancedForm';
import { DynamicAmount, FixedAmount, FixedAmountWithQuantity } from './FieldTypesRepresentations';

import { paiseToRupees } from 'common/utils/rzp-utils';
import { getCurrency } from 'common/ui/Amount';
import FIELD_TYPES from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import { isMandatoryToBool } from '../../../../../../PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { validateAmount } from 'common/utils/validators';

import track from '../../../track';

@connect(null, {
  openModal,
  closeModal,
})
export default class BaseForm extends React.Component {
  constructor(props) {
    super(props);

    const { field, currency } = this.props;

    this.state = {
      currency,
      field, // This becomes source of truth of rest of the component
      hasDescription: !!field.description,
      isMandatory: field.mandatory,
      disableSubmit: !field.item.name,
      isAdvancedFormOpened: false,
    };
  }

  toggleSubmitBtn = () => {
    const disableSubmit = !!this.formEl.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  onSubmitAdvancedForm = (formData) => {
    const { field } = this.state;

    // TODO: HERE....
    // TODO: Can be merged with constructAmountField which handles extra cases as well, but not straightforward
    if (formData.hasOwnProperty('min_purchase') && !formData.min_purchase) {
      formData.min_purchase = 0; // Cannot be null (inorder to differentiate field definition from fixed price optional field)
    }

    if (formData.hasOwnProperty('max_purchase') && !Number(formData.max_purchase)) {
      formData.max_purchase = null;
    }

    if (formData.hasOwnProperty('min_amount') && !Number(formData.min_amount)) {
      formData.min_amount = null; // Has to be null, not 0 for proper validation at BE
    }

    if (formData.hasOwnProperty('max_amount') && !Number(formData.max_amount)) {
      formData.max_amount = null;
    }

    // If stock key is not defined, that means, Unlimited is selected.
    formData.stock = formData.stock || null; // Setting key to null explicitly inorder to override previous value, cuz "objects" are merged, not override.

    this.setState({
      field: {
        ...field,
        ...formData,
      },
    });
  };

  handleSubmitBaseForm = (formData) => {
    const { name, description, amount } = formData;
    const { currency } = this.state; // Can be taken from formData.currency too

    // Normalize data as per amount field's blueprint
    const baseFormData = {
      item: {
        name,
        description,
        amount,
      },
    };

    // Note: the advanced form data, isMandatory is already updated in this.state.field

    // Combine data from advanced form
    const newField = {
      ...this.state.field,
      ...baseFormData,
    };

    // Update amount item
    this.props.onSubmit(newField, currency);

    this.props.handleClose();
  };

  handleChange = () => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  handleToggleMakeMandatory = () => {
    this.setState((prevState) => {
      return {
        isMandatory: !prevState.isMandatory,
      };
    }, this.onChangeIsMandatory);
  };

  // To keep BaseForm and AdvancedForm in sync. Helps in adding default value and validators on min_purchase / min_amount.
  onChangeIsMandatory = (mandatory) => {
    const { fieldType } = this.props;
    const { field, currency } = this.state;

    const isMandatory = isMandatoryToBool(this.state.isMandatory);

    const newField = { ...field };
    newField.mandatory = isMandatory;

    if (isMandatory) {
      switch (fieldType) {
        case FIELD_TYPES.dynamic_price.key: {
          const minAmountAllowed = paiseToRupees(getCurrency(currency).min_value); // Dealing with rupees(bigger currency) in UI. Converted to paisa only when sent to API.

          if (Number(newField.min_amount) < Number(minAmountAllowed)) {
            newField.min_amount = minAmountAllowed; // Must be at least min payable value as per currency
          }

          break;
        }

        case FIELD_TYPES.multiple_purchase.key: {
          if (Number(newField.min_purchase) === 0) {
            newField.min_purchase = 1; // Must be at least 1 if mandatory field
          }

          break;
        }
        default:
      }
    }

    this.setState({
      field: newField,
    });

    track.toggleMakeMandatory(mandatory);
  };

  handleToggleAddDescription = () => {
    this.setState(
      (prevState) => {
        return {
          hasDescription: !prevState.hasDescription,
        };
      },
      () => {
        track.amountFieldDescription(this.state.hasDescription);
      },
    );
  };

  handleToggleAdvancedOptionsForm = (toOpen) => {
    this.setState({
      isAdvancedFormOpened: toOpen,
    });
  };

  onChangeCurrency = (selectedCurrency) => {
    // TODO: Add onUpdateCurrency
    const newCurrencyISO = selectedCurrency.name;

    track.changeCurrency();

    this.setState({
      currency: newCurrencyISO,
    });

    const currentCommonCurrency = this.props.currency; // This is currency set in the store, not in this in component

    if (currentCommonCurrency !== newCurrencyISO) {
      this.props.openModal({
        size: 'small',
        component: (
          <div>
            <ModalHeader title="Change Currency?" />

            <div class="modal-body">
              <div>
                You're changing currency from <b>{this.props.currency}</b> to{' '}
                <b>{newCurrencyISO}</b>.
              </div>
              <div>On saving this item, this currency will apply to all items on this page.</div>
              <br />
              <footer>
                <Button.Primary class="Button--full-width" onClick={this.props.closeModal}>
                  Ok
                </Button.Primary>
              </footer>
            </div>
          </div>
        ),
      });
    }
  };

  get additionalOptionsButton() {
    const { indexInOrder, handleDeleteField } = this.props;
    const { isMandatory, hasDescription } = this.state;

    return (
      <FieldOptionsDropdown
        trigger={
          <Button.Transparent onClick={track.amountScreenOpenMoreOptions}>
            <i class="i i-ellipsis-v" />
          </Button.Transparent>
        }
      >
        <OptionsItem isSelected={!isMandatory}>
          <div onClick={this.handleToggleMakeMandatory}>
            <i class="i i-optional_mark" />
            Optional Item
          </div>
        </OptionsItem>

        <OptionsItem isSelected={!!hasDescription}>
          <div onClick={this.handleToggleAddDescription}>
            <i class="i i-sort i-fix-sort" />
            {hasDescription ? 'Remove Description' : 'Add Description'}
          </div>
        </OptionsItem>

        <OptionsItem>
          <div
            onClick={() => {
              this.handleToggleAdvancedOptionsForm(true);

              track.openAdvanceOptions();
            }}
          >
            <i class="i i-options" />
            <div>
              Advanced Options
              <div class="subOption">Add quantity, define rules around quantity, etc.</div>
            </div>
          </div>
        </OptionsItem>

        {typeof indexInOrder !== 'undefined' && handleDeleteField && (
          <OptionsItem>
            <div class="OptionsDropdown-item--delete" onClick={handleDeleteField}>
              <i class="i i-delete" />
              <div>Delete Field</div>
            </div>
          </OptionsItem>
        )}
      </FieldOptionsDropdown>
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

            track.amountFieldCancel();
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

  getAmountInputField(isDynamicAmountField) {
    const disableAmountInput = isDynamicAmountField;
    const { isEditExistingId } = this.props;
    const { field, currency } = this.state;

    const amount = field.item.amount || ''; // Note: If amount is there, then disableAmountInput = false;
    const placeholder = disableAmountInput ? 'To be filled by customer' : '0.00';
    const minAmountAllowed = disableAmountInput
      ? ''
      : paiseToRupees(getCurrency(currency).min_value);

    let inputField = (
      <Input
        name={disableAmountInput ? undefined : 'amount'}
        class="placeholder-field Input--Amount"
        placeholder={placeholder}
        defaultValue={amount}
        validator={(val) => validateAmount(val, minAmountAllowed)}
        disabled={disableAmountInput}
        required={!disableAmountInput}
      />
    );

    if (disableAmountInput) {
      inputField = (
        <DynamicAmount field={field} currency={currency}>
          {inputField}
        </DynamicAmount>
      );
    }

    return (
      <Input.Group label="Value" class="InputGroup--inline">
        <div class="Input-content">
          <Input.CurrencySelect
            defaultValue={currency}
            parentQuerySelector=".Modal-content--PaymentButton-CreateForm"
            onChange={this.onChangeCurrency}
            disabled={isEditExistingId}
          />

          {inputField}
        </div>
      </Input.Group>
    );
  }

  get amountFieldForFieldType() {
    const { fieldType } = this.props;
    const { field } = this.state;

    switch (fieldType) {
      case FIELD_TYPES.fixed_price.key:
        return (
          <FixedAmount isMandatory={this.state.isMandatory}>
            {this.getAmountInputField()}
          </FixedAmount>
        );

      case FIELD_TYPES.dynamic_price.key:
        return this.getAmountInputField(true);

      case FIELD_TYPES.multiple_purchase.key:
        return (
          <FixedAmountWithQuantity field={field}>
            {this.getAmountInputField()}
          </FixedAmountWithQuantity>
        );
      default:
        return '';
    }
  }

  setRefForm = (el) => (this.formEl = el);

  render() {
    const { fieldType } = this.props;
    const { field, currency } = this.state;

    // Field is taken from state and not from props, bcoz user might change field_type from InputDropdown
    const { isMandatory, hasDescription, isAdvancedFormOpened } = this.state;

    return (
      <React.Fragment>
        <EditorModal class="CreatorModal-BaseForm" overElement allowScroll>
          <Form
            onSubmit={this.handleSubmitBaseForm}
            onChange={this.handleChange}
            setRef={this.setRefForm}
          >
            <Input.Group label="Field Label" class="Input--vTop" required>
              <Input
                name="name"
                class="Input--vTop"
                placeholder="Enter field label"
                defaultValue={field.item.name}
                autoFocus
                description={!isMandatory ? 'Optional' : ''}
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

                  if (this.props.validateSameTitleExists(val, this.props.indexInOrder)) {
                    return 'Field label cannot be same as other field';
                  }
                  return '';
                }}
              />
            </Input.Group>

            <Input.Group class="Input--vTop">
              {this.amountFieldForFieldType}

              {hasDescription && (
                <Input.TextareaAutoResize
                  class="Input--description"
                  name="description"
                  placeholder="Enter field description"
                  defaultValue={field.description}
                  validator={(val) => {
                    if (val && val.length > 128) {
                      return 'Field description cannot be more than 128 characters';
                    }
                    return '';
                  }}
                />
              )}
            </Input.Group>

            {this.formFooter}
          </Form>

          {this.additionalOptionsButton}
        </EditorModal>
        {isAdvancedFormOpened && (
          <AdvancedForm
            field={field}
            fieldType={fieldType}
            currency={currency}
            onSubmit={this.onSubmitAdvancedForm}
            handleClose={() => this.handleToggleAdvancedOptionsForm(false)}
          />
        )}
      </React.Fragment>
    );
  }
}
