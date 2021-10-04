import { Link } from 'react-router-dom';

import Input from 'common/new-ui/Input';

import { isAmount, isEmail, isPhone, maxLength } from 'common/utils/validators';

import Popover from 'common/ui/Popover';

import DocsLink from 'merchant/components/DocsLink';

import { trackSelectCurrency } from '../ga';

import {
  MIN_AMOUNT_TEXT,
  PopoverBodyText,
  validateMinAmount,
} from '../components/Edit/EditMinimumAmount';

const CustomInput = (props) => {
  return (
    <div class="Input--custom">
      <div class="Input-label">
        {MIN_AMOUNT_TEXT} (Optional)
        <small className="help-content">
          <i class="i i-info-outline" style={{ marginLeft: 4 }} />
          <Popover align="top" parentQuerySelector=".Modal-body .PaymentLinks--Create">
            {PopoverBodyText}
          </Popover>
        </small>
      </div>

      <Input.Group class="InputGroup--inline">
        <div class="Input-content">
          <Input.CurrencySelect
            name="currency"
            defaultValue="INR"
            disabled
            parentQuerySelector=".Modal-body"
          />

          <Input {...props} />
        </div>
      </Input.Group>
    </div>
  );
};

/* Form fields of Payment Links */
export default [
  {
    label: 'Amount',
    required: true,
    inlineFields: [
      {
        name: 'currency',
        _cmp: Input.CurrencySelect,
        onChange: (currency) => {
          if (currency) {
            trackSelectCurrency(currency.name);
          }
          // track 'Dashboard - International - Payment links'	'select currency'
        },
        parentQuerySelector: '.Modal-body',
      },
      {
        name: 'amount',
        type: 'tel',
        placeholder: '0.00',
        required: true,
        autoFocus: true,
        validator: (val) => {
          if (!isAmount(val)) {
            const decimal = val && val.split('.');

            if (decimal.length == 2 && decimal[1].length > 2) {
              return 'Enter upto 2 decimals';
            } else {
              return 'Invalid Amount';
            }
          }
          return undefined;
        },
      },
    ],
  },
  [
    {
      name: 'partial_payment',
      fieldLabel: <span>Enable Partial Payment</span>,
      _cmp: Input.Check,
      _autoRenderImpure: true,
    },
    {
      name: 'first_payment_min_amount',
      placeholder: '0.00',
      size: 'half_big',
      _autoRenderImpure: true,
      _cmp: CustomInput,
      validator: function validator(val) {
        return validateMinAmount(val, this.state.dirty.amount);
      },
      _when: function _when(form) {
        return (
          form.state.dirty.partial_payment == '1' && form.props.user.isMinimumFirstPaymentEnabled
        );
      },
    },
  ],
  {
    name: 'description',
    label: (form) => getPaymentLinkFormLabel('description', form.props.user),
    placeholder: (form) => getPaymentLinkFormPlaceholder('description', form.props.user),
    required: function required(ctx) {
      return !ctx.props.user.isPaymentlinksV2Enabled; // In new PL micro service, it's not mandatory
    },
    description: 'This will be visible to the customer',
    _cmp: Input.Textarea,
  },
  {
    label: 'Customer Details',
    inlineFields: [
      {
        name: 'contact',
        type: 'tel',
        placeholder: 'Mobile',
        size: 'half_big',
        validator: (val) => {
          if (!isPhone(val)) {
            return 'Invalid phone';
          }
          return undefined;
        },
      },
      {
        name: 'email',
        type: 'email',
        placeholder: 'Email',
        size: 'half_big',
        validator: (val) => {
          if (!isEmail(val)) {
            return 'Invalid email';
          }
          return undefined;
        },
      },
    ],
  },
  {
    label: 'Notify',
    className: 'InputGroup--vTop InputGroup--near',
    inlineFields: [
      {
        name: 'sms_notify',
        fieldLabel: 'via SMS',
        description: () => (
          <DocsLink
            title="More ways to notify"
            url="https://razorpay.com/app-store/"
            style={{ paddingLeft: '0' }}
          />
        ),
        size: 'half_big',
        _cmp: Input.Check,
        _autoRenderImpure: true,
        onChange: (e) => {
          if (e.target.value == '1') {
            document.getElementsByName('contact')[0].focus();
          }
        },
      },
      {
        name: 'email_notify',
        fieldLabel: 'via Email',
        size: 'half_big',
        _cmp: Input.Check,
        _autoRenderImpure: true,
        onChange: (e) => {
          if (e.target.value == '1') {
            document.getElementsByName('email')[0].focus();
          }
        },
      },
    ],
  },
  {
    name: 'customer_name',
    type: 'text',
    placeholder: 'Customer Name',
    label: 'Customer Name',
    _when: (form) => form.props.user.isPaymentLinkCustomerNameFieldEnabled,
  },
  {
    name: 'receipt',
    label: (form) => getPaymentLinkFormLabel('receipt', form.props.user),
    validator: maxLength(40),
    size: 'half_big',
  },
  {
    _name: 'hasNoExpiry',
    label: 'Expire On',
    fieldLabel: 'No Expiry',
    _cmp: Input.Check,
    _when: function _when(ctx) {
      return !ctx.props.user.isExpireByRequired;
    },
    _autoRenderImpure: true,
    className: 'Input--vTop',
    onChange: (e) => {
      if (e.target.value == '0') {
        // 0 => unselected
        setTimeout(() => {
          document.querySelector('[data-name="expire_by_date"]').focus();
          document.querySelector('[data-name="expire_by_date"]').click();
        }, 10);
      }
    },
  },
  {
    className: function className(ctx) {
      return ctx.props.user.isExpireByRequired ? null : 'InputGroup--near';
    },
    label: function label(ctx) {
      return ctx.props.user.isExpireByRequired ? 'Expire On' : null;
    },
    required: function required(ctx) {
      return ctx.props.user.isExpireByRequired;
    },
    inlineFields: [
      {
        _name: 'expire_by_date',
        placeholder: 'DD-MM-YYYY',
        size: 'half_big',
        _disabledWhen: (form) => form.state._name.hasNoExpiry === '1',
        addonAfter: <i class="i i-date-range" />,

        _cmp: Input.ToCalendar,
        allowToday: true,
        disablePastDates: true,
        placement: 'topLeft',
        readOnly: true,
        required: function required(ctx) {
          return ctx.props.user.isExpireByRequired;
        },
      },
      {
        name: 'expire_by',
        placeholder: '11:59PM',
        size: 'half_big',
        _when: (form) => !!form.state._name.expire_by_date,
        _disabledWhen: (form) => form.state._name.hasNoExpiry === '1',
        addonAfter: <i class="i i-time" />,

        // defaultValue: moment().endOf().unix(), // Epoch of timestamp today end. Don't set. Has to be in sync with Date(expire_by_date).
        _cmp: Input.TimePicker,
        readOnly: true,
        required: function required(ctx) {
          return ctx.props.user.isExpireByRequired;
        },
      },
    ],
  },
  {
    className: 'InputGroup--vTop',
    name: 'reminder_enable',
    fieldLabel: 'Send auto reminders',
    description: ({ props, state }) => {
      return getRemindersOptionDescription(
        props.paymentLinksRemindersSettings.count,
        Number(state._name.hasNoExpiry),
      );
    },
    _cmp: Input.Check,
    label: 'Reminders',
    _when: function _when(form) {
      return form.props.paymentLinksRemindersSettings.isEnabled;
    },
    _autoRenderImpure: true,
  },
  {
    label: 'Reminders',
    _cmp: () => <ReminderNotEnabled />,
    _when: function _when(form) {
      return (
        !form.props.paymentLinksRemindersSettings.isEnabled && form.state._name.hasNoExpiry === '0'
      );
    },
  },
  {
    label: 'Reminders',
    _cmp: () => {
      return <ReminderNotEnabled type="no" />;
    },
    _when: function _when(form) {
      return (
        !form.props.paymentLinksRemindersSettings.isEnabled && form.state._name.hasNoExpiry === '1'
      );
    },
  },
  {
    name: 'notes',
    label: 'Internal Notes',
    className: 'Input--vTop',
    _cmp: Input.PairList,
    _when: function _when(form) {
      return !form.props.user.isCustomNotesDropdownEnabled;
    },
  },
  {
    name: 'notes',
    label: function label(ctx) {
      return ctx.props.user.isCustomNotesDropdownEnabled && getCustomNotesOptions().type;
    },
    _cmp: Input.Select,
    options: function options(ctx) {
      return ctx.props.user.isCustomNotesDropdownEnabled && getCustomNotesOptions().options;
    },
    _when: function _when(form) {
      return form.props.user.isCustomNotesDropdownEnabled;
    },
  },
];

const ReminderNotEnabled = ({ type = '' }) => (
  <div class="Input">
    <div class="Input-label">Reminders</div>
    <div class="Input-content">
      Reminders is not set to payment links with {type} expiry date.
      <br />
      Set it up{' '}
      <Link target="_blank" to="/reminders">
        here
      </Link>
    </div>
  </div>
);

function getRemindersOptionDescription(count, hasNoExpiry) {
  const totalReminders = hasNoExpiry
    ? count.withOutExpireRemindersCount
    : count.withExpireRemindersCount;

  return `${totalReminders} auto reminders will be sent to this customer based on the reminder settings`;
}

export function getCustomNotesOptions() {
  const { type, options } = window.custom_notes;

  return {
    type,
    options: [{ label: 'Select A Value', value: '' }, ...options],
  };
}

function getPaymentLinkFormLabel(fieldName, user) {
  return user.getPaymentLinkCustomizedFormFields[fieldName].label;
}

function getPaymentLinkFormPlaceholder(fieldName, user) {
  return user.getPaymentLinkCustomizedFormFields[fieldName].placeholder;
}
