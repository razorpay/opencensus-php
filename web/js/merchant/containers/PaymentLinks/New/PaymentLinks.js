import Input from 'component/Input';
import { merchantFetch } from 'rzp/utils/ajax';

/* Specific Api Actions of Payment Links */
export function PLCreate() {
  const curDirtyForm = this.state.dirty[this.state.activeTab];

  const reqPayload = {
    ...curDirtyForm,
    type: 'link',
    currency: 'INR', // Get is dynamically
  };

  return merchantFetch({
    url: 'invoices',
    // mode: this.props.mode,
    method: 'post',
    data: reqPayload,
  }).then(resp => {
    return resp;
  });
}

/* Form fields of Payment Links */
export default [
  [
    {
      name: 'amount',
      label: 'Amount',
      placeholder: '0.00',
      required: true,
      addonBefore: '₹',
    },
    {
      name: 'partial_payment',
      fieldLabel: <b>Enable Partial Payment</b>,
      _cmp: Input.Check,
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
    name: 'receipt',
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
      _name: 'expire_by_date',
      type: 'tel',
      _disabledWhen: form =>
        form.state._name[form.state.activeTab].expiry === '1',
      addonAfter: <i class="i i-account" />,
    },
    {
      name: 'expire_by',
      type: 'tel',
      _disabledWhen: form =>
        form.state._name[form.state.activeTab].expiry === '1',
      addonAfter: <i class="i i-account" />,
      _when: form => {
        const expireByDateExist =
          form.state._name[form.state.activeTab].expire_by_date ||
          form.props.expire_by;

        return !!expireByDateExist;
      },
    },
  ],
  [
    {
      label: 'Notify Customer',
      name: 'contact_mobile',
      size: 'small',
      type: 'tel',
      placeholder: 'Enter 10-digit phone number',
    },
    {
      name: 'contact_email',
      type: 'email',
      placeholder: 'Enter email address',
      description: 'Notify customer either via phone or email, or both.',
    },
  ],
  {
    name: 'notes',
    label: 'Internal Notes',
  },
];
