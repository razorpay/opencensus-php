const tabs = [
  'Contact Details',
  'Business Details - 1',
  'Business Details - 2',
  'Bank Account Details',
  'Documents Upload',
]

const contactFields = [
  {
    label: 'Contact Name',
    name: 'contact_name'
  },

  {
    label: 'Contact Number',
    type: 'tel',
    name: 'contact_mobile'
  },

  {
    label: 'Contact Email',
    type: 'email',
    name: 'contact_email'
  }
]

const businessFields1 = [
  {
    label: 'Business Name',
    name: 'business_name'
  },

  {
    label: 'Doing Business As',
    name: 'business_dba'
  },

  {
    label: 'Business Type',
    type: 'select',
    name: 'business_type'
  },

  {
    label: 'Business Model',
    type: 'select',
    name: 'business_model'
  },

  {
    label: 'We want to accept International Payments as well',
    type: 'checkbox',
    name: 'business_international'
  },

  {
    label: 'CIN',
    name: 'company_cin'
  },

  {
    label: 'Business PAN Details',
    required: false,
    name: 'company_pan',
    placeholder: 'PAN Number'
  },

  {
    required: false,
    name: 'company_pan_name',
    placeholder: 'PAN Owner Name'
  },

  {
    name: 'promoter_pan',
    label: 'PAN info of Authorized Signatory/Promoter/Director',
    placeholder: 'PAN Number'
  },

  {
    name: 'promoter_pan_name',
    required: false,
    placeholder: 'PAN Owner Name'
  }
]

const businessFields2 = [
  {
    id: 'app_type',
    label: 'Website/App Details',
    type: 'radio',
    options: [
      'Website',
      'App',
      'We don\'t have either'
    ]
  },

  {
    id: 'app_desc',
    type: 'hidden',
    description: <p>
      You can still accept payments through <b>Razorpay Invoices</b>
      and <b> Razorpay Payment Links</b>. You can request access to
      other products (<b>Route</b>, <b>Subscription</b>, <b>Smart Collect</b>)
      once you have a website or app.
    </p>,
    if: function() {
      return this.state.app_type === 2
    }
  },

  {
    name: 'business_website',
    type: 'url',
    description: <p>
      Your website should have following information easily accessible:
      <b>About Us</b>, <b>Contact</b>, <b>Privacy Policy</b>,
      <b>Terms & Conditions</b>, <b>Refund Policy</b> & <b>Pricing</b>.
      Please refer our <a href='' target='_blank'>Compliance Policies</a>
      for more details.
    </p>,
    if: function() {
      return this.state.app_type !== 2
    },
    placeholder: 'Enter URL'
  },

  {
    name: 'business_registered_address',
    type: 'textarea',
    placeholder: 'Enter Street Address',
    label: 'Registered Address'
  },

  {
    name: 'business_registered_pin',
    label: 'Pincode',
    className: 'field-grouped'
  },

  {
    name: 'business_registered_city',
    label: 'City',
    className: 'field-grouped'
  },

  {
    name: 'business_registered_state',
    label: 'State',
    className: 'field-grouped'
  },

  {
    id: 'same_address',
    label: 'Operational Address same as Registered Address',
    type: 'checkbox',
    description: 'Physical verification may be performed at this address'
  },

  {
    name: 'business_operation_address',
    label: 'Operational Address',
    type: 'textarea',
    placeholder: 'Enter Street Address',
    if: ifDifferentAddress
  },

  {
    name: 'business_operation_pin',
    label: 'Pincode',
    if: ifDifferentAddress,
    className: 'field-grouped'
  },

  {
    name: 'business_operation_city',
    label: 'City',
    if: ifDifferentAddress,
    className: 'field-grouped'
  },

  {
    name: 'business_operation_state',
    label: 'State',
    if: ifDifferentAddress,
    className: 'field-grouped'
  },

  {
    id: 'has_gstin',
    label: 'GSTIN',
    type: 'radio',
    options: [
      'We have a registered GSTIN',
      'We don\'t have a GSTIN'
    ]
  },

  {
    name: 'gstin',
    if: function() { return !this.state.has_gstin }
  }
]

const bankAccountFields = [
  {
    name: 'bank_branch_ifsc',
    label: 'Branch IFSC Code'
  },

  {
    name: 'bank_account_number',
    label: 'Account Number',
    type: 'password'
  },

  {
    id: 'account_no',
    label: 'Re-Enter Account Number'
  },

  {
    name: 'bank_account_name',
    label: 'Beneficiary Name'
  }
]

