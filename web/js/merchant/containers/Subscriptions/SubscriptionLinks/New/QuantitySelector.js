import Input from 'component/Input';

import Amount from 'rzp/ui/Amount';

export default function QuantitySelector(props) {
  return (
    <div class="quantity-selector">
      <Input
        label={
          <>
            <Amount value={props.rate} currency={props.currency} />
            &nbsp;x&nbsp;
          </>
        }
        name={props.name || 'quantity'}
        type="number"
        size="half"
        value={props.quantity}
      />
      {props.informativeMessage(props.rate * props.quantity, props.currency)}
    </div>
  );
}
