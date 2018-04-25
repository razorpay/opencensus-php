import Input from 'component/Input';
import Form from 'component/Form';
import Button, { AsyncBtn } from 'component/Button';
import { classList } from 'common/util';

const tabs = [
  'Contact Details',
  'Business Details - 1',
  'Business Details - 2',
  'Bank Account Details',
  'Documents Upload',
];

const contactFields = [
  {
    label: 'Contact Name',
    name: 'contact_name',
  },
  {
    label: 'Contact Number',
    name: 'contact_mobile',
    type: 'tel',
    addonBefore: '+91',
    info: "We'll reach out on this number for any account related issues.",
  },
  {
    label: 'Contact Email',
    name: 'contact_email',
    type: 'email',
    info: "We'll reach out to this email for any account related issues.",
  },
];

const businessFields1 = [
  {
    label: 'Business Name',
    name: 'business_name',
    info: 'Example: Acme Private Limited',
  },
  {
    label: 'Doing Business As',
    name: 'business_dba',
  },
  {
    label: 'Business Type',
    name: 'business_type',
    _cmp: Input.Select,
    options: [
      '',
      'Proprietorship',
      'Individual',
      'Partnership',
      'Private Limited',
      'Public Limited',
      'LLP',
      'NGO',
      'Educational Institutes',
      'Trust',
      'Society',
      'Not yet registered',
      'Other',
    ],
  },
  {
    label: 'Business Model',
    name: 'business_model',
    _cmp: Input.Select,
    options: [],
  },
  {
    label: 'We want to accept International Payments as well',
    name: 'business_international',
    _cmp: Input.Check,
  },
  {
    label: 'CIN',
    name: 'company_cin',
  },
  {
    label: 'Business PAN Details',
    name: 'company_pan',
    placeholder: 'PAN Number',
    info: 'PAN details should belong to the business mentioned above',
  },
  {
    label: 'PAN Owner Name',
    name: 'company_pan_name',
  },
  {
    label: 'PAN info of Authorized Signatory/Promoter/Director',
    name: 'promoter_pan',
    placeholder: 'PAN Number',
  },
  {
    label: 'PAN Owner Name',
    name: 'promoter_pan_name',
  },
];

const differentAddress = activation => activation.state.same_address === '0';

const businessFields2 = [
  [
    {
      label: 'Website/App Details',
      _cmp: Input.Radio,
      _name: 'app_type',
      options: [
        'Website',
        'App',
        {
          label: "We don't have either",
          description: (
            <React.Fragment>
              You can still accept payments through <b>Razorpay Invoices</b> and
              <b> Razorpay Payment Links</b>. You can request access to other
              products (<b>Route</b>, <b>Subscription</b>, <b>Smart Collect</b>)
              once you have a website or app.
            </React.Fragment>
          ),
        },
      ],
    },
    {
      name: 'business_website',
      placeholder: 'Enter URL',
      type: 'url',
      required: false,
      description: (
        <React.Fragment>
          Your website should have following information easily accessible:
          <b> About Us</b>,<b> Contact</b>,<b> Privacy Policy</b>,
          <b> Terms & Conditions</b>, <b>Refund Policy</b> & <b>Pricing</b>.
          Please refer our{' '}
          <a href="" target="_blank">
            Compliance Policies{' '}
          </a>
          for more details.
        </React.Fragment>
      ),
      _when: activation => activation.state.app_type !== '2',
      info: 'Example: https://www.company.com',
    },
  ],
  [
    {
      name: 'business_registered_address',
      placeholder: 'Enter Street Address',
      label: 'Registered Address',
      _cmp: Input.Textarea,
    },
    {
      name: 'business_registered_pin',
      type: 'number',
      label: 'Pincode',
      size: 'small',
      min: '100000',
      max: '999999',
      validator: value => {
        let pin = Number(value);
        if (!pin || pin < 100000 || pin > 999999) {
          return 'Please enter 6 digit pincode';
        }
      },
    },
    {
      name: 'business_registered_city',
      label: 'City',
    },
    {
      name: 'business_registered_state',
      label: 'State',
    },
  ],
  {
    _name: 'same_address',
    label: 'Operational Address same as Registered Address',
    description: 'Physical verification may be performed at this address',
    _cmp: Input.Check,
  },
  [
    {
      name: 'business_operation_address',
      placeholder: 'Enter Street Address',
      label: 'Operational Address',
      _cmp: Input.Textarea,
      _when: differentAddress,
    },
    {
      name: 'business_operation_pin',
      type: 'number',
      label: 'Pincode',
      _when: differentAddress,
    },
    {
      name: 'business_operation_city',
      label: 'City',
      _when: differentAddress,
    },
    {
      name: 'business_operation_state',
      label: 'State',
      _when: differentAddress,
    },
  ],
  [
    {
      _name: 'has_gstin',
      label: 'GSTIN',
      options: ['We have a registered GSTIN', "We don't have a GSTIN"],
      _cmp: Input.Radio,
    },
    {
      name: 'gstin',
      _when: activation => activation.state.has_gstin === '0',
      placeholder: 'Enter GSTIN',
      size: 'small',
    },
  ],
];

