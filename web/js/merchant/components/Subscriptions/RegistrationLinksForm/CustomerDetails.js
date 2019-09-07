import { isEmail, isPhone } from 'rzp/utils/validators';

import Input from 'component/Input';

export default props => {
  const { hasNoExpiry, handleDateChange } = props;

  return (
    <React.Fragment>
      <Input.Textarea
        name="description"
        label="Description"
        description="Payment / Authentication Description"
        required
      />

      <Input
        name="customerName"
        label="Customer Name"
        description="Name of Customer"
      />

      <Input.Group class="InputGroup--inline" label="Customer Contact" required>
        <div class="Input-content">
          <Input
            name="customerContact"
            placeholder="Mobile"
            type="tel"
            size="half_big"
            required
            validator={val => !isPhone(val) && 'Invalid Phone'}
            description="Phone number of Customer"
          />

          <Input
            name="customerEmail"
            placeholder="Email"
            type="email"
            size="half_big"
            required
            validator={val => !isEmail(val) && 'Invalid Email'}
            description="Email of Customer"
          />
        </div>
      </Input.Group>

      <Input.Group class="InputGroup--inline InputGroup--vTop" label="Notify">
        <div class="Input-content">
          <Input.Check name="configSmsNotify" fieldLabel="Via SMS" />

          <Input.Check name="configEmailNotify" fieldLabel="Via Email" />
        </div>
      </Input.Group>

      <Input
        name="receipt"
        size="half_big"
        label="Receipt No."
        description="Receipt for Customer"
      />

      <Input.Group label="Expiry" class="InputGroup--vTop">
        <Input.Check
          fieldLabel="No Expiry"
          data-name="hasNoExpiry"
          defaultValue="1"
        />

        <Input.ToCalendar
          name="expireAt"
          placeholder="DD-MM-YYYY"
          allowToday
          disablePastDates
          placement="topLeft"
          size="half_big"
          addonAfter={<i class="i i-date-range" />}
          disabled={!!Number(hasNoExpiry)}
          onChange={handleDateChange('expireAt')}
          description="Expiry of Registration Link"
        />
      </Input.Group>
    </React.Fragment>
  );
};
