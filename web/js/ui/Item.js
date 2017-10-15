import { methods } from 'util/data';

const methodKeys = Object.keys(methods);

export const paymentMethod = item => methods[item.payment_method];
export const selectMethod = props => item => (
  <select {...props} defaultValue={item.method}>
    {methodKeys.map(m => (
      <option key={m} value={m}>
        {methods[m]}
      </option>
    ))}
  </select>
);
