import Input from 'component/Input';
import { isAmount, isInteger, maxLength } from 'rzp/utils/validators';

/* Form fields of Payment-Pages Links */
export default [
  {
    name: 'amount',
    label: 'Amount',
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
  [
    {
      name: 'title',
      label: 'Payment For',
      placeholder: 'Payment Title',
      validator: maxLength(40),
      description: form => {
        if (form.state._name.hasDesc == '0') {
          return 'This will be visible to the customer';
        }
      },
      required: true,
    },
    {
      name: 'description',
      placeholder: 'Provide additional description',
      _cmp: Input.Textarea,
      description: form => {
        if (form.state._name.hasDesc == '1') {
          return 'This will be visible to the customer';
        }
      },
      _when: form => form.state._name.hasDesc == '1',
    },
    {
      _name: 'hasDesc',
      _cmp: Input.Check,
      checkboxMaskLabel: ['+ Add description', '- Remove description'],
      _autoRenderImpure: true,
    },
  ],
  {
    name: 'receipt',
    label: 'Receipt No.',
    validator: maxLength(40),
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
        size: 'half',
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
        _when: form => !!form.state._name.expire_by_date,
        size: 'half',
        _disabledWhen: form => form.state._name.hasNoExpiry === '1',
        addonAfter: <i class="i i-time" />,

        // defaultValue: moment().endOf().unix(), // Epoch of timestamp today end. Don't set. Has to be in sync with Date(expire_by_date).
        _cmp: Input.TimePicker,
        readOnly: true,
      },
    ],
  },
  [
    {
      _name: 'hasNoLimit',
      label: 'Times Payable',
      required: true,
      fieldLabel: 'No Limit',
      _cmp: Input.Check,
      _autoRenderImpure: true,
      className: 'Input--vTop',
      onChange: e => {
        if (e.target.value == '0') {
          setTimeout(
            () => document.getElementsByName('times_payable')[0].focus(),
            10
          );
        }
      },
    },
    {
      name: 'times_payable',
      type: 'tel',
      required: true,
      size: 'half',
      defaultValue: 0,
      onFocus: e => {
        e.target.select();
      },
      _autoRenderImpure: true,
      description:
        'Upon reaching limit, link will close. Limit can be modified anytime.',
      _disabledWhen: form => form.state._name.hasNoLimit === '1',
      validator: function(val) {
        if (this.state._name.hasNoLimit === '0') {
          if (!val) {
            return 'Please fill out this field';
          } else if (!isInteger(val)) {
            return 'Enter valid number';
          }
        }
      },
    },
  ],
  {
    name: 'notes',
    label: 'Internal Notes',
    className: 'Input--vTop',
    _cmp: Input.PairList,
  },
];
