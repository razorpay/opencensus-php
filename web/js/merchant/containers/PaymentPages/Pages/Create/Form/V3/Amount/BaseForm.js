import { connect } from 'react-redux';
import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList } from 'common/util';
import { mapFieldToAmountFieldType } from '../../Amount_Fields/V3';
import FIELD_TYPES from '../../Amount_Fields/fieldTypes';
import FieldOptionsDropdown, { OptionsItem } from '../../FieldOptionsDropdown';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import { openModal, closeModal } from 'rzp/modules/modals';

import { getCurrency } from 'rzp/ui/Amount';

import ModalHeader from 'rzp/ui/ModalHeader';

@connect(null, {
  openModal,
  closeModal,
})
export default class BaseForm extends React.PureComponent {
  constructor(props) {
    super(props);
    const field = props.field;

    const title = field.item.title,
      disableSubmit = !title,
      hasDescription = !!field.item.description,
      imageUrl = field.image_url || '';

    this.state = {
      disableSubmit,
      hasDescription,
      mirrorDisplayTitle: title || '',
      imageUrl,
    };

    this.fieldType = props.fieldType || mapFieldToAmountFieldType(field);
  }

  get isMandatory() {
    const isMandatory =
      typeof this.props.field.mandatory === 'boolean'
        ? this.props.field.mandatory
        : Boolean(Number(this.props.field.mandatory));

    return isMandatory;
  }

