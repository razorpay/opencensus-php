import Input from 'component/Input';

import { checkIfAmount } from './utils';

export default ({ amount }) => (
  <React.Fragment>
    <Input.Group class="InputGroup--inline" label="Amount">
      <div class="Input-content">
        <Input
          required
          name="amount"
          type="tel"
          placeholder="0.00"
          description="Amount of Registration Link Payment"
          value={amount}
          validator={checkIfAmount}
          size="half_big"
          class="Input--Amount"
        />
      </div>
    </Input.Group>
  </React.Fragment>
);