const uploadFields = [
  {
    name: 'business_proof_url',
    label: 'Business Registration Proof',
    type: 'file'
  },

  {
    name: 'business_pan_url',
    label: 'Business PAN',
    type: 'file'
  },

  {
    name: 'address_proof_url',
    label: 'Company\'s Bank Account Statement with Address',
    type: 'file'
  },

  {
    name: 'promoter_address_url',
    label: 'Authorized Signatory\'s Address Proof',
    type: 'file'
  }
]

function ifDifferentAddress() {
  return !this.state.same_address;
}

const tabContent = [
  contactFields,
  businessFields1,
  businessFields2,
  bankAccountFields,
  uploadFields
]

// default values
tabContent.forEach(t => t.forEach(field => {
  if (!field.hasOwnProperty('required')) {
    field.required = true;
  }
  if (!field.hasOwnProperty('type')) {
    field.type = 'text';
  }
}))

export default class ActivationWizard extends React.Component {
  state = {
    activeTab: 4,
    data: {},
    same_address: 1,
    app_type: 0,
    has_gstin: 0,
    account_no: ''
  }

  changeTab = ({ target }) => {
    this.setState({
      activeTab: parseInt(target.getAttribute('data-index'))
    })
  }

  onChange = ({ target }) => {
    let name = target.name;
    let value;

    if (target.type === 'checkbox') {
      value = !!target.checked;
    } else if (target.type === 'radio') {
      value = parseInt(target.getAttribute('data-index'));
    }

    let id = target.getAttribute('data-id');
    if (id) {
      this.setState({
        [id]: value
      })
    } else {
      this.setState({
        data: {
          ...this.state.data,
          [name]: value
        }
      })
    }
  }

  onDragEnter = ({ target }) => {
    target.className = 'drop-active';
  }

  onDragLeave = ({ target }) => {
    target.className = '';
  }

  render() {
    let {
      activeTab,
      data
    } = this.state;

    return <div class="activation-wizard" onDrop={this.onDrop}>
      <aside>
        <side-title>Account Activation</side-title>
        <p>
          Fill and submit the activation form to start
          transacting live from your Razorpay account.
        </p>
        <ul>
          {tabs.map((t, i) => <li
            class={i === activeTab ? 'active' : ''}
            key={i}
            data-index={i}
            onClick={this.changeTab}
          >{t}</li>)}
        </ul>
      </aside>
      <main>
        <main-title>{tabs[activeTab]}</main-title>
        {tabContent[activeTab].map((field, i) => {
          if (field.if && !field.if.call(this)) {
            return;
          }

          let InputTag, stateValue, idName;
          let {
            type,
            required,
            name,
            id,
            label,
            className
          } = field;

          if (name) {
            idName = name;
            stateValue = data[name];
          } else {
            idName = id;
            stateValue = this.state[idName];
          }

          let wrapperClass = `activation-field field-${type}`;

          let inputId = `activation-field-${idName}`;

          if (label) {
            wrapperClass += ' has-label';
          }

          if (className) {
            wrapperClass += ' ' + className;
          }

          if (required) {
            wrapperClass += ' is-required';
          }

          if (type === 'select' || type === 'textarea') {
            InputTag = type;
            type = null;
          } else {
            InputTag = 'input';
          }

          let isFile = type === 'file';

          let Input = <InputTag
            defaultChecked={type === 'checkbox' && this.state[id]}
            name={name}
            data-id={id}
            id={inputId}
            required={required}
            type={type}
            onChange={this.onChange}
            placeholder={field.placeholder || ''}>
          </InputTag>

          return <div key={idName} class={wrapperClass}>
            {type === 'radio' && field.options.map((f, i) => (
              <div class='field-radio' key={i}>
                <input
                  id={`${idName}-${i}`}
                  key={i}
                  onChange={this.onChange}
                  type='radio'
                  name={idName}
                  data-id={id}
                  data-index={i}
                  defaultChecked={i === stateValue}
                />
                <label for={`${idName}-${i}`}>{f}</label>
              </div>
              )) || (isFile ? <div
                class='input-placeholder'
                onDragEnter={this.onDragEnter}
                onDragLeave={this.onDragLeave}
              >{Input}</div> : Input)
            }

            {label && <label for={inputId}>{label}</label>}

            {field.description}
          </div>
        })}
      </main>
    </div>
  }
}