  onChange = ({ target }) => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmitBtn = () => {
    const form = this.formEl;
    let disableSubmit = !!form.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  onSaveForm = formData => {
    const { title, description, amount, ...restFormData } = formData;

    // Normalize data as per amount field's blueprint
    const baseFormData = {
      item: {
        title,
        description,
        amount,
      },
      ...restFormData,
    };

    this.props.onSaveForm(baseFormData);
  };

  toggleDescriptionField = _ => {
    this.setState({
      hasDescription: !this.state.hasDescription,
    });
  };

  toggleImage = _ => {
    const toAddImage = !this.state.imageUrl;

    if (toAddImage) {
      // 1. Open modal to add image
      // 2. After success imageUrl in state
    } else {
      this.setState({
        imageUrl: '',
      });
    }
  };

  onDeleteField = _ => {
    this.props.onDeleteField();
  };

  onInputTitle = ({ target }) => {
    this.setState({
      mirrorDisplayTitle: target.value,
    });
  };

  onChangeCurrency = selectedCurrency => {
    if (this.props.currency !== selectedCurrency.name) {
      this.props.openModal({
        size: 'small',
        component: (
          <div>
            <ModalHeader title="Currency change?" />

            <div className="modal-body">
              <div>
                You're changing currency from <b>{this.props.currency}</b> to{' '}
                <b>{selectedCurrency.name}</b>.
              </div>
              <div>
                On saving this item, this currency will apply to all items on
                this page.
              </div>
              <br />
              <footer>
                <Button.Primary
                  class="Button--full-width"
                  onClick={this.props.closeModal}
                >
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
    const { field, currency } = this.props;
    const amount = field.item.amount || ''; // Note: If amount is there, then isDisabled = false;
    const placeholder = isDisabled ? 'To be filled by customer' : '0.00';

    let inputField = (
      <Input
        name={isDisabled ? '' : 'amount'}
        class="placeholder-field"
        placeholder={placeholder}
        defaultValue={amount}
        pattern="^[0-9]+(.([0-9]){1,2})?$"
        disabled={isDisabled}
        required={!isDisabled}
      />
    );

    if (isDisabled) {
      const minAmount = `${getCurrency(currency).symbol} ${Number(
        field.min_amount
      ).toFixed(2)}`;
      const maxAmount = field.max_amount
        ? `${getCurrency(currency).symbol} ${Number(field.max_amount).toFixed(
            2
          )}`
        : 'No Limit';

      inputField = (
        <div className="Input">
          {inputField}
          <Popover
            align="top"
            theme="dark"
            parentQuerySelector=".Modal-container"
          >
            <PopoverBody>
              Customer can fill custom amount
              <br />
              {/* TODO: As per the actual limits */}
              (Min: {minAmount}, Max: {maxAmount})
            </PopoverBody>
          </Popover>
        </div>
      );
    }

    return (
      <Input.Group class="InputGroup--inline InputGroup--full">
        <div class="Input-content">
          <Input.CurrencySelect
            name="currency"
            defaultValue={currency}
            parentQuerySelector=".Modal-container"
            onChange={this.onChangeCurrency}
          />

          {inputField}
        </div>
      </Input.Group>
    );
  }

  get amountRepresentationForFieldType() {
    const { field } = this.props;
    const fieldType = this.fieldType;

    switch (fieldType) {
      // Same Advanced Form for both fixed_price and fixed_price_optional
      case FIELD_TYPES.fixed_price.key:
        return this.getREP_Amount();

      case FIELD_TYPES.fixed_price_optional.key:
        return (
          <React.Fragment>
            {this.getREP_Amount()}
            {/* Dummy Checkbox for optional field */}
            <div class="Input-checkboxTooltip">
              <Input.Check disabled />

              <Popover
                align="top"
                theme="dark"
                parentQuerySelector=".Modal-container"
              >
                <PopoverBody>Customer can unselect Item</PopoverBody>
              </Popover>
            </div>
          </React.Fragment>
        );

      case FIELD_TYPES.dynamic_price.key:
        return this.getREP_Amount(true);

      case FIELD_TYPES.multiple_purchase.key:
        return (
          <React.Fragment>
            {this.getREP_Amount()}

            {/* Add dummy Counter */}
            <div className="Input-counterTooltip">
              <div className="Field--counter Field--small Input--disabled">
                <div
                  className="Field-wrapper Field-wrapper--counter"
                  style={{
                    display: 'inline-block',
                    pointerEvents: 'none',
                  }}
                >
                  <button type="button" disabled>
                    -
                  </button>
                  <input
                    class="Field-el counter-value"
                    value={field.min_purchase}
                    disabled
                  />
                  <button type="button" disabled>
                    +
                  </button>
                </div>
              </div>

              <Popover
                align="top"
                theme="dark"
                parentQuerySelector=".Modal-container"
              >
                <PopoverBody>
                  Customer can change Item quantity
                  <br />
                  {/* TODO: As per the actual limits */}
                  (Min: {field.min_purchase}, Max:{' '}
                  {field.max_purchase || 'No Limit'})
                </PopoverBody>
              </Popover>
            </div>
          </React.Fragment>
        );
    }
  }

  setRefForm = el => (this.formEl = el);

  render() {
    const {
      field,
      selfIndex,
      validateSameTitleExists,
      onCloseForm,
      onDeleteField,
    } = this.props;

    const {
      imageUrl,
      hasDescription,
      disableSubmit,
      mirrorDisplayTitle,
    } = this.state;

    return (
      <Form
        setRef={this.setRefForm}
        onChange={this.onChange}
        onSubmit={this.onSaveForm}
      >
        {/* This will automatically be controlled by both initial field and on re-render on save of Advanced Form */}
        <input
          name="mandatory"
          value={Number(this.isMandatory)}
          readOnly
          hidden
        />

        <Input.TextareaAutoResize
          class="Input--title"
          name="title"
          defaultValue={field.item.title || ''}
          placeholder="Enter field title"
          pattern="^[0-9a-zA-Z ]+"
          onInput={this.onInputTitle}
          validator={function(val) {
            if (!val) {
              return 'Field title is required';
            }

            if (!isNaN(val)) {
              return 'Field title must have atleast 1 character';
            }

            if (validateSameTitleExists(val, selfIndex)) {
              return 'Field title cannot be same as other field';
            }
          }}
          autoFocus
        >
          <div
            class={classList(
              'Field Field--mirrorDisplay',
              this.isMandatory && 'Field--required'
            )}
          >
            <span class="mirror-title">{mirrorDisplayTitle}</span>
            {mirrorDisplayTitle && <span class="symbol--red">*</span>}
          </div>
        </Input.TextareaAutoResize>

        <input name="image_url" value={imageUrl} hidden readOnly />

        <div class="Field--representation">
          {this.amountRepresentationForFieldType}

          {hasDescription && (
            <Input.TextareaAutoResize
              class="Input--description"
              name="description"
              placeholder="Enter description"
              defaultValue={field.item.description || ''}
              validator={val => {
                if (val && val.length > 128) {
                  return 'Field description cannot be more than 128 characters';
                }
              }}
              autoFocus
            />
          )}
        </div>

        <FieldOptionsDropdown
          trigger={
            <Button.Transparent>
              <i class="i i-ellipsis-v" />
            </Button.Transparent>
          }
        >
          <OptionsItem isSelected={!!this.state.imageUrl}>
            <div onClick={this.toggleImage}>
              <i class="i i-add_image" />
              {this.state.imageUrl ? 'Remove Image' : 'Add Image'}
            </div>
          </OptionsItem>

          <OptionsItem isSelected={!!this.state.hasDescription}>
            <div onClick={this.toggleDescriptionField}>
              <i class="i i-alphabet_underline" />
              {this.state.hasDescription
                ? 'Remove Description'
                : 'Add Description'}
            </div>
          </OptionsItem>

          <OptionsItem>
            <div onClick={this.props.openAdvancedForm}>
              <i class="i i-options" />
              <div>
                Advanced Options
                <div class="subOption">
                  Add quantity, define rules around quantity, etc.
                </div>
              </div>
            </div>
          </OptionsItem>

          {typeof selfIndex !== 'undefined' &&
            onDeleteField && (
              <OptionsItem>
                <div
                  class="OptionsDropdown-item--delete"
                  onClick={this.onDeleteField}
                >
                  <i class="i i-delete" />
                  <div>Delete Field</div>
                </div>
              </OptionsItem>
            )}
        </FieldOptionsDropdown>

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
