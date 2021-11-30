import Input from 'common/new-ui/Input';
import { validateAmount } from 'common/utils/validators';

import track from '../../track';
import { FORM_CLASS_NAME } from '../FormWizard';

const Amount = (props) => {
  return (
    <Input.Group
      class="InputGroup--inline InputGroup--vTop amount"
      label="Amount"
      required
      disabled={props.disabled}
    >
      <div class="Input-content pt-8">
        <Input.CurrencySelect
          autoRender
          name="currency"
          defaultValue={props.defaultCurrency}
          disabled={props.disableCurrencySelect}
          parentQuerySelector=".Modal-body"
          onChange={track.lj.fields.currency}
        />
        <Input
          autoRender
          required
          name="amount"
          placeholder="0.00"
          defaultValue={props.defaultAmount}
          validator={amountValidator}
          onBlur={callTrackers(props)}
        />
      </div>
    </Input.Group>
  );
};

function amountValidator(value) {
  return validateAmount(value);
}

function callTrackers(props) {
  return () => {
    track.lj.fields.amount({
      modified: props.isIntentDuplicate,
    });

    const invalidAmountField = document.querySelector(
      `.${FORM_CLASS_NAME} .amount .Input.Input--required.is-invalid`,
    );
    if (invalidAmountField) {
      track.lj.form.fail({ response: 'Please fill out this field' });
    }
  };
}

export default Amount;
