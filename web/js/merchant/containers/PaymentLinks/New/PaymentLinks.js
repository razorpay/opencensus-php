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

  reqPayload.amount *= 100;
  reqPayload.amount *= 100;
  reqPayload.expire_by = Math.round(reqPayload.expire_by / 1000);

  /* Customer details */
  const customer = {};
  if (reqPayload.contact) {
    customer.contact = reqPayload.contact;
    delete reqPayload.contact;
  }

  if (reqPayload.email) {
    customer.email = reqPayload.email;
    delete reqPayload.email;
  }

  if (Object.keys(customer).length) {
    reqPayload.customer = customer;
  }

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
    label: 'Customer Details',
    inlineFields: [
      {
        name: 'contact',
        type: 'tel',
        placeholder: 'Mobile (10 digits)',
        size: 'half_small',
      },
      {
        name: 'email',
        type: 'email',
        placeholder: 'Email',
        size: 'half_big',
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
        size: 'half_small',
        _cmp: Input.Check,
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
  },
  {
    _name: 'expiry',
    label: 'Expiry',
    fieldLabel: 'No Expiry',
    _cmp: Input.Check,
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
        placeholder: '15-04-2018',
        size: 'half',
        _disabledWhen: form =>
          form.state._name[form.state.activeTab].expiry === '1',
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
        size: 'half',
        _when: form => !!form.state._name[form.state.activeTab].expire_by_date,
        _disabledWhen: form =>
          form.state._name[form.state.activeTab].expiry === '1',
        addonAfter: <i class="i i-time" />,

        // defaultValue: moment().startOf().unix(), // Epoch of timestamp today start. Don't set. Has to be in sync with Date(expiry_by_date).
        _cmp: Input.TimePicker,
        readOnly: true,
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
