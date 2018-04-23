import Input from 'component/Input';
import Form from 'component/Form';
import Button from 'component/Button';

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
    name: 'contact_mobile',
    type: 'tel',
    addonBefore: '+91'
  },
  {
    label: 'Contact Email',
    name: 'contact_email',
    type: 'email'
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
    name: 'business_type',
    _cmp: Input.Select,
    options: []
  },
  {
    label: 'Business Model',
    name: 'business_model',
    _cmp: Input.Select,
    options: []
  },
  {
    label: 'We want to accept International Payments as well',
    name: 'business_international',
    _cmp: Input.Check
  },
  {
    label: 'CIN',
    name: 'company_cin'
  },
  {
    label: 'Business PAN Details',
    name: 'company_pan',
    placeholder: 'PAN Number'
  },
  {
    label: 'PAN Owner Name',
    name: 'company_pan_name'
  },
  {
    label: 'PAN info of Authorized Signatory/Promoter/Director',
    name: 'promoter_pan',
    placeholder: 'PAN Number'
  },
  {
    label: 'PAN Owner Name',
    name: 'promoter_pan_name'
  }
]

const differentAddress = activation => activation.state.same_address === '0'

const businessFields2 = [
  [{
    label: 'Website/App Details',
    _cmp: Input.Radio,
    _name: 'app_type',
    options: [
      'Website',
      'App',
      {
        label: 'We don\'t have either',
        description: <React.Fragment>
          You can still accept payments through <b>Razorpay Invoices</b> and
          <b> Razorpay Payment Links</b>. You can request access to
          other products (<b>Route</b>, <b>Subscription</b>, <b>Smart Collect</b>)
          once you have a website or app.
        </React.Fragment>
      }
    ]
  },
  {
    name: 'business_website',
    placeholder: 'Enter URL',
    type: 'url',
    required: false,
    description: <React.Fragment>
      Your website should have following information easily accessible:
      <b> About Us</b>,<b> Contact</b>,<b> Privacy Policy</b>,
      <b> Terms & Conditions</b>, <b>Refund Policy</b> & <b>Pricing</b>.
      Please refer our <a href='' target='_blank'>Compliance Policies </a>
      for more details.
    </React.Fragment>,
    _when: activation => activation.state.app_type !== '2',
  }],
  [{
    name: 'business_registered_address',
    placeholder: 'Enter Street Address',
    label: 'Registered Address',
    _cmp: Input.Textarea
  },
  {
    name: 'business_registered_pin',
    type: 'number',
    label: 'Pincode'
  },
  {
    name: 'business_registered_city',
    label: 'City'
  },
  {
    name: 'business_registered_state',
    label: 'State'
  }],
  {
    _name: 'same_address',
    label: 'Operational Address same as Registered Address',
    description: 'Physical verification may be performed at this address',
    _cmp: Input.Check
  },
  [{
    name: 'business_operation_address',
    placeholder: 'Enter Street Address',
    label: 'Registered Address',
    _cmp: Input.Textarea,
    _when: differentAddress
  },
  {
    name: 'business_operation_pin',
    type: 'number',
    label: 'Pincode',
    _when: differentAddress
  },
  {
    name: 'business_operation_city',
    label: 'City',
    _when: differentAddress
  },
  {
    name: 'business_operation_state',
    label: 'State',
    _when: differentAddress
  }],
  {
    _name: 'has_gstin',
    label: 'GSTIN',
    options: [
      'We have a registered GSTIN',
      'We don\'t have a GSTIN'
    ],
    _cmp: Input.Radio
  },
  {
    name: 'gstin',
    _when: activation => activation.state.has_gstin === '0'
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
    _name: 'account_no',
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
    label: 'Business Registration Proof'
  },
  {
    name: 'business_pan_url',
    label: 'Business PAN'
  },
  {
    name: 'address_proof_url',
    label: "Company's Bank Account Statement with Address"
  },
  {
    name: 'promoter_address_url',
    label: "Authorized Signatory's Address Proof"
  }
]
uploadFields.forEach(a => a._cmp = Input.File)

const tabContent = [
  contactFields,
  businessFields1,
  businessFields2,
  bankAccountFields,
  uploadFields
]

