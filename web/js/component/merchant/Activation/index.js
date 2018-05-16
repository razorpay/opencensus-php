import Form from 'component/Form';
import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';
import Alert from 'component/Alert';
import { classList } from 'common/util';
import { prevent } from 'common/util';
import { autoPrefixUrls } from 'rzp/utils/rzp-utils';

import {
  addDropShield,
  removeDropShield,
} from 'merchant/components/File/Upload';

import mainFormTabsContent, { mainFormTabs } from './ActivationFormMap';
import accountFormTabsContent, {
  accountFormTabs,
} from './AccountActivationFormMap';

const LOADING_STATES = {
  ERROR: -1, // Error = show error msg
  SUCCESS: 1, // Success = show success msg
  PENDING: 0, // Pending = show spinner
  INITIAL: null, // Initial = hide spinner
};

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

let FORM_TABS; // Maintains naming of the tabs
let FORM_TABS_CONTENT; // Actual tab content corresponding to FORM_TABS

export default class ActivationWizard extends React.Component {
  state = {
    isSaving: LOADING_STATES.INITIAL,
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

      // Business Category in "Business Modal" exists in main activation form. Setting value dynamically from props.
      FORM_TABS_CONTENT[1][3][0].options = [''].concat(
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
          (a.onChange = (file, progressTracker) => {
            return props.saveFile(a.name, file, progressTracker);
          })
      );
  }

  componentDidMount() {
    addDropShield('.Activation--wizard');
  }

  componentWillUnmount() {
    removeDropShield('.Activation--wizard');
  }

  componentDidUpdate(nextProps) {
    if (
      this.props.data &&
      this.props.data.updated_at !== nextProps.data.updated_at
    ) {
      this.markTabIfActive();
    }
  }

  setInitialTab() {
    let firstInValid = null;
    let isSubmitFormRemoved = isSubmitFormDisabled(this.props.data); // Linked accounts form can still be seen after activation.

    for (let i = 0; i < FORM_TABS.length; i++) {
      let tabStatusValid = this.tabValidity(i);

      if (!tabStatusValid && firstInValid === null) {
        firstInValid = i;
      }
      this.state.tabs[i] = tabStatusValid; // Mark tabs as valid-invalid
    }

    if (firstInValid === null) {
      firstInValid = FORM_TABS.length - 1; // In case all are filled then set last tab(which is actually filled)
      !isSubmitFormRemoved && (this.state.showSubmitLayer = true); // Don't show submit form if it's already activated/locked/submitted
    }

    this.state.activeTab = firstInValid;
  }

  markTabIfActive() {
    let lastActive = this.state.lastActiveTab; // Last active tab while saving should update its tick
    let isValid = this.tabValidity(lastActive);
    let tabs = this.state.tabs.slice();

    tabs[lastActive] = isValid;

    this.setState({
      tabs,
    });
  }

  changeTab = ({ target }) =>
    this.goto(parseInt(target.getAttribute('data-index')));

  goto = newActiveTab => {
    if (newActiveTab === this.state.activeTab) {
      return; // No action if clicked on same Tab.
    }

    let currentActive = this.state.activeTab;
    let tabs = this.state.tabs.slice();

    newActiveTab =
      typeof newActiveTab === 'undefined' ? currentActive : newActiveTab; // currentActive tab remains (To handle Save btn click).

    let shouldSave = Object.keys(this.state.dirty).length ? true : null;

    this.setState({
      lastActiveTab: currentActive,
      activeTab: newActiveTab,
      isSaving: shouldSave ? LOADING_STATES.PENDING : LOADING_STATES.INITIAL,
      showSubmitLayer: false,
    });

    if (!shouldSave) {
      return;
    }

    const data = { ...this.state.dirty };

    // Setting the empty strings as null. Changed to null, since this is the default value in database.
    Object.keys(data).forEach(k => {
      if (data[k] === '') {
        data[k] = null;
      }
    });

    for (let i = 0; i < Object.keys(data).length; i++) {
      let key = Object.keys(this.state.dirty)[i];

      if (data.hasOwnProperty(key)) {
        if (data[key] === '') {
          // If user empties the field, it must be set to NULL in DB
          data[key] = null;
        }
      }
    }

    this.props.save(data).then(data => {
      // After updating 'Business type' detail, now update dependent field on FE.
      if (DOCUMENT_UPLOAD_STEP && currentActive === BUSINESS_TYPE_FORM_STEP) {
        if (this.state.dirty.business_type) {
          let isDocumentStepValid = this.tabValidity(DOCUMENT_UPLOAD_STEP);
          tabs[DOCUMENT_UPLOAD_STEP] = isDocumentStepValid;

          this.setState({
            tabs,
          });
        }
      }

      let isSaving;

      if (data.errors) {
        isSaving = LOADING_STATES.ERROR;
      } else {
        isSaving = LOADING_STATES.SUCCESS;
      }

      this.setState({
        dirty: {},
        isSaving,
      });

      this.removeLoader();
    });
  };

