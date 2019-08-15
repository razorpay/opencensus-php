import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList, getFormattedAmount } from 'common/util';
import Amount, { AmountTooltip } from 'rzp/ui/Amount';
import { AdvancedFormModal } from './CreatorManager';

/* TODO: This component contains all the variations of displaying Amount
*   1. Fixed Amount
*   2. Fixed Amount with checkbox(mandatory)
*   3. Dynamic Amount Field
*   3. Amount with Counter
* */
export default class BaseForm extends React.PureComponent {
  constructor(props) {
    super(props);
    const field = props.field;
    const isAmountEntitySet = field.hasOwnProperty('amount');

    this.state = {
      isAdvancedFormOpened: false,
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

  toggleAdvancedForm = forcedState => {
    const isAdvancedFormOpened =
      typeof forcedState !== 'undefined'
        ? forcedState
        : !this.state.isAdvancedFormOpened;

    this.setState({
      isAdvancedFormOpened,
    });
  };

  onSaveAdvancedForm = formData => {
    console.log('ADVANCED FORM...', formDta);
  };

  render() {
    const { onClose, onSubmit, field, field_type_key } = this.props;
    const {
      hasDynamicAmount,
      allowMultipleUnits,
      hasQuantity,
      disableSubmit,
      isAdvancedFormOpened,
    } = this.state;

    const isCurrencyChangeDisabled = !!this.props.field.id;
    // console.log('...', this.props.field);

    return (
      <React.Fragment>
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
        </Form>

        {isAdvancedFormOpened && (
          <AdvancedFormModal
            field={field}
            field_type_key={field_type_key}
            onSubmit={this.onSaveAdvancedForm}
            closeFormModal={_ => this.toggleAdvancedForm(false)}
          />
        )}
      </React.Fragment>
    );
  }
}
