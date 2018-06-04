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
      fieldLabel: (
        <b>
          Enable Partial Payment
          <a
            class="btn-link m-l"
            href="https://razorpay.com/docs/private/partial-payments/"
            target="_blank"
          >
            What's this?
          </a>
        </b>
      ),
      _cmp: Input.Check,
      _featureEnabled: 'Invoice_Partial_Payments',
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
        _disabledWhen: form =>
          form.state._name[form.state.activeTab].expiry === '1',
        addonAfter: <i class="i i-account" />,
      },
      {
        name: 'expire_by',
        placeholder: '12:00AM',
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
  },
  {
    label: 'Customer Details',
    inlineFields: [
      {
        name: 'contact_mobile',
        type: 'tel',
        placeholder: 'Enter mobile number',
      },
      {
        name: 'contact_email',
        type: 'email',
        placeholder: 'Enter email address',
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
        _cmp: Input.Check,
        onChange: e => {
          if (e.target.value == '1') {
            document.getElementsByName('contact_mobile')[0].focus();
          }
        },
      },
      {
        name: 'email_notify',
        fieldLabel: 'via Email',
        _cmp: Input.Check,
        onChange: e => {
          if (e.target.value == '1') {
            document.getElementsByName('contact_email')[0].focus();
          }
        },
      },
    ],
  },
  {
    name: 'notes',
    label: 'Internal Notes',
    className: 'Input--vTop',
    _cmp: Input.Pair,
  },
];
