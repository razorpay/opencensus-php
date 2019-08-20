import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList } from 'common/util';
import { mapFieldToAmountFieldType } from '../../Amount_Fields/V3';
import FIELD_TYPES, {
  fieldTypesWithMandatory,
} from '../../Amount_Fields/fieldTypes';
import FieldOptionsDropdown, { OptionsItem } from '../../FieldOptionsDropdown';
import Popover, { PopoverBody } from 'rzp/ui/Popover';

export default class BaseForm extends React.PureComponent {
  constructor(props) {
    super(props);
    const field = props.field;

    const title = field.item.title,
      disableSubmit = !title,
      hasDescription = !!field.item.description,
      isMandatory =
        typeof field.mandatory === 'undefined'
          ? fieldTypesWithMandatory.indexOf(props.fieldType) > -1
          : !!field.mandatory,
      imageUrl = field.image_url || '';

    this.state = {
      disableSubmit,
      hasDescription,
      mirrorDisplayTitle: title || '',
      isMandatory,
      imageUrl,
    };

    this.fieldType = props.fieldType || mapFieldToAmountFieldType(field);
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
    this.props.onSaveForm(formData);
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

  getREP_Amount(isDisabled) {
    const { currency, field } = this.props;
    const amount = field.item.amount || ''; // Note: If amount is there, then isDisabled = false;
    const placeholder = isDisabled ? 'To be filled by customer' : '0.00';

    return (
      <Input.Group
        class={classList(
          'InputGroup--inline',
          isDisabled && 'InputGroup--full'
        )}
      >
        <div class="Input-content Input-content--limits">
          <Input.CurrencySelect
            name="currency"
            defaultValue={currency}
            parentQuerySelector=".Modal-container"
          />

          <Input
            name={isDisabled ? '' : 'amount'}
            class="Input--limits placeholder-field"
            placeholder={placeholder}
            defaultValue={amount}
            pattern="^[0-9]+(.([0-9]){1,2})?$"
            disabled={isDisabled}
            required={!isDisabled}
          />
        </div>
      </Input.Group>
    );
  }

  get amountRepresentationForFieldType() {
    const fieldType = this.props.fieldType;

    console.log('FIELD TYPE...', this.fieldType);

    switch (fieldType) {
      // Same Advanced Form for both fixed_price and fixed_price_optional
      case FIELD_TYPES.fixed_price.key:
        return this.getREP_Amount();

      case FIELD_TYPES.fixed_price_optional.key:
        return (
          <React.Fragment>
            {this.getREP_Amount()}
            {/* Add dummy Checkbox */}
            <div class="Input-checkboxTooltip">
              <Input.Check disabled checked />

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
                <span style={{ margin: '0 10px' }}>×</span>
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
                    className="Field-el counter-value"
                    name="field_1"
                    defaultValue="1"
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
                  {/* TODO As per the actual limits */}
                  (Min: 0, Max: Unlimited)
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
      currency,
    } = this.props;

    const {
      imageUrl,
      isMandatory,
      hasDescription,
      disableSubmit,
      mirrorDisplayTitle,
    } = this.state;

    console.log('FIELD...', field);

    return (
      <Form
        setRef={this.setRefForm}
        onChange={this.onChange}
        onSubmit={this.onSaveForm}
      >
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
              isMandatory && 'Field--required'
            )}
          >
            <span class="mirror-title">{mirrorDisplayTitle}</span>
            {mirrorDisplayTitle && <span className="symbol--red">*</span>}
          </div>
        </Input.TextareaAutoResize>

        <input name="mandatory" value={isMandatory | 0} hidden readOnly />
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
          <OptionsItem isSelected={!!this.state.isMandatory}>
            <div onClick={this.toggleImage}>
              <i class="i i-info-circle" />
              {this.state.hasDescription ? 'Remove' : 'Add'} Image
            </div>
          </OptionsItem>

          <OptionsItem isSelected={!!this.state.hasDescription}>
            <div onClick={this.toggleDescriptionField}>
              <i class="i i-info-circle" />
              {this.state.hasDescription ? 'Remove' : 'Add'} Description
            </div>
          </OptionsItem>

          <OptionsItem>
            <div onClick={this.props.openAdvancedForm}>
              <i class="i i-info-circle" />
              Advanced Options
            </div>
          </OptionsItem>

          {typeof selfIndex !== 'undefined' &&
            onDeleteField && (
              <OptionsItem>
                <div onClick={this.onDeleteField}>
                  <i class="i i-delete" />
                  Delete Field
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
