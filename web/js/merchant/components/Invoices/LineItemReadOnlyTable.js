import Amount from 'rzp/ui/Amount';
import InvoiceTotal from './InvoiceTotal';

export default ({ line_items }) => {
  let subTotal = line_items.reduce(
    (total, line_item) => total + line_item.amount,
    0
  );
  let total = subTotal;

  return (
    <div class="invoice-lineitem">
      <table class="table">
        <thead>
          <tr>
            <th>Name/Description</th>
            <th style={{ width: '12%' }} class="text-right">
              Quantity
            </th>
            <th style={{ width: '18%' }} class="text-right">
              Rate
            </th>
            <th class="text-right">Amount</th>
          </tr>
        </thead>
        <tbody>
          {line_items.map(line_item => (
            <tr key={line_item.id}>
              <td>
                <p>{line_item.name}</p>
                <div>{line_item.description}</div>
              </td>

              <td class="text-right">{line_item.quantity}</td>
              <td class="text-right">
                <Amount value={line_item.amount} />
              </td>
              <td class="text-right">
                <Amount value={line_item.quantity * line_item.amount} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      <InvoiceTotal line_items={line_items} />
    </div>
  );
};
