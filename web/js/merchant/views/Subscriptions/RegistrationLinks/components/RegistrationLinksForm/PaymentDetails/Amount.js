import Input from 'common/new-ui/Input';
import { AmountTooltip } from 'common/ui/Amount';

export default function Amount({
  amount,
  onBlurElement,
  placeholder,
  amountValidator,
  currency,
  ...props
}) {
  return (
    <Input.Group className="InputGroup--inline" label="Authorisation Amount">
      <div className="Input-content">
        <Input
          required
          name="amount"
          type="number"
          placeholder={placeholder}
          value={amount}
          validator={amountValidator}
          size="half_big"
          className="Input--Amount"
          onBlur={onBlurElement}
          data-name="amount"
          addonBefore={<AmountTooltip currency={currency} parentQuerySelector=".Modal" />}
          {...props}
        />
      </div>
    </Input.Group>
  );
}
