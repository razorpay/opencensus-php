import Form from 'component/Form';
import Button from 'component/Button';
import Input from 'component/Input';
import { mapFieldToAmountFieldType } from '../../Amount_Fields/V3';
import FIELD_TYPES from '../../Amount_Fields/fieldTypes';

export default class AdvancedForm extends React.PureComponent {
  constructor(props) {
    super(props);

    const field = props.field;

    this.state = {
      disableSubmit: false,
      hasQuantity: !!field && typeof field.quantity_available !== 'undefined',
    };
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
    // console.log('formData...', formData);
    this.props.onSaveForm(formData);
  };

  toggleAddQuantity = data => {
    console.log('data.....', data);

    this.setState({
      hasQuantity: !this.state.hasQuantity,
    });
  };

  get FIELD_availableQuantity() {
    const { field } = this.props;
    const { hasQuantity } = this.state;
    const quantityAvailable = field ? this.props.field.quantity_available : '';

    return (
      <Input.Radio
        className="Input--hasQuantity Input--vTop"
        label={() => (
          <div style={{ fontWeight: 'normal' }}>
            Available Quantity
            <div class="modal-description">in stock</div>
          </div>
        )}
        onChange={this.toggleAddQuantity}
        options={[
          'Unlimited',
          {
            label: (
              <div class="Input--quantity">
                <span>Limited</span>
                {hasQuantity && (
                  <Input
                    name="quantity_available"
                    defaultValue={quantityAvailable}
                    autoFocus
                    step="1"
                    pattern="\d+"
                  />
                )}
              </div>
            ),
          },
        ]}
        defaultValue={hasQuantity}
      />
    );
  }

  get fieldsForOptionalFixedPrice() {
    return <React.Fragment />;
  }

  get fieldsForDynamicPrice() {
    return <React.Fragment />;
  }

  get fieldsForMultiplePurchase() {
    return <React.Fragment />;
  }

  get fieldsForFieldType() {
    const fieldType = this.props.fieldType;

    console.log('FIELD TYPE...', this.props.fieldType);

    switch (fieldType) {
      // Same Advanced Form for both fixed_price and fixed_price_optional
      case FIELD_TYPES.fixed_price.key:
      case FIELD_TYPES.fixed_price_optional.key:
        return this.FIELD_availableQuantity;

      case FIELD_TYPES.dynamic_price.key:
        return this.fieldsForDynamicPrice;
      case FIELD_TYPES.multiple_purchase.key:
        return this.fieldsForMultiplePurchase;
    }
  }

  setRefForm = el => (this.formEl = el);

  render() {
    const { field, onCloseForm } = this.props;
    const { disableSubmit } = this.state;

    console.log('FIELD...', field);

    return (
      <div>
        <div class="modal-title">Advanced Options</div>
        <div class="modal-description">
          Add quantity, define rules around quantity and price, etc.
        </div>

        <hr />

        <Form
          setRef={this.setRefForm}
          onChange={this.onChange}
          onSubmit={this.onSaveForm}
        >
          {this.fieldsForFieldType}

          <footer>
            <Button.Transparent class="" type="button" onClick={onCloseForm}>
              Cancel
            </Button.Transparent>

            <Button.Primary type="submit" disabled={disableSubmit}>
              Add
            </Button.Primary>
          </footer>
        </Form>
      </div>
    );
  }
}
