import Input from 'common/new-ui/Input';
import { validateAmount } from 'common/utils/validators';

const PaymentHandleAmount = (props) => {
  return (
    <Input.Group
      className="InputGroup--inline InputGroup--vTop amount"
      label="Amount (optional)"
      required={props.required}
      disabled={props.disabled}
    >
      <div className="Input-content pt-8">
        <Input.CurrencySelect
          autoRender
          name="currency"
          defaultValue="INR"
          disabled
          parentQuerySelector=".modal-body"
        />
        <Input
          autoRender
          name="amount"
          placeholder="0.00"
          defaultValue={props.defaultAmount}
          validator={validateAmount}
          onChange={props.onChange}
        />
      </div>
    </Input.Group>
  );
};

export default PaymentHandleAmount;