  submitForm = () => {
    return this.props.submitForm();
  };

  /* Fadeout based loader text */
  removeLoader = _ => {
    setTimeout(_ => {
      this.setState({ isSaving: LOADING_STATES.INITIAL });
    }, 7000);
  };

  next = e => this.goto(this.state.activeTab + 1);
  prev = e => this.goto(this.state.activeTab - 1);

  onChange = ({ target }) => {
    let stateName = target.getAttribute('data-name');
    let fieldValue = target.value;
    let fieldName = target.name;

    let sideEffectFieldsToUpdate = {}; // Some fields might lead to other fields get dirty. So, they also needs to be updated alongside
    const { dirty } = this.state;
    const { data } = this.props;

    /*
    * Step 1: These 4 fields are directly filled on user's behalf,
    * And marked dirty to be sent on click of Save
    * */
    if (stateName === 'same_address' && target.checked) {
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
      } else if (fieldName === 'business_registered_address') {
        sideEffectFieldsToUpdate['business_operation_address'] = fieldValue;
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
            const cityField = fieldName.slice(0, -3) + 'city'; // It can be operation_ / registered_
            const stateField = fieldName.slice(0, -3) + 'state';

            // Update dependent values
            sideEffectFieldsToUpdate[cityField] = data.city;
            sideEffectFieldsToUpdate[stateField] = data.state_code;
            if (this.state.same_address == '1') {
              sideEffectFieldsToUpdate['business_operation_city'] = data.city;
              sideEffectFieldsToUpdate['business_operation_state'] =
                data.state_code;
            }

            // Input fields are uncontrolled, so needs to be updated directly. Updating dependent field visible in view.
            document.querySelector(
              `.form-container [name=${cityField}]`
            ).value =
              data.city;
            document.querySelector(
              `.form-container [name=${stateField}]`
            ).value =
              data.state_code;

            this.setState({
              dirty: {
                ...this.state.dirty,
                // [target.name]: fieldValue, // No need to update, since it's already updated in state.dirty before Promise.
                ...sideEffectFieldsToUpdate,
              },
            });
          }
        });
      }
    }

    /* Step 4: Business category and sub category are always marked dirty in pairs. BE validates them in pair. */
    if (fieldName === 'business_category') {
      // Set first option in new set of subcategory. It remains '', it would convert to null before making api call.
      sideEffectFieldsToUpdate['business_subcategory'] = '';

      const el = document.querySelector(
        `.form-container [name=business_subcategory]`
      );
      el && (el.value = '');
    }

    if (fieldName === 'business_subcategory') {
      sideEffectFieldsToUpdate['business_category'] =
        dirty['business_category'] || data['business_category'];
    }

    /* Step 5: Business website must have http/https prepended */
    if (fieldName === 'business_website') {
      fieldValue = autoPrefixUrls(fieldValue); // Updating in view will happen if he comes to this tab again. Otherwise single backspace on 'http' must be handled as full word not single character.
    }

    /* Step Last: */
    if (stateName) {
      this.setState({
        [stateName]: fieldValue,
      });

      if (Object.keys(sideEffectFieldsToUpdate).length) {
        this.setState({
          dirty: {
            ...this.state.dirty,
            ...sideEffectFieldsToUpdate,
          },
        });
      }
    } else {
      this.setState({
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
    let isLinkedAccountForm = !!this.props.accountId; // Check if this activation wizard is invoked from Linked accounts.
    let isSubmitFormRemoved = isSubmitFormDisabled(this.props.data); // Submit form is removed if locked, activated or in submitted state

    let activeTab = this.state.activeTab;
    activeTab = activeTab < 0 ? 0 : activeTab; // Graceful failure in case activeTab becomes negative. To handle non-reproducible weird error.

    let isLastTab = activeTab == FORM_TABS.length - 1;

    // Data would be present, otherwise spinner is shown before this activation wizard
    let currentBusinessType =
      this.state.dirty.business_type || this.props.data.business_type;

    let content;
    let documentContent; // Document content will always be shown so that upload progress is maintained in DOM

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

    documentContent =
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
        {/* Activation form tabs */}
        <aside>
          <side-title>Account Activation</side-title>
          {!isLinkedAccountForm &&
            !isSubmitFormRemoved && (
              <p>
                Fill and submit the activation form to start transacting live
                from your Razorpay account.
              </p>
            )}
          <ul>
            {/* Activation form tabs */}
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

            {/* Submit form tab*/}
            {!isSubmitFormRemoved && (
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

        {/* Activation form Content */}
        <main
          class={classList(
            'form-container',
            this.state.showSubmitLayer && 'block-scroll'
          )}
        >
          {/* Active tab title */}
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

          {/* Show Alert: if linked account has been activated */}
          {isLinkedAccountForm &&
            !!this.props.data.activated && (
              <Alert.Info>The account has been activated</Alert.Info>
            )}

          {/* Show alert: if main activation form is in locked state */}
          {do {
            const showFormDisabledAlert =
              !isLinkedAccountForm &&
              (!!this.props.data.locked ||
                !!this.props.data.submitted ||
                !!this.props.data.activated); // Later activated condition to be removed as form will never be shown in this scenario.
            let icon, msg;

            if (showFormDisabledAlert) {
              if (!!this.props.data.activated) {
                icon = 'i-done-all';
                msg = 'Congratulations! Your account is Activated.';
              } else if (!!this.props.data.locked) {
                // 'locked' has priority than 'submitted'
                icon = 'i-outline-lock';
                msg =
                  "Your activation form is locked as it's under review. We'll inform you once your account gets activated.";
              } else if (!!this.props.data.submitted) {
                icon = 'i-check';
                msg =
                  "Your activation form is already submitted. We'll inform you once your account gets activated.";
              }

              <Alert.Info iconBefore={icon}>
                {msg}
                <div class="side-description">
                  In case of any queries, you can reach out to us at{' '}
                  <a href="mailto:support@razorpay.com">support@razorpay.com</a>.
                </div>
              </Alert.Info>;
            }
          }}

          {/* Show Alert: if user has selected individual business type */}
          {!isLinkedAccountForm &&
            currentBusinessType == 2 && (
              <Alert.Warning>
                We may not be able to support individual as of now. Get in touch
                with{' '}
                <a href="mailto:support@razorpay.com">support@razorpay.com</a>{' '}
                for more details.
              </Alert.Warning>
            )}

          {/* Activation form starts here */}
          <Form onChange={this.onChange} layout="tabular">
            {/* Other form content shown if not Document */}
            {content}

            {/* Document content is always in DOM */}
            <div style={{ display: content ? 'none' : 'inherit' }}>
              {documentContent}
            </div>
          </Form>
        </main>

        {/* Submit form overlay view */}
        {!isSubmitFormRemoved &&
          this.state.showSubmitLayer && (
            <main class="overlay-container">
              <SubmitForm
                closeSubmitForm={this.toggleSubmitLayer}
                submitActvationForm={this.submitForm}
              />
            </main>
          )}

        {/* Activation form footer, to show actions / saving state */}
        {!this.state.showSubmitLayer && (
          <footer>
            {/* Spinner state */}
            <Loader isSaving={this.state.isSaving} />

            {/* Action Button 1 */}
            {activeTab != DOCUMENT_UPLOAD_STEP && (
              <Button onClick={_ => this.goto()}>Save</Button>
            )}

            {/* Action Button 2 */}
            {isLastTab || (
              <Button.Primary iconAfter="chevron-right" onClick={this.next}>
                <span class="btn--desktop">Save & Next</span>
                <span class="btn--mobile">Next</span>
              </Button.Primary>
            )}

            {/* Action Button 3 */}
            {isLastTab &&
              !isSubmitFormRemoved && (
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
  if (isSaving === LOADING_STATES.INITIAL) {
    return <span class="Loader" />;
  }

  return (
    <span class="Loader Loader--visible">
      {do {
        if (isSaving === LOADING_STATES.PENDING) {
          <React.Fragment>
            <span class="spin-btn" />
            Saving Changes...
          </React.Fragment>;
        } else if (isSaving === LOADING_STATES.SUCCESS) {
          <React.Fragment>
            <i class="i-check text-success" />
            <span class="text-success">All changes saved</span>
          </React.Fragment>;
        } else if (isSaving === LOADING_STATES.ERROR) {
          <React.Fragment>
            <i class="i-close text-danger" />
            <span class="text-danger">Last changes are not saved!</span>
          </React.Fragment>;
        }
      }}
    </span>
  );
}

function ActivationField(field) {
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
    defaultValue = this.props.data[key];
  } else if (_name) {
    defaultValue = this.state[_name];
    key = _name;
  }

  return (
    <Component
      key={key}
      data-name={_name}
      defaultValue={defaultValue}
      disabled={isSubmitFormDisabled(this.props.data)} // If form cannot be submitted, then all fields are disabled.
      {...rest}
    />
  );
}

function isSubmitFormDisabled(data) {
  let isSubmitFormRemoved = data.activated || data.submitted || data.locked; // Linked accounts form can still be seen after activation.

  return !!isSubmitFormRemoved;
}

function isFieldValid(field, activation) {
  let data = activation.props.data;
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
    allowSubmit: false, // Check if checkbox is ticked
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
            {/* Confirmation checkbox*/}
            <Input.Check
              onChange={e => {
                this.setState({
                  allowSubmit: e.target.checked,
                });
              }}
            />

            {/* Primary copy */}
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

          {/* Secondary copy */}
          <p class="text-fade">
            Please review the form before submitting as you cannot make any
            changes after submitting. For changes hereafter, contact us at
            support@razorpay.com.
          </p>

          {/* Action button 1 */}
          <Button
            iconBefore="chevron-left"
            onClick={e => closeSubmitForm(e, false)}
          >
            Back to form
          </Button>

          {/* Action button 2 */}
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