const defaultFieldProps = f => {
  if (Array.isArray(f)) {
    return f.forEach(defaultFieldProps);
  }
  if (!f._cmp) {
    f._cmp = Input;
  }
  if (!f.hasOwnProperty('required')) {
    f.required = true;
  }
}

defaultFieldProps(tabContent);

export default class ActivationWizard extends React.Component {
  state = {
    data: this.props.data || {
      contact_email: 'pranav@gmail.com',
      contact_mobile: '8875242434',
      contact_name: 'Pranav'
    },
    tabs: [],
    same_address: '1',
    app_type: '0',
    has_gstin: '0',
    account_no: ''
  }

  constructor(props) {
    super(props);
    this.setInitialTab();
  }

  setInitialTab() {
    for (let i = 0; i < tabs.length; i++) {
      let tabStatus = this.tabValidity(i);
      if (!tabStatus) {
        this.state.activeTab = i;
        return;
      }
      this.state.tabs[i] = tabStatus;
    }
  }

  changeTab = ({ target }) => this.goto(parseInt(target.getAttribute('data-index')))
  goto = activeTab => {
    let currentActive = this.state.activeTab;
    let isValid = this.tabValidity(currentActive);
    let tabs = this.state.tabs.slice();
    tabs[currentActive] = isValid;

    this.setState({
      activeTab,
      tabs
    });
  }

  next = e => this.goto(this.state.activeTab + 1);
  prev = e => this.goto(this.state.activeTab - 1);

  onChange = ({ target }) => {
    let stateName = target.getAttribute('data-name');
    if (stateName) {
      this.setState({
        [stateName]: target.value
      })
    } else {
      this.setState({
        data: {
          ...this.state.data,
          [target.name]: target.value
        }
      })
    }
  }

  render() {
    let activeTab = this.state.activeTab;
    let isLastTab = activeTab !== tabs.length - 1;
    let content = tabContent[activeTab].map((field, i) => {
      if (Array.isArray(field)) {
        return <Input.Group key={i}>{field.map(ActivationField, this)}</Input.Group>
      }
      return ActivationField.call(this, field);
    })

    return <div class="activation-wizard">
      <aside>
        <side-title>Account Activation</side-title>
        <p>
          Fill and submit the activation form to start
          transacting live from your Razorpay account.
        </p>
        <ul>
          {tabs.map((t, i) => {
            let isTabValid = this.state.tabs[i];
            return <li
              class={i === activeTab ? 'active' : ''}
              key={i}
              data-index={i}
              onClick={this.changeTab}
            >
              {t}
              {isTabValid && <i class={'i-done'} />}
            </li>
          })}
        </ul>
      </aside>
      <main>
        <main-title>{tabs[activeTab]}</main-title>
        <Form
          onChange={this.onChange}
          layout='tabular'
        >
          {content}
        </Form>
      </main>
      <footer>
        {activeTab && <Button iconBefore='chevron-left' onClick={this.prev}>Back</Button> || null}
        {isLastTab && <Button.Primary iconAfter='chevron-right' onClick={this.next}>Next</Button.Primary>}
        {isLastTab || <Button.Primary onClick={this.submit}>Submit Form</Button.Primary>}
      </footer>
    </div>
  }

  submit = e => {

  }

  // returns validity
  tabValidity(i) {
    return tabContent[i].every(c => Array.isArray(c) ? c.every(d => isFieldValid(d, this)) : isFieldValid(c, this));
  }
}

function ActivationField(field, activation) {
  let {
    _cmp: Component,
    _name,
    _when,
    ...rest
  } = field;

  if (_when && !_when(this)) {
    return null;
  }

  let defaultValue, key;
  if (rest.name) {
    key = rest.name;
    defaultValue = this.state.data[key];
  } else if (_name) {
    defaultValue = this.state[_name];
    key = _name;
  }

  return <Component
    key={key}
    data-name={_name}
    defaultValue={defaultValue}
    {...rest}
  />
}

function isFieldValid(field, activation) {
  let data = activation.state.data;
  if (!field.name) {
    // what isn't submissible is valid
    return true;
  }
  if (field._when) {
    // what isn't visible is valid
    if (!field._when(activation)) {
      return true;
    }
  }

  let value = data[field.name]
  if (field.required && !value) {
    // value missing in required field
    return false;
  }
  return true;
}
