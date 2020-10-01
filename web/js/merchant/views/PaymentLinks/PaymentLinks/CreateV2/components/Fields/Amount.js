import Input from 'common/new-ui/Input';

const Amount = (props) => {
  return (
    <Input.Group
      class="InputGroup--inline InputGroup--vTop amount"
      label="Amount"
      required
      disabled={props.disabled}
    >
      <div class="Input-content">
        <Input.CurrencySelect
          autoRender
          name="currency"
          defaultValue={props.defaultCurrency}
          disabled={props.disableCurrencySelect}
          parentQuerySelector=".Modal-body"
        />
        <Input
          autoRender
          required
          name="amount"
          placeholder="0.00"
          defaultValue={props.defaultAmount}
        />
      </div>
    </Input.Group>
  );
};

export default Amount;