const bankAccountFields = [
  {
    name: 'bank_branch_ifsc',
    label: 'Branch IFSC Code',
  },
  {
    name: 'bank_account_number',
    label: 'Account Number',
    type: 'password',
    info: 'Your company account to which your payments will be settled',
  },
  {
    _name: 'account_no',
    label: 'Re-Enter Account Number',
  },
  {
    name: 'bank_account_name',
    label: 'Beneficiary Name',
    description:
      'The beneficiary name should be same as the company name or individual name, in case of an LLP/Individual.',
  },
];

const uploadFields = [
  {
    name: 'business_proof_url',
    label: 'Business Registration Proof',
    description: (
      <ul>
        Upload scan of the following:
        <li>
          Sales Tax/Service Tax or Shop Act Registration or GST Certificate
          (mandatory, if Proprietorship firm)
        </li>
        <li>Partnership Deed (mandatory, if Partnership firm)</li>
        <li>
          Certificate of Incorporation (mandatory, if Private Limited or LLP)
        </li>
        <li>Registration Proof or Certificate (Trust/Society/NGO etc.)</li>
      </ul>
    ),
  },
  {
    name: 'business_pan_url',
    label: 'Business PAN',
    description: 'The PAN details should match the ones provided earlier',
  },
  {
    name: 'address_proof_url',
    label: "Company's Bank Account Statement with Address",
    description:
      'Your Bank account number, IFSC code, and Company Name should be clearly visible',
  },
  {
    name: 'promoter_address_url',
    label: "Authorized Signatory's Address Proof",
  },
];
uploadFields.forEach(a => (a._cmp = Input.File));

const tabContent = [
  contactFields,
  businessFields1,
  businessFields2,
  bankAccountFields,
  uploadFields,
];

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
};

defaultFieldProps(tabContent);

export default class ActivationWizard extends React.Component {
  state = {
    isSaving: null,
    data: this.props.data || {},
    dirty: {},
    tabs: [],
    same_address: '1',
    app_type: '0',
    has_gstin: '0',
    account_no: '',
    activeTab: 0, // Fallback
  };

  constructor(props) {
    businessFields1[3].options = [''].concat(
      Object.keys(props.categories).map(c => ({
        name: c,
        label: props.categories[c].description,
      }))
    );
    super(props);
    this.setInitialTab();

    uploadFields.forEach(a => (a.onChange = e => props.saveFile(e, a.name)));
  }

  setInitialTab() {
    let firstInValid;

    for (let i = 0; i < tabs.length; i++) {
      let tabStatus = this.tabValidity(i);

      if (!tabStatus && !firstInValid) {
        firstInValid = i;
      }
      this.state.tabs[i] = tabStatus; // Mark tabs as valid-invalid
    }

    this.state.activeTab = firstInValid;
  }

