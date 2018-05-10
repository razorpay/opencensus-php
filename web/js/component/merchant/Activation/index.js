import Form from 'component/Form';
import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';
import { classList } from 'common/util';
import { prevent } from 'common/util';

import {
  addDropShield,
  removeDropShield,
} from 'merchant/components/File/Upload';

import mainFormTabsContent, { mainFormTabs } from './ActivationFormMap';
import accountFormTabsContent, {
  accountFormTabs,
} from './AccountActivationFormMap';

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

let DOCUMENT_UPLOAD_STEP; // To handle specific case for document step
const BUSINESS_TYPE_FORM_STEP = 1; // If NGO is selected, then Document Upload would have 2 more fields

let FORM_TABS;
let FORM_TABS_CONTENT;

export default class ActivationWizard extends React.Component {
  state = {
    isSaving: null,
    data: this.props.data || {},
    dirty: {},
    tabs: [],
    same_address: '1',
    app_type: this.props.data && this.props.data.business_website ? '0' : '1',
    has_gstin: this.props.data && this.props.data.gstin ? '0' : '1',
    account_no: '',
    activeTab: 0, // Fallback for all cases.
  };

  constructor(props) {
    super(props);
    this.prepareTabs(props);
    this.setInitialTab();
  }

  prepareTabs(props) {
    if (props.accountId) {
      // Activation form for linked account

      FORM_TABS = [...accountFormTabs];
      FORM_TABS_CONTENT = [...accountFormTabsContent];
      DOCUMENT_UPLOAD_STEP = 2;

      // Removing document upload
      if (!props.data.need_kyc) {
        FORM_TABS.splice(DOCUMENT_UPLOAD_STEP, 1);
        FORM_TABS_CONTENT.splice(DOCUMENT_UPLOAD_STEP, 1);

        DOCUMENT_UPLOAD_STEP = null; // To set 'Document Upload Step' is not available.
      }
    } else {
      // Main Activation form for merchant

      FORM_TABS = mainFormTabs;
      FORM_TABS_CONTENT = mainFormTabsContent;
      DOCUMENT_UPLOAD_STEP = 4;

      // Business Category in "Business Fields-1" exists in main activation form
      FORM_TABS_CONTENT[1][3].options = [''].concat(
        Object.keys(props.categories).map(c => ({
          name: c,
          label: props.categories[c].description,
        }))
      );
    }

    defaultFieldProps(FORM_TABS_CONTENT); // Set the default props for all tab content views

    // All document fields in activation form to have same footprint
    DOCUMENT_UPLOAD_STEP &&
      FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP].forEach(a => {
        a._cmp = Input.File;
        a._accept = ['pdf', 'image'];
        a._showAcceptInfo = false;
        a._showStagedFileStatus = false;
        a.required = true;
      });

