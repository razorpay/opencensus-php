import Input from 'component/Input';
import { merchantFetch } from 'rzp/utils/ajax';

/* Specific Api Actions of Reusable Payment Links */
export function RPLCreate() {
  const curDirtyForm = this.state.dirty[this.state.activeTab];
  console.log('RPL', curDirtyForm);
}

/* Form fields of Reusable Payment Links */
export default [
  {
    name: 'amount',
    label: 'Amount',
    placeholder: '0.00',
    required: true,
    addonBefore: '₹',
  },
  [
    {
      name: 'title',
      label: 'Payment For',
      placeholder: 'Payment Title',
      required: true,
    },
    {
      name: 'description',
      placeholder: 'Provide additional description',
      description: 'This will be visible to the customer',
      _cmp: Input.Textarea,
      _when: form => form.state._name[form.state.activeTab].withDesc == '1',
    },
    {
      _name: 'withDesc',
      _cmp: Input.Check,
    },
  ],
  {
    _name: 'expiry',
    label: 'Expiry',
    fieldLabel: 'No Expiry',
    _cmp: Input.Check,
    className: 'Input--vTop',
    onChange: e => {
      if (e.target.value == '0') {
        setTimeout(
          () => document.querySelector('[data-name="expire_by_date"]').focus(),
          10
        );
      }
    },
  },
  {
    className: 'InputGroup--near',
    inlineFields: [
      {
        _name: 'expire_by_date',
        placeholder: '15-04-2018',
        size: 'half',
        _disabledWhen: form =>
          form.state._name[form.state.activeTab].expiry === '1',
        addonAfter: <i class="i i-date-range" />,
      },
      {
        name: 'expire_by',
        placeholder: '12:00AM',
        size: 'half',
        _disabledWhen: form =>
          form.state._name[form.state.activeTab].expiry === '1',
        addonAfter: <i class="i i-time" />,
        _when: form => !!form.state._name[form.state.activeTab].expire_by_date,
      },
    ],
  },
  [
    {
      _name: 'noLimit',
      label: 'Times Payable',
      fieldLabel: 'No Limit',
      _cmp: Input.Check,
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
      type: 'number',
      size: 'half',
      description:
        'Upon reaching limit, link will close. Limit can be modified anytime.',
      _disabledWhen: form =>
        form.state._name[form.state.activeTab].noLimit === '1',
    },
  ],
  {
    name: 'notes',
    label: 'Internal Notes',
    className: 'Input--vTop',
    _cmp: Input.Pair,
  },
];
