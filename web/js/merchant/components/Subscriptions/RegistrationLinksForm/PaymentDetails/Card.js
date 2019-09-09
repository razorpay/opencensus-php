import Input from 'component/Input';

import { checkIfAmount } from './utils';

export default ({ amount }) => (
  <React.Fragment>
    <Input.Group class="InputGroup--inline" label="Amount">
      <div class="Input-content">
        <Input.CurrencySelect name="currency" />

        <Input
          name="amount"
          type="tel"
          placeholder="0.00"
          description="Amount of Registration Link Payment"
          validator={checkIfAmount}
          value={amount}
          required
        />
      </div>
    </Input.Group>
  </React.Fragment>
);
