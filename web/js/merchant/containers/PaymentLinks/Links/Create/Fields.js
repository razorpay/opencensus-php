import Input from 'component/Input';
import { trackHelpClick } from '../ga';
import { isAmount, isEmail, isPhone, maxLength } from 'rzp/utils/validators';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import {
  MIN_AMOUNT_TEXT,
  PopoverBodyText,
  validateMinAmount,
} from '../../Edit/EditMinimumAmount';
import ShowWhen from 'merchant/components/ShowWhen';

const CustomInput = props => {
  return (
    <div class="Input--custom">
      <div class="Input-label">
        {MIN_AMOUNT_TEXT} (Optional)
        <small className="help-content">
          <i class="i i-info-outline" style={{ marginLeft: 4 }} />
          <Popover
            align="top"
            parentQuerySelector={`.Modal-body .PaymentLinks--Create`}
          >
            {PopoverBodyText}
          </Popover>
        </small>
      </div>
      <Input {...props} />
    </div>
  );
};

/* Form fields of Payment Links */
export default [
  [
    {
      name: 'amount',
      label: 'Amount',
      type: 'tel',
      placeholder: '0.00',
      required: true,
      addonBefore: '₹',
      autoFocus: true,
      validator: val => {
        if (!isAmount(val)) {
          const decimal = val && val.split('.');

          if (decimal.length == 2 && decimal[1].length > 2) {
            return 'Enter upto 2 decimals';
          } else {
            return 'Invalid Amount';
          }
        }
      },
    },
    {
      name: 'partial_payment',
      fieldLabel: (
        <span>
          Enable Partial Payment
          <ShowWhen
            additionalCondition={user =>
              user.isOrgAllowedFunctionality('external_links')
            }
          >
            <a
              class="btn-link m-l"
              href="https://razorpay.com/docs/payment-links/partial-payments/"
              target="_blank"
              onClick={trackHelpClick}
            >
              (What's this?)
            </a>
          </ShowWhen>
        </span>
      ),
      _cmp: Input.Check,
      _autoRenderImpure: true,
    },
    {
      name: 'first_payment_min_amount',
      addonBefore: '₹',
      placeholder: '0.00',
      size: 'half_big',
      _autoRenderImpure: true,
      _cmp: CustomInput,
      validator: function(val) {
        return validateMinAmount(val, this.state.dirty.amount);
      },
      _when: function(form) {
        return (
          form.state.dirty.partial_payment == '1' &&
          form.props.user.isMinimumFirstPaymentEnabled
        );
      },
    },
  ],
  {
    name: 'description',
    label: 'Payment For',
    placeholder: 'Payment Description',
    required: true,
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
        validator: val => {
          if (!isPhone(val)) {
            return 'Invalid phone';
          }
        },
      },
      {
        name: 'email',
        type: 'email',
        placeholder: 'Email',
        size: 'half_big',
        validator: val => {
          if (!isEmail(val)) {
            return 'Invalid email';
          }
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
        size: 'half_big',
        _cmp: Input.Check,
        _autoRenderImpure: true,
        onChange: e => {
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
        onChange: e => {
          if (e.target.value == '1') {
            document.getElementsByName('email')[0].focus();
          }
        },
      },
    ],
  },
  {
    name: 'receipt',
    label: 'Receipt No.',
    validator: maxLength(40),
    size: 'half_big',
  },
  {
    _name: 'hasNoExpiry',
    label: 'Expire On',
    fieldLabel: 'No Expiry',
    _cmp: Input.Check,
    _autoRenderImpure: true,
    className: 'Input--vTop',
    onChange: e => {
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
    className: 'InputGroup--near',
    inlineFields: [
      {
        _name: 'expire_by_date',
        placeholder: 'DD-MM-YYYY',
        size: 'half_big',
        _disabledWhen: form => form.state._name.hasNoExpiry === '1',
        addonAfter: <i class="i i-date-range" />,

        _cmp: Input.ToCalendar,
        allowToday: true,
        disablePastDates: true,
        placement: 'topLeft',
        readOnly: true,
      },
      {
        name: 'expire_by',
        placeholder: '11:59PM',
        size: 'half_big',
        _when: form => !!form.state._name.expire_by_date,
        _disabledWhen: form => form.state._name.hasNoExpiry === '1',
        addonAfter: <i class="i i-time" />,

        // defaultValue: moment().endOf().unix(), // Epoch of timestamp today end. Don't set. Has to be in sync with Date(expire_by_date).
        _cmp: Input.TimePicker,
        readOnly: true,
      },
    ],
  },
  {
    name: 'notes',
    label: 'Internal Notes',
    className: 'Input--vTop',
    _cmp: Input.PairList,
  },
];
