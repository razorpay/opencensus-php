import Input from 'common/new-ui/Input';
import { AmountTooltip } from 'common/ui/Amount';

import { checkIfAmount } from './utils';

export default ({ amount, onBlurElement, placeholder, ...props }) => (
  <React.Fragment>
    <Input.Group class="InputGroup--inline" label="Amount">
      <div class="Input-content">
        <Input
          required
          name="amount"
          type="number"
          placeholder={placeholder}
          description="Amount of Registration Link Payment"
          value={amount}
          validator={checkIfAmount}
          size="half_big"
          class="Input--Amount"
          onBlur={onBlurElement}
          data-name="amount"
          addonBefore={
            <AmountTooltip currency={'INR'} parentQuerySelector=".Modal" />
          }
          {...props}
        />
      </div>
    </Input.Group>
  </React.Fragment>
);
