import { isEmail, isPhone } from 'rzp/utils/validators';

import Input from 'component/Input';

export default function NewSubscriptionLinkLinkDetails(props) {
  return (
    <>
      <Input.Group class="InputGroup--inline" label="Customer Contact">
        <div class="Input-content">
          <Input
            placeholder="Mobile"
            name="notify_info.notify_phone"
            type="tel"
            size="half"
            validator={val => !isPhone(val) && 'Invalid Phone'}
          />

          <Input
            placeholder="Email"
            name="notify_info.notify_email"
            type="email"
            size="half"
            validator={val => !isEmail(val) && 'Invalid Email'}
          />
        </div>
      </Input.Group>

      <Input.Group class="InputGroup--near">
        <Input.Check fieldLabel="Notify Customer" name="customer_notify" />
      </Input.Group>

      <Input.Check
        label="Link Expiry"
        fieldLabel="No Expiry"
        data-name="_isNonExpiringLink"
      />
      <Input.Group class="InputGroup--inline InputGroup--near">
        <div class="Input-content">
          <Input.ToCalendar
            placeholder="DD-MM-YYYY"
            allowToday
            disablePastDates
            size="half"
            addonAfter={<i class="i i-date-range" />}
            placement="topLeft"
            disabled={false}
            name="expire_by"
            onChange={props.onDateChange('expire_by')}
            disabled={props.disableExpireBy}
          />

          {props.showTimeInput && (
            <Input.TimePicker
              name="startAtTime"
              placeholder="HH:MM A"
              addonAfter={<i class="i i-time" />}
              name="expire_by_time"
              onChange={props.onTimeChange('expire_by_time')}
              disabled={props.disableExpireBy}
            />
          )}
        </div>
      </Input.Group>

      <Input.PairList
        name="notes"
        label="Internal Notes"
        class="Input--vTop"
        onChange={props.onNotesChange}
      />
    </>
  );
}