  changeTab = ({ target }) =>
    this.goto(parseInt(target.getAttribute('data-index')));

  goto = activeTab => {
    let currentActive = this.state.activeTab;
    let isValid = this.tabValidity(currentActive);
    let tabs = this.state.tabs.slice();
    tabs[currentActive] = isValid;

    let shouldSave = Object.keys(this.state.dirty).length ? true : null;

    this.setState({
      activeTab,
      tabs,
      isSaving: shouldSave,
      showSubmitLayer: false,
    });

    if (!shouldSave) {
      return;
    }

    this.props
      .save(this.state.dirty, this.props.accountId) // Account id for linked_account
      .then(response => {
        this.props.callback && this.props.callback(); // Support for callback for linked_account activation
        this.setState({
          dirty: {},
          isSaving: false,
        });
        this.removeLoader();
      })
      .catch(_ => {
        this.setState({
          isSaving: null,
        });
      });
  };

  submitForm = () => {
    return this.props.submitForm();
  };

  /* Fadeout based loader text */
  removeLoader = _ => {
    setTimeout(_ => {
      this.setState({ isSaving: null });
    }, 3000);
  };

  next = e => this.goto(this.state.activeTab + 1);
  prev = e => this.goto(this.state.activeTab - 1);

  onChange = ({ target }) => {
    let stateName = target.getAttribute('data-name');
    if (stateName) {
      this.setState({
        [stateName]: target.value,
      });
    } else {
      this.setState({
        data: {
          ...this.state.data,
          [target.name]: target.value,
        },
        dirty: {
          ...this.state.dirty,
          [target.name]: target.value,
        },
      });
    }
  };

  /* Find if all tabs are valid */
  isAllTabsValid() {
    let isValid = true;

    for (let i = 0; i < this.state.tabs.length; i++) {
      if (!this.state.tabs[i]) {
        isValid = false;
        break;
      }
    }

    return isValid;
  }

  /*
  * Opens backdrop submit layer
  * - By default is opens the submit layer.
  * - Closes the layer if false passed explicitly
  * */
  toggleSubmitLayer = (e, mode = true) => {
    if (mode && !this.isAllTabsValid()) {
      return;
    }

    this.setState({
      showSubmitLayer: mode,
    });
  };

  render() {
    let activeTab = this.state.activeTab;
    let isLastTab = activeTab !== tabs.length - 1;
    let content = tabContent[activeTab].map((field, i) => {
      if (Array.isArray(field)) {
        return (
          <Input.Group key={i}>{field.map(ActivationField, this)}</Input.Group>
        );
      }
      return ActivationField.call(this, field);
    });

    return (
      <div class="activation-wizard">
        <aside>
          <side-title>Account Activation</side-title>
          <p>
            Fill and submit the activation form to start transacting live from
            your Razorpay account.
          </p>
          <ul>
            {tabs.map((t, i) => {
              let isTabValid = this.state.tabs[i];
              return (
                <li
                  class={classList(
                    i === activeTab && !this.state.showSubmitLayer && 'active',
                    isTabValid && 'text-success'
                  )}
                  key={i}
                  data-index={i}
                  onClick={this.changeTab}
                >
                  {t}
                  {isTabValid && <i class={'i-done text-success'} />}
                </li>
              );
            })}
            <li
              onClick={this.toggleSubmitLayer}
              class={classList(
                !this.isAllTabsValid() && 'disabled',
                this.state.showSubmitLayer && 'active'
              )}
            >
              Submit Form
            </li>
          </ul>
        </aside>
        <main class={this.state.showSubmitLayer ? 'block-scroll' : ''}>
          <main-title>{tabs[activeTab]}</main-title>
          <Form onChange={this.onChange} layout="tabular">
            {content}
          </Form>
        </main>
        {this.state.showSubmitLayer && (
          <main class="overlay-container">
            <SubmitForm
              closeSubmitForm={this.toggleSubmitLayer}
              submitActvationForm={this.submitForm}
            />
          </main>
        )}
        {!this.state.showSubmitLayer && (
          <footer>
            <Loader isSaving={this.state.isSaving} />
            {(activeTab && (
              <Button iconBefore="chevron-left" onClick={this.prev}>
                Back
              </Button>
            )) ||
              null}
            {isLastTab && (
              <Button.Primary iconAfter="chevron-right" onClick={this.next}>
                Next
              </Button.Primary>
            )}
            {isLastTab || (
              <Button.Primary
                class={classList(!this.isAllTabsValid() && 'disabled')}
                onClick={this.toggleSubmitLayer}
              >
                Submit Form
              </Button.Primary>
            )}
          </footer>
        )}
      </div>
    );
  }

