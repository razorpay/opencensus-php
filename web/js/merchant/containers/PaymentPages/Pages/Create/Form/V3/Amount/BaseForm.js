import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList, getFormattedAmount } from 'common/util';
import Amount, { AmountTooltip } from 'rzp/ui/Amount';
import EditLayer from '../../../EditLayer';

export class BaseForm extends React.PureComponent {
  constructor(props) {
    super(props);
    const field = props.field;
    const isAmountEntitySet = field.hasOwnProperty('amount');

    this.state = {
      hasDynamicAmount: isAmountEntitySet ? !field.amount : false,
      quantity: isAmountEntitySet ? field.quantity : '',
      hasQuantity: isAmountEntitySet ? !!field.quantity | 0 : false,
      disableSubmit: !isAmountEntitySet,
      allowMultipleUnits: isAmountEntitySet
        ? field.settings.allow_multiple_units
        : false,
    };

    this.defaults = {
      amount: isAmountEntitySet ? field.amount : '',
    };
  }

  onChange = ({ target }) => {
    const { name, value } = target;
    const stateName = target.getAttribute('data-name');

    if (stateName === 'has_dynamic_amount') {
      this.setState({
        hasDynamicAmount: value == 1 ? true : false,
        allowMultipleUnits: false,
        hasQuantity: 0,
      });

      document.getElementsByName('amount')[0].value = '';
    } else if (name === 'allow_multiple_units') {
      this.setState({
        allowMultipleUnits: target.checked,
      });
    } else if (stateName === 'has_quantity') {
      this.setState({
        hasQuantity: value == 1 ? true : false,
      });
    }

    setTimeout(() => {
      const form = document.getElementsByName('form_creator_amount')[0];
      const amount = document.getElementsByName('amount')[0].value;

      const disableSubmit =
        form.querySelectorAll('.is-invalid').length ||
        (!amount && !this.state.hasDynamicAmount);
      this.setState({ disableSubmit });
    });
  };

  render() {
    const { onClose, onSubmit } = this.props;
    const {
      hasDynamicAmount,
      allowMultipleUnits,
      hasQuantity,
      disableSubmit,
    } = this.state;

    const isCurrencyChangeDisabled = !!this.props.field.id;
    // console.log('...', this.props.field);

    return (
      <Form
        name="form_creator_amount"
        onChange={this.onChange}
        onSubmit={onSubmit}
      >
        <div class="section section-1">
          <Input.Group class="InputGroup--inline" label="Amount">
            <div class="Input-content">
              <Input.CurrencySelect
                name="currency"
                disabled={isCurrencyChangeDisabled}
                defaultValue={this.props.field.currency}
                parentQuerySelector=".Modal-body"
              />

              <Input
                name="amount"
                class="Input--amount"
                placeholder="0.00"
                defaultValue={this.defaults.amount}
                autoFocus
                pattern="^[0-9]+(.([0-9]){1,2})?$"
                disabled={hasDynamicAmount}
              />
            </div>
          </Input.Group>
          <Input.Check
            data-name="has_dynamic_amount"
            fieldLabel="Customer decides this while paying"
            defaultValue={!!this.state.hasDynamicAmount | 0}
          />
        </div>
        <div class="section section-2">
          <Input.Check
            name="allow_multiple_units"
            fieldLabel="Allow multiple purchases per customer"
            disabled={hasDynamicAmount}
            checked={allowMultipleUnits}
            autoRender
          />
          <Input.Check
            data-name="has_quantity"
            autoRender={true}
            disabled={hasDynamicAmount}
            checked={Boolean(hasQuantity)}
            fieldLabel={() => (
              <span>
                {hasQuantity
                  ? 'This item has'
                  : 'This item has limited quantity'}{' '}
                {!!hasQuantity && (
                  <React.Fragment>
                    <Input
                      name="quantity"
                      defaultValue={this.state.quantity}
                      class="checkbox-Input"
                      autoFocus
                      step="1"
                      pattern="\d+"
                    />{' '}
                    units available
                  </React.Fragment>
                )}
              </span>
            )}
          />
        </div>
        <footer>
          <div class="group-right">
            <button type="button" class="btn-link" onClick={onClose}>
              Cancel
            </button>
            <Button.Primary type="submit" disabled={disableSubmit}>
              Add
            </Button.Primary>
          </div>
        </footer>
      </Form>
    );
  }
}
