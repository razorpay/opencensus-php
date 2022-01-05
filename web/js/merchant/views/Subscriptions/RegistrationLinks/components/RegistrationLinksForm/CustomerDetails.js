import moment from 'moment';
import { isEmail, isPhone } from 'common/utils/validators';

import Input from 'common/new-ui/Input';

export default function CustomerDetailsForm(props) {
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
    onBlurElement,
  } = props;

  return (
    <>
      <Input.Textarea
        name="description"
        data-name="description"
        label="Description"
        value={description}
        description="Payment / Authentication Description"
        required
        onBlur={onBlurElement}
      />

      <Input
        required={isCustomerNameRequired}
        name="customerName"
        label="Customer Name"
        data-name="customer_name"
        value={customerName}
        onBlur={onBlurElement}
      />

      <Input.Group class="InputGroup--inline" label="Customer Contact" required>
        <div class="Input-content">
          <Input
            required
            name="customerContact"
            placeholder="Mobile"
            data-name="customer_contact"
            type="tel"
            value={customerContact}
            validator={validatePhone}
            description="Phone number of Customer"
            onBlur={onBlurElement}
          />

          <Input
            required
            name="customerEmail"
            placeholder="Email"
            data-name="customer_email"
            type="email"
            value={customerEmail}
            validator={validateEmail}
            description="Email of Customer"
            onBlur={onBlurElement}
          />
        </div>
      </Input.Group>

      <Input.Group class="InputGroup--inline InputGroup--vTop" label="Notify">
        <div class="Input-content">
          <Input.Check
            name="configSmsNotify"
            checked={configSmsNotify}
            fieldLabel="Via SMS"
            data-name="notify_sms"
            onBlur={onBlurElement}
          />

          <Input.Check
            name="configEmailNotify"
            checked={configEmailNotify}
            fieldLabel="Via Email"
            data-name="notify_email"
            onBlur={onBlurElement}
          />
        </div>
      </Input.Group>

      <Input
        name="receipt"
        label="Receipt No."
        value={receipt}
        description="Receipt for Customer"
        data-name="receipt"
        onBlur={onBlurElement}
      />

      <Input.Group label="Registration Link Expiry" class="InputGroup--vTop">
        <Input.Check
          fieldLabel="No Expiry"
          name="hasNoExpiry"
          checked={hasNoExpiry}
          data-name="expiry"
          onBlur={onBlurElement}
        />

        <Input.ToCalendar
          allowToday
          disablePastDates
          name="expireAt"
          placeholder="Expiry (DD-MM-YYYY)"
          placement="topLeft"
          addonAfter={<i class="i i-date-range" />}
          disabled={hasNoExpiry}
          defaultValue={!hasNoExpiry && expireAt ? moment(expireAt, 'X') : null}
          onChange={handleDateChange('expireAt')}
          data-name="expiry-date"
          onBlur={() => onBlurElement(null, 'expiry-date')}
        />
      </Input.Group>
    </>
  );
}

function validatePhone(val) {
  return !isPhone(val) && 'Invalid Phone';
}

function validateEmail(val) {
  return !isEmail(val) && 'Invalid Email';
}