  // returns validity
  tabValidity(i) {
    return tabContent[i].every(
      c =>
        Array.isArray(c)
          ? c.every(d => isFieldValid(d, this))
          : isFieldValid(c, this)
    );
  }
}

/*
* Component for showing step saving loader in footer
* @prop {Boolean or null} isSaving - Current status of Loader
* */
function Loader({ isSaving }) {
  if (isSaving === null) {
    return <span class="Loader" />;
  }

  return (
    <span class="Loader Loader--visible">
      {isSaving ? (
        'Saving Changes...'
      ) : (
        <React.Fragment>
          <i class="i-check" />
          All changes saved
        </React.Fragment>
      )}
    </span>
  );
}

function ActivationField(field, activation) {
  let { _cmp: Component, _name, _when, ...rest } = field;

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

  return (
    <Component
      key={key}
      data-name={_name}
      defaultValue={defaultValue}
      disabled={this.state.data.locked}
      {...rest}
    />
  );
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

  let value = data[field.name];
  if (field.required && !value) {
    // value missing in required field
    return false;
  }
  return true;
}

/*
* Submit Form opens with backdrop inside Activation form's main content
* - The activeTab keeps showing in the background
* - @props
*     {Function} CloseSubmitForm, just closes the submit form layer and focuses back the activeTab
*     {Function} submitActvationForm, call the submit form api
* */
class SubmitForm extends React.Component {
  state = {
    allowSubmit: false,
  };

  submit = e => {
    if (!this.state.allowSubmit) {
      return;
    }

    return this.props.submitActvationForm();
  };

  render() {
    const { closeSubmitFormg } = this.props;

    return (
      <div class="SubmitForm-backdrop">
        <div class="SubmitForm-modal">
          <Input.Check
            onChange={e => {
              this.setState({
                allowSubmit: e.target.checked,
              });
            }}
          />
          <p>
            I have read and understood the{' '}
            <a
              href="https://razorpay.com/terms/"
              target="_blank"
              class="highlight"
            >
              Terms & Conditions
            </a>,{' '}
            <a
              href="https://razorpay.com/agreement/"
              target="_blank"
              class="highlight"
            >
              Merchant Agreement
            </a>{' '}
            and the{' '}
            <a
              href="https://razorpay.com/privacy/"
              target="_blank"
              class="highlight"
            >
              Privacy Policy
            </a>. By submitting the form, I agree to abide by the rules at all
            times.
          </p>
          <p class="text-fade">
            Please review the form before submitting as you cannot make any
            changes after submitting. For changes hereafter, contact us at
            support@razorpay.com.
          </p>
          <Button
            iconBefore="chevron-left"
            onClick={e => closeSubmitForm(e, false)}
          >
            Back to form
          </Button>
          <AsyncBtn.Primary
            class={this.state.allowSubmit ? '' : 'disabled'}
            onClick={this.submit}
            pendingState={'Submitting...'}
          >
            Submit Form
          </AsyncBtn.Primary>
        </div>
      </div>
    );
  }
}
