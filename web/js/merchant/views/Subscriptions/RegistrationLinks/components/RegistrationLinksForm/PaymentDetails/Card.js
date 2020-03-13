import Input from 'common/new-ui/Input';

import { checkIfAmount } from './utils';

export default ({ amount, onBlurElement }) => (
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
          onBlur={onBlurElement}
          data-name="amount"
        />
      </div>
    </Input.Group>
  </React.Fragment>
);
