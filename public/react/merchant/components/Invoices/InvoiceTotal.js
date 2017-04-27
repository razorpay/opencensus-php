import Amount from 'rzp/ui/Amount';

export default ({ line_items }) => {
  let subTotal = line_items.reduce(
    (total, line_item) => total + line_item.amount,
    0
  );
  let total = subTotal;

  return (
    <div class="clearfix">
      <div class="invoice-total pull-right">
        <dl class="dl-horizontal">
          <dt>SUB TOTAL:</dt>
          <dd class="text-right">
            <Amount value={subTotal} />
          </dd>

          <dt>TOTAL:</dt>
          <dd class="text-right">
            <Amount value={subTotal} />
          </dd>
        </dl>
      </div>
    </div>
  );
};
