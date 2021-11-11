import { isEmail, isPhone } from 'common/utils/validators';

import Input from 'common/new-ui/Input';

export default function NewSubscriptionLinkLinkDetails({
  fields,
  internals,
  ...props
}) {
  const dateInMoment = fields.expire_by
    ? moment(fields.expire_by, 'X')
    : undefined;

  return (
    <>
      <Input.Group
        class="InputGroup--inline"
        label="Customer Contact"
        required={fields.customer_notify}
      >
        <div class="Input-content">
          <Input
            placeholder="Email"
            name="notify_info.notify_email"
            type="email"
            size="half"
            validator={val => !isEmail(val) && 'Invalid Email'}
            defaultValue={(fields.notify_info || {}).notify_email}
          />

          <Input
            placeholder="Mobile"
            name="notify_info.notify_phone"
            type="tel"
            size="half"
            validator={val => !isPhone(val) && 'Invalid Phone'}
            defaultValue={(fields.notify_info || {}).notify_phone}
          />
        </div>
      </Input.Group>

      <Input.Group class="InputGroup--near">
        <Input.Check
          fieldLabel="Notify Customer"
          name="customer_notify"
          checked={fields.customer_notify}
        />
      </Input.Group>

      <Input.Check
        label="Link Expiry"
        fieldLabel="No Expiry"
        data-name="_isNonExpiringLink"
        checked={internals._isNonExpiringLink}
        required
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
            name="expire_by"
            onChange={props.onDateChange('expire_by')}
            disabled={internals._isNonExpiringLink}
            defaultValue={dateInMoment}
            readOnly
          />

          {!!fields.expire_by && (
            <Input.TimePicker
              placeholder="HH:MM A"
              addonAfter={<i class="i i-time" />}
              name="expire_by_time"
              onChange={props.onTimeChange('expire_by_time')}
              disabled={internals._isNonExpiringLink}
              defaultValue={dateInMoment}
              readOnly
            />
          )}
        </div>
      </Input.Group>

      <Input.PairList
        name="notes"
        label="Internal Notes"
        class="Input--vTop"
        defaultValue={fields.notes}
        onChange={() => {}}
      />
    </>
  );
}
