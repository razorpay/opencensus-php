import Input from 'common/new-ui/Input';

import Amount from 'common/ui/Amount';

export default function QuantitySelector(props) {
  return (
    <div className="Subscription--New-quant-select">
      <Input
        label={
          <>
            <Amount
              value={props.rate}
              currency={props.currency}
              parentQuerySelector=".Modal-body .SubscriptionLinks--new"
            />
            <span className="m-l">x</span>
          </>
        }
        name={props.name || 'quantity'}
        type="number"
        size="half"
        value={props.quantity}
        min={1}
        onBlur={props.onBlur}
        disabled={props.disabled}
        autoRender
      />
      <span>(Quantity)</span>
      <div className="m-t">{props.informativeMessage(props.rate * props.quantity, props.currency)}</div>
    </div>
  );
}
