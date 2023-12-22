import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import Button from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { getCurrency } from 'common/ui/Amount';
import ModalHeader from 'common/ui/ModalHeader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { classList, i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import { validateAmount } from 'common/utils/validators';
import {
  isMandatoryToBool,
  isFormItemOfTypeLateFee,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import FIELD_TYPES_MAP, {
  LATE_FEE_FIELD_TYPES,
  LATE_FEE_TYPES_MAP,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';
import {
  BATCH_UPLOAD_MSG,
  FILLED_BY_CUSTOMER,
} from 'merchant/views/PaymentPages/PaymentPages/constants';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import AdditionalOptions from './AdditionalOptions';

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
      selectedLateFeeType: field?.settings?.late_fee_config?.late_fee_type || '',
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
    const { onSaveForm, field } = this.props;
    const { name, description, amount, lateFeeType, ...restFormData } = formData;

    // Normalize data as per amount field's blueprint
    const baseFormData = {
      item: {
        name,
        description,
        amount,
      },
      settings: {},
      ...restFormData,
    };

    if (isFormItemOfTypeLateFee(field)) {
      baseFormData.settings.late_fee_config = {
        ...field?.settings?.late_fee_config,
        late_fee_type: lateFeeType,
      };
    }

    onSaveForm(baseFormData);
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

  setLateFeeType = (lateFeeType) => {
    this.setState({ selectedLateFeeType: lateFeeType });
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
                You&apos;re changing currency from <b>{this.props.currency}</b> to{' '}
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

      case LATE_FEE_FIELD_TYPES.flat_fee.key:
      case LATE_FEE_FIELD_TYPES.per_day_fee.key:
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
      onUpdateImage,
      openImageCropper,
      openAdvancedForm,
    } = this.props;

    const { hasDescription, disableSubmit, mirrorDisplayName, isMandatory, selectedLateFeeType } =
      this.state;

    return (
      <Form setRef={this.setRefForm} onChange={this.onChange} onSubmit={this.onSaveForm}>
        {/* This will automatically be controlled by both initial field and on re-render on save of Advanced Form */}
        <input name="mandatory" value={Number(isMandatory)} readOnly hidden />

        {isFormItemOfTypeLateFee(field) && (
          <input name="lateFeeType" value={selectedLateFeeType} readOnly hidden />
        )}

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

            <div className="text-optional-wrap">
              {mirrorDisplayName && !isMandatory && <div className="text-optional">(Optional)</div>}

              {mirrorDisplayName && isFormItemOfTypeLateFee(field) && (
                <div className="text-optional">{`(${LATE_FEE_TYPES_MAP[selectedLateFeeType]})`}</div>
              )}
            </div>
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

        <AdditionalOptions
          isBatchPaymentPages={isBatchPaymentPages}
          field={field}
          disableSubmit={disableSubmit}
          hasDescription={hasDescription}
          isMandatory={isMandatory}
          selectedLateFeeType={selectedLateFeeType}
          selfIndex={selfIndex}
          openImageCropper={openImageCropper}
          onUpdateImage={onUpdateImage}
          onCloseForm={onCloseForm}
          openAdvancedForm={openAdvancedForm}
          onDeleteField={onDeleteField}
          setLateFeeType={this.setLateFeeType}
          toggleDescriptionField={this.toggleDescriptionField}
          toggleIsMandatory={this.toggleIsMandatory}
        />
      </Form>
    );
  }
}
