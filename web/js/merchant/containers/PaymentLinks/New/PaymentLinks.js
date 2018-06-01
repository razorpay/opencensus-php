import Input from 'component/Input';

export default [
  {
    name: 'contact_name',
    label: 'Amount',
    placeholder: '0.00',
    required: true,
    addonBefore: '₹',
  },
  {
    name: 'payment_for',
    label: 'Payment For',
    placeholder: 'Payment Description',
    required: true,
    description: 'This will be visible to the customer',
    _cmp: Input.Textarea,
  },
  {
    name: 'partial_payment',
    fieldLabel: <b>Enable Partial Payment</b>,
    _cmp: Input.Check,
  },
  {
    name: 'receipt_no',
    label: 'Receipt No.',
  },
  {
    _name: 'expiry',
    label: 'Expiry',
    fieldLabel: 'No Expiry',
    _cmp: Input.Check,
    className: 'Input-vTop',
  },
  [
    {
      name: 'date',
      type: 'tel',
      _disabledWhen: form =>
        form.state._name[form.state.activeTab].expiry === '1',
      addonAfter: <i class="i i-account" />,
    },
    {
      name: 'time',
      type: 'tel',
      _disabledWhen: form =>
        form.state._name[form.state.activeTab].expiry === '1',
      addonAfter: <i class="i i-account" />,
    },
  ],
  [
    {
      label: 'Notify Customer',
      name: 'contact_mobile',
      size: 'small',
      type: 'tel',
      placeholder: 'Enter 10-digit phone number',
      addonBefore: <i class="i i-account" />,
    },
    {
      name: 'contact_email',
      type: 'email',
      addonBefore: <i class="i i-account" />,
      placeholder: 'Enter email address',
      description: 'Notify customer either via phone or email, or both.',
    },
  ],
  {
    name: 'notes',
    label: 'Internal Notes',
  },
];