    // Adding onChange listener to all document upload fields
    DOCUMENT_UPLOAD_STEP &&
      FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP].forEach(
        a =>
          (a.onChange = (file, progressTracker) =>
            props.saveFile(a.name, file, progressTracker))
      );
  }

  componentDidMount() {
    addDropShield('.Activation--wizard');
  }

  componentWillUnmount() {
    removeDropShield('.Activation--wizard');
  }

  setInitialTab() {
    let firstInValid = null;
    let isSubmitDisabled = !(
      this.props.data.activated && this.props.data.locked
    );

    for (let i = 0; i < FORM_TABS.length; i++) {
      let tabStatusValid = this.tabValidity(i);

      if (!tabStatusValid && firstInValid === null) {
        firstInValid = i;
      }
      this.state.tabs[i] = tabStatusValid; // Mark tabs as valid-invalid
    }

    if (firstInValid === null) {
      firstInValid = FORM_TABS.length - 1; // In case all are filled then set last tab(which is actually filled)
      isSubmitDisabled && (this.state.showSubmitLayer = true); // Don't show submit form if it's already activated
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
    activeTab = typeof activeTab === 'undefined' ? currentActive : activeTab; // Tab is not changed (To handle Save btn click).

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

    if (DOCUMENT_UPLOAD_STEP && activeTab === BUSINESS_TYPE_FORM_STEP) {
      if (this.state.dirty.business_type) {
        let isDocumentStepValid = this.tabValidity(DOCUMENT_UPLOAD_STEP);
        tabs[DOCUMENT_UPLOAD_STEP] = isDocumentStepValid;

        this.setState({
          tabs,
        });
      }
    }

    const data = { ...this.state.dirty };

    for (let i = 0; i < Object.keys(data).length; i++) {
      let key = Object.keys(this.state.dirty)[i];

      if (data.hasOwnProperty(key)) {
        if (data[key] === '') {
          // If user empties the field, it must be set to NULL in DB
          data[key] = null;
        }
      }
    }

    this.props
      .save(data)
      .then(response => {
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
    let fieldValue = target.value;
    let fieldName = target.name;

    let sideEffectFieldsToUpdate = {}; // Some fields might lead to other fields get dirty. So, they also needs to be updated alongside

    /*
    * Step 1: These 4 fields are directly filled on user's behalf,
    * And marked dirty to be sent on click of Save
    * */
    if (stateName === 'same_address' && target.checked) {
      const { dirty, data } = this.state;
      // Checking the box, sets the ALL operation fields also dirty.
      sideEffectFieldsToUpdate['business_operation_address'] =
        dirty['business_registered_address'] ||
        data['business_registered_address'];
      sideEffectFieldsToUpdate['business_operation_pin'] =
        dirty['business_registered_pin'] || data['business_registered_pin'];
      sideEffectFieldsToUpdate['business_operation_city'] =
        dirty['business_registered_city'] || data['business_registered_city'];
      sideEffectFieldsToUpdate['business_operation_state'] =
        dirty['business_registered_state'] || data['business_registered_state'];
    }

    /* Step 2: If same_address is already ticked and any of business_registered fields are changed, then mark operational fields dirty;'.*/
    if (this.state.same_address == '1') {
      if (fieldName === 'business_registered_pin') {
        sideEffectFieldsToUpdate['business_operation_pin'] = fieldValue;
      } else if (fieldName === 'business_registered_city') {
        sideEffectFieldsToUpdate['business_operation_city'] = fieldValue;
      } else if (fieldName === 'business_registered_state') {
        sideEffectFieldsToUpdate['business_operation_state'] = fieldValue;
      }
    }

    /* Step 3: Auto fill city and state based on pin */
    if (
      fieldName === 'business_operation_pin' ||
      fieldName === 'business_registered_pin'
    ) {
      if (fieldValue.length === 6) {
        this.props.getPincodeDetails(fieldValue).then(data => {
          if (data) {
            const cityField = fieldName.slice(0, -3) + 'city';
            const stateField = fieldName.slice(0, -3) + 'state';

            sideEffectFieldsToUpdate[cityField] = data.city;
            sideEffectFieldsToUpdate[stateField] = data.state_code;

            // Input fields are uncontrolled, so needs to be updated directly
            document.querySelector(
              `.form-container [name=${cityField}]`
            ).value =
              data.city;
            document.querySelector(
              `.form-container [name=${stateField}]`
            ).value =
              data.state_code;
          }
        });
      }
    }

    /* Step Last: */
    if (stateName) {
      this.setState({
        [stateName]: fieldValue,
      });

      if (Object.keys(sideEffectFieldsToUpdate).length) {
        this.setState({
          data: {
            ...this.state.data,
            ...sideEffectFieldsToUpdate,
          },
          dirty: {
            ...this.state.dirty,
            ...sideEffectFieldsToUpdate,
          },
        });
      }
    } else {
      this.setState({
        data: {
          ...this.state.data,
          [target.name]: fieldValue,
          ...sideEffectFieldsToUpdate,
        },
        dirty: {
          ...this.state.dirty,
          [target.name]: fieldValue,
          ...sideEffectFieldsToUpdate,
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
    let isLinkedAccountForm = !!this.props.accountId;
    let isSubmitDisabled = !(
      this.props.data.activated && this.props.data.locked
    ); // Linked accounts form can still be seen after activation.

    let activeTab = this.state.activeTab;
    let isLastTab = activeTab == FORM_TABS.length - 1;
    let content; // Document content will always be shown so that upload progress is maintained

    if (activeTab !== DOCUMENT_UPLOAD_STEP) {
      content = FORM_TABS_CONTENT[activeTab].map((field, i) => {
        if (Array.isArray(field)) {
          return (
            <Input.Group key={i}>
              {field.map(ActivationField, this)}
            </Input.Group>
          );
        }

        return ActivationField.call(this, field);
      });
    }

    let documentContent =
      DOCUMENT_UPLOAD_STEP &&
      FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP].map((field, i) => {
        if (Array.isArray(field)) {
          return (
            <Input.Group key={i}>
              {field.map(ActivationField, this)}
            </Input.Group>
          );
        }

        return ActivationField.call(this, field);
      });

    return (
      <div class="Activation--wizard">
        <aside>
          <side-title>Account Activation</side-title>
          {!isLinkedAccountForm &&
            !isSubmitDisabled && (
              <p>
                Fill and submit the activation form to start transacting live
                from your Razorpay account.
              </p>
            )}
          <ul>
            {FORM_TABS.map((t, i) => {
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
                  {isTabValid && <i class={'i-check text-success'} />}
                  {t}
                </li>
              );
            })}
            {isSubmitDisabled && (
              <li
                onClick={this.toggleSubmitLayer}
                class={classList(
                  !this.isAllTabsValid() && 'disabled',
                  this.state.showSubmitLayer && 'active',
                  'li--submit'
                )}
              >
                Submit Form
                {!this.isAllTabsValid() && (
                  <div style={{ marginTop: -20, fontSize: 12 }}>
                    Fill required fields to submit
                  </div>
                )}
              </li>
            )}
          </ul>
        </aside>
        {/* Rest of the Content for business form */}
        {content && (
          <main
            class={classList(
              'form-container',
              this.state.showSubmitLayer && 'block-scroll'
            )}
          >
            <main-title>
              {activeTab != 0 && (
                <Button
                  class="btn--mobile btn--back"
                  iconBefore="chevron-left"
                  onClick={this.prev}
                >
                  Back
                </Button>
              )}
              {FORM_TABS[activeTab]}
            </main-title>

            <Form onChange={this.onChange} layout="tabular">
              {content}
            </Form>
          </main>
        )}
        {/* Document Content */}
        {DOCUMENT_UPLOAD_STEP && (
          <main
            class={classList(
              'form-container',
              this.state.showSubmitLayer && 'block-scroll',
              content && 'main--hide'
            )}
          >
            {
              <Button
                class="btn--mobile btn--back"
                iconBefore="chevron-left"
                onClick={this.prev}
              >
                Back
              </Button>
            }
            <main-title>{FORM_TABS[DOCUMENT_UPLOAD_STEP]}</main-title>
            <Form onChange={this.onChange} layout="tabular">
              {documentContent}
            </Form>
          </main>
        )}
        {isSubmitDisabled &&
          this.state.showSubmitLayer && (
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
            {activeTab != DOCUMENT_UPLOAD_STEP && (
              <Button onClick={_ => this.goto()}>Save</Button>
            )}
            {isLastTab || (
              <Button.Primary iconAfter="chevron-right" onClick={this.next}>
                <span class="btn--desktop">Save & Next</span>
                <span class="btn--mobile">Next</span>
              </Button.Primary>
            )}
            {isLastTab &&
              isSubmitDisabled && (
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
    return FORM_TABS_CONTENT[i].every(
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
        <React.Fragment>
          <span class="spin-btn" />
          Saving Changes...
        </React.Fragment>
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
  let { _cmp: Component, _name, _when, _optionsFn, ...rest } = field;

  if (_when && !_when(this)) {
    return null;
  }

  // Need to update options using rest.options to update in view, otherwise calling JUST _optionsFn changes options but doesnt change view.
  if (_optionsFn) {
    if (field.name === 'business_subcategory') {
      rest.options = field._optionsFn(this, this.props.categories);
    }
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
      disabled={!!this.state.data.locked || !!this.state.data.activated} // Linked accounts form can still be seen after activation
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
    const { closeSubmitForm } = this.props;

    return (
      <div class="SubmitForm-backdrop">
        <div class="SubmitForm-modal">
          <main-title>SUBMIT FORM</main-title>
          <div class="tnc-text">
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
          </div>
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
