import { isEmail, isPhone } from 'common/utils/validators';
import moment from 'moment';
import Input from 'common/new-ui/Input';
import analytics from '../../analytics';

export default function NewSubscriptionLinkDetails(props) {
  const { fields, internals, cloneOptions, onDateChange, onTimeChange } = props;
  const dateInMoment = fields.expire_by ? moment(fields.expire_by, 'X') : undefined;

  return (
    <>
      <Input.Group
        className="InputGroup--inline"
        label="Customer Contact"
        required={fields.customer_notify}
      >
        <div className="Input-content">
          <Input
            placeholder="Email"
            name="notify_info.notify_email"
            type="email"
            size="half"
            validator={(val) => !isEmail(val) && 'Invalid Email'}
            defaultValue={(fields.notify_info || {}).notify_email}
            onBlur={() => {
              analytics.track('subscription.create.authlink_email', cloneOptions);
            }}
          />

          <Input
            placeholder="Mobile"
            name="notify_info.notify_phone"
            type="tel"
            size="half"
            validator={(val) => !isPhone(val) && 'Invalid Phone'}
            defaultValue={(fields.notify_info || {}).notify_phone}
            onBlur={() => {
              analytics.track('subscription.create.authlink_mobile', cloneOptions);
            }}
          />
        </div>
      </Input.Group>

      <Input.Group className="InputGroup--near">
        <Input.Check
          fieldLabel="Notify Customer"
          name="customer_notify"
          checked={fields.customer_notify}
          onBlur={() => {
            analytics.track('subscription.create.authlink_notify', cloneOptions);
          }}
        />
      </Input.Group>

      <Input.Check
        label="Link Expiry"
        fieldLabel="No Expiry"
        data-name="_isNonExpiringLink"
        checked={internals._isNonExpiringLink}
        required
        onBlur={() => {
          analytics.track('subscription.create.authlink_expiry_no', cloneOptions);
        }}
      />
      <Input.Group className="InputGroup--inline InputGroup--near">
        <div className="Input-content">
          <Input.ToCalendar
            placeholder="DD-MM-YYYY"
            allowToday
            disablePastDates
            size="half"
            addonAfter={<i className="i i-date-range" />}
            placement="topLeft"
            name="expire_by"
            onChange={onDateChange('expire_by')}
            disabled={internals._isNonExpiringLink}
            defaultValue={dateInMoment}
            readOnly
            onBlur={() => {
              analytics.track('subscription.create.authlink_expiry_date', cloneOptions);
            }}
          />

          {!!fields.expire_by && (
            <Input.TimePicker
              placeholder="HH:MM A"
              addonAfter={<i className="i i-time" />}
              name="expire_by_time"
              onChange={onTimeChange('expire_by_time')}
              disabled={internals._isNonExpiringLink}
              defaultValue={dateInMoment}
              readOnly
              onBlur={() => {
                analytics.track('subscription.create.authlink_expiry_time', cloneOptions);
              }}
            />
          )}
        </div>
      </Input.Group>

      <Input.PairList
        name="notes"
        label="Internal Notes"
        className="Input--vTop"
        labelClass="Input-label"
        defaultValue={fields.notes}
        onChange={(_, field) => {
          analytics.track(
            `subscription.create.${
              field === 'key' ? 'authlink_notes_key' : 'authlink_notes_value'
            }`,
            cloneOptions,
          );
        }}
        onAddNew={() => {
          analytics.track('subscription.create.authlink_add_notes', cloneOptions);
        }}
      />
    </>
  );
}
