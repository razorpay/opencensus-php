import { isEmail, isPhone } from 'rzp/utils/validators';

import Input from 'component/Input';

export default props => {
  const {
    expireAt,
    isCustomerNameRequired,
    hasNoExpiry,
    handleDateChange,
    description,
    customerName,
    customerContact,
    configSmsNotify,
    customerEmail,
    configEmailNotify,
    receipt,
  } = props;

  return (
    <React.Fragment>
      <Input.Textarea
        name="description"
        label="Description"
        value={description}
        description="Payment / Authentication Description"
        required
      />

      <Input
        required={isCustomerNameRequired}
        name="customerName"
        label="Customer Name"
        value={customerName}
        description="Name of Customer"
      />

      <Input.Group class="InputGroup--inline" label="Customer Contact" required>
        <div class="Input-content">
          <Input
            required
            name="customerContact"
            placeholder="Mobile"
            type="tel"
            value={customerContact}
            validator={validatePhone}
            description="Phone number of Customer"
          />

          <Input
            required
            name="customerEmail"
            placeholder="Email"
            type="email"
            value={customerEmail}
            validator={validateEmail}
            description="Email of Customer"
          />
        </div>
      </Input.Group>

      <Input.Group class="InputGroup--inline InputGroup--vTop" label="Notify">
        <div class="Input-content">
          <Input.Check
            name="configSmsNotify"
            checked={configSmsNotify}
            fieldLabel="Via SMS"
          />

          <Input.Check
            name="configEmailNotify"
            checked={configEmailNotify}
            fieldLabel="Via Email"
          />
        </div>
      </Input.Group>

      <Input
        name="receipt"
        label="Receipt No."
        value={receipt}
        description="Receipt for Customer"
      />

      <Input.Group label="Expiry" class="InputGroup--vTop">
        <Input.Check
          fieldLabel="No Expiry"
          name="hasNoExpiry"
          defaultValue="1"
          value={hasNoExpiry}
        />

        <Input.ToCalendar
          name="expireAt"
          placeholder="DD-MM-YYYY"
          allowToday
          disablePastDates
          placement="topLeft"
          addonAfter={<i class="i i-date-range" />}
          disabled={!!Number(hasNoExpiry)}
          defaultValue={!Number(hasNoExpiry) ? moment(expireAt) : null}
          onChange={handleDateChange('expireAt')}
          description="Expiry of Registration Link"
        />
      </Input.Group>
    </React.Fragment>
  );
};

export function validatePhone(val) {
  return !isPhone(val) && 'Invalid Phone';
}

export function validateEmail(val) {
  return !isEmail(val) && 'Invalid Email';
}
