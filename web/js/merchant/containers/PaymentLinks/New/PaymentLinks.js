import Input from 'component/Input';

export default [
  {
    label: 'Amount',
    required: true,
    name: 'contact_name',
  },
  {
    label: 'Payment For',
    name: 'payment_for',
    required: true,
    _cmp: Input.Textarea,
  },
  {
    _name: 'expiry',
    label: 'Expire By',
    _cmp: Input.Check,
  },
  [
    {
      name: 'date',
      type: 'tel',
      _when: form => form.state._name[form.state.activeTab].expiry === '1',
    },
    {
      name: 'time',
      type: 'tel',
      _when: form => form.state._name[form.state.activeTab].expiry === '1',
    },
  ],
  [
    {
      label: 'Notify Customer',
      name: 'contact_mobile',
      type: 'tel',
      addonBefore: '+91',
    },
    {
      name: 'contact_email',
      type: 'email',
      description: 'Notify customer either via phone or email, or both.',
    },
  ],
  {
    name: 'notes',
    label: 'Internal Notes',
  },
];
