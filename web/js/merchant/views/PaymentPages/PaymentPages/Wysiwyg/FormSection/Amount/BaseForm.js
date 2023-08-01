import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { classList, i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import { isMandatoryToBool } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import FIELD_TYPES_MAP from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import FieldOptionsDropdownWrapper, {
  OptionsItem,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldOptionsDropdown';
// eslint-disable-next-line import/no-named-as-default
import Popover, { PopoverBody } from 'common/ui/Popover';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import { getCurrency } from 'common/ui/Amount';
import { validateAmount } from 'common/utils/validators';

import ModalHeader from 'common/ui/ModalHeader';
import ShowWhen from 'merchant/components/ShowWhen';

import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';

import {
  BATCH_UPLOAD_MSG,
  FILLED_BY_CUSTOMER,
} from 'merchant/views/PaymentPages/PaymentPages/constants';

@connect((state) => ({ countryCode: state.session.user.merchant.country_code }), {
  openModal,
  closeModal,
})
@RTracking(() => window.rzpQ.component('BaseForm'))
export default class BaseForm extends React.PureComponent {
  constructor(props) {
    super(props);
    const field = props.field;

    const name = field.item.name;
    const disableSubmit = !name;
    const hasDescription = !!field.item.description;

    this.state = {
      disableSubmit,
      hasDescription,
      mirrorDisplayName: name || '',
      isMandatory: isMandatoryToBool(field.mandatory),
    };
  }

  componentDidMount() {
    const { fieldType, field, countryCode } = this.props;

    const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];

    // if name is present and fixed price field -> focus on amount. Else focus on name field
    if (field.item.hasOwnProperty('name') && fieldType === FIELD_TYPES.fixed_price.key) {
      document.querySelector('input[name=amount]') &&
        document.querySelector('input[name=amount]').focus();
    } else {
      document.querySelector('textarea[name=name]') &&
        document.querySelector('textarea[name=name]').focus();
    }

    setTimeout(this.toggleSubmitBtn);
  }

  onChange = () => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmitBtn = () => {
    const form = this.formEl;
    const disableSubmit = !!form.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  onSaveForm = (formData) => {
    const { name, description, amount, ...restFormData } = formData;

    // Normalize data as per amount field's blueprint
    const baseFormData = {
      item: {
        name,
        description,
        amount,
      },
      ...restFormData,
    };

    this.props.onSaveForm(baseFormData);
  };

  toggleDescriptionField = (_) => {
    this.setState((prevState) => ({
      hasDescription: !prevState.hasDescription,
    }));
  };

  toggleIsMandatory = (_) => {
    const isMandatory = !this.state.isMandatory;

    this.setState((prevState) => ({
      isMandatory: !prevState.isMandatory,
    }));

    this.props.onChangeIsMandatory(isMandatory);
  };

  onInputName = ({ target }) => {
    this.setState({
      mirrorDisplayName: target.value,
    });

    track.wysiwyg.inputFieldName();
  };

  onChangeCurrency = (selectedCurrency) => {
    this.props.onUpdateCurrency(selectedCurrency.name);

    if (this.props.currency !== selectedCurrency.name) {
      this.props.openModal({
        size: 'small',
        component: (
          <div>
            <ModalHeader title="Currency change?" />

            <div class="modal-body">
              <div>
                You're changing currency from <b>{this.props.currency}</b> to{' '}
                <b>{selectedCurrency.name}</b>.
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

  getREP_Amount(isDisabled) {
    const { field, currency, isPaymentPageEditMode, isBatchPaymentPages } = this.props;
    const amount = field.item.amount || ''; // Note: If amount is there, then isDisabled = false;

    const placeholder = isDisabled
      ? isBatchPaymentPages
        ? BATCH_UPLOAD_MSG
        : FILLED_BY_CUSTOMER
      : '0.00';

    const minAmountAllowed = isDisabled
      ? ''
      : i18CurrencyConversionFromMinorUnitToCommonUnit(getCurrency(currency).min_value, currency);

    let inputField = (
      <Input
        name={isDisabled ? undefined : 'amount'}
        class="placeholder-field"
        placeholder={placeholder}
        defaultValue={amount}
        validator={(val) => validateAmount(val, minAmountAllowed, currency)}
        disabled={isDisabled}
        required={!isDisabled}
        autoRender
      />
    );

    if (isDisabled) {
      const minAmount = `${getCurrency(currency).symbol} ${Number(field.min_amount || 0).toFixed(
        2,
      )}`;
      const maxAmount = field.max_amount
        ? `${getCurrency(currency).symbol} ${Number(field.max_amount).toFixed(2)}`
        : 'No Limit';

      inputField = (
        <div class="Input">
          {inputField}
          <Popover
            align="top"
            theme="dark"
            parentQuerySelector=".Modal-content .paymentlinks-creator"
          >
            <PopoverBody>
              {isBatchPaymentPages ? BATCH_UPLOAD_MSG : 'Customers can fill custom amount'}
              <br />
              {/* TODO: As per the actual limits */}
              (Min: {minAmount}, Max: {maxAmount})
            </PopoverBody>
          </Popover>
        </div>
      );
    }

    const isEditDisabledForCurrency = isPaymentPageEditMode;

    return (
      <Input.Group class="InputGroup--inline InputGroup--full">
        <div class="Input-content">
          {field.image_url && (
            <div class="Input Input--img">
              <img src={field.image_url} />
            </div>
          )}

          <Input.CurrencySelect
            name="currency"
            defaultValue={currency}
            parentQuerySelector=".Modal-content .paymentlinks-creator"
            onChange={this.onChangeCurrency}
            disabled={isEditDisabledForCurrency}
          />
          {inputField}
        </div>
      </Input.Group>
    );
  }

  // eslint-disable-next-line getter-return, consistent-return
  get amountRepresentationForFieldType() {
    const { field, countryCode } = this.props;
    const fieldType = this.props.fieldType;

    const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];

    switch (fieldType) {
      case FIELD_TYPES.fixed_price.key:
        return (
          <React.Fragment>
            {this.getREP_Amount()}

            {/* Dummy Checkbox for optional field */}
            {!this.state.isMandatory && (
              <div class="Input-checkboxTooltip">
                <Input.Check disabled />

                <Popover
                  align="top"
                  theme="dark"
                  parentQuerySelector=".Modal-content .paymentlinks-creator"
                >
                  <PopoverBody>Customers can select or unselect this Item</PopoverBody>
                </Popover>
              </div>
            )}
          </React.Fragment>
        );

      case FIELD_TYPES.dynamic_price.key:
        return this.getREP_Amount(true);

      case FIELD_TYPES.multiple_purchase.key:
        return (
          <React.Fragment>
            {this.getREP_Amount()}

            {/* Add dummy Counter */}
            <div class="Input-counterTooltip">
              <div class="Field--counter Field--small Input--disabled">
                <div
                  class="Field-wrapper Field-wrapper--counter"
                  style={{
                    display: 'inline-block',
                    pointerEvents: 'none',
                  }}
                >
                  <button type="button" disabled>
                    -
                  </button>
                  <input class="Field-el counter-value" value={field.min_purchase} disabled />
                  <button type="button" disabled>
                    +
                  </button>
                </div>
              </div>

              <Popover
                align="top"
                theme="dark"
                parentQuerySelector=".Modal-content .paymentlinks-creator"
              >
                <PopoverBody>
                  Customers can change Item quantity
                  <br />
                  {/* TODO: As per the actual limits */}
                  (Min: {field.min_purchase}, Max: {field.max_purchase || 'No Limit'})
                </PopoverBody>
              </Popover>
            </div>
          </React.Fragment>
        );
      default:
    }
  }

  setRefForm = (el) => (this.formEl = el);

  render() {
    const {
      field,
      selfIndex,
      validateSameTitleExists,
      onCloseForm,
      onDeleteField,
      isBatchPaymentPages,
    } = this.props;

    const { hasDescription, disableSubmit, mirrorDisplayName, isMandatory } = this.state;

    return (
      <Form setRef={this.setRefForm} onChange={this.onChange} onSubmit={this.onSaveForm}>
        {/* This will automatically be controlled by both initial field and on re-render on save of Advanced Form */}
        <input name="mandatory" value={Number(isMandatory)} readOnly hidden />

        <Input.TextareaAutoResize
          class="Input--title"
          name="name"
          defaultValue={field.item.name || ''}
          maxLength="60"
          placeholder="Enter field label"
          onInput={this.onInputName}
          autoRender
          // eslint-disable-next-line consistent-return
          validator={(val) => {
            if (!val) {
              return 'Field title is required';
            }

            const regex = new RegExp(`^[0-9a-zA-Z ]+$`, 'i');

            if (!regex.test(val)) {
              return 'Please enter valid value';
            }

            if (!isNaN(val)) {
              return 'Field title must have at least 1 character';
            }

            if (validateSameTitleExists(val, selfIndex)) {
              return 'Field title cannot be same as other field';
            }
          }}
        >
          <div class={classList('Field Field--mirrorDisplay', isMandatory && 'Field--required')}>
            <span class="mirror-title">{mirrorDisplayName}</span>
            {mirrorDisplayName && !isMandatory && <div class="text-optional">(Optional)</div>}
          </div>
        </Input.TextareaAutoResize>

        <div class="Field--representation">
          {this.amountRepresentationForFieldType}

          {hasDescription && (
            <Input.TextareaAutoResize
              class="Input--description"
              name="description"
              placeholder="Enter description"
              defaultValue={field.item.description || ''}
              // eslint-disable-next-line consistent-return
              validator={(val) => {
                if (val && val.length > 128) {
                  return 'Field description cannot be more than 128 characters';
                }
              }}
              autoFocus
            />
          )}
        </div>
        <FieldOptionsDropdownWrapper
          trigger={
            <Button.Transparent>
              <i class="i i-ellipsis-v" />
            </Button.Transparent>
          }
        >
          <ShowWhen additionalCondition={() => !isBatchPaymentPages}>
            <OptionsItem>
              <div
                onClick={
                  !!field.image_url
                    ? (_) => this.props.onUpdateImage(null)
                    : this.props.openImageCropper
                }
              >
                <i class="i i-add_image" />
                {field.image_url ? 'Remove Image' : 'Add Image'}
              </div>
            </OptionsItem>
          </ShowWhen>

          <OptionsItem isSelected={!!this.state.hasDescription}>
            <div onClick={this.toggleDescriptionField}>
              <i class="i i-sort i-fix-sort" />
              {this.state.hasDescription ? 'Remove Description' : 'Add Description'}
            </div>
          </OptionsItem>

          <OptionsItem isSelected={!this.state.isMandatory}>
            <div onClick={this.toggleIsMandatory}>
              <i class="i i-optional_mark" />
              {!this.state.isMandatory ? 'Optional Item' : 'Make it Optional Item'}
            </div>
          </OptionsItem>
          <ShowWhen additionalCondition={() => !isBatchPaymentPages}>
            <OptionsItem>
              <div onClick={this.props.openAdvancedForm}>
                <i class="i i-options" />
                <div>
                  Advanced Options
                  <div class="subOption">Add quantity, define rules around quantity, etc.</div>
                </div>
              </div>
            </OptionsItem>
          </ShowWhen>

          {typeof selfIndex !== 'undefined' && onDeleteField && (
            <OptionsItem>
              <div class="OptionsDropdown-item--delete" onClick={onDeleteField}>
                <i class="i i-delete" />
                <div>Delete Field</div>
              </div>
            </OptionsItem>
          )}
        </FieldOptionsDropdownWrapper>

        <Button.Transparent
          class="base-form-side-btn base-form-cancel"
          type="button"
          onClick={onCloseForm}
        >
          <span>&times;</span>
          Cancel
        </Button.Transparent>

        <Button.Transparent
          class="base-form-side-btn base-form-save"
          type="submit"
          disabled={disableSubmit}
        >
          <span class="icon i-check" />
          Save
        </Button.Transparent>
      </Form>
    );
  }
}
