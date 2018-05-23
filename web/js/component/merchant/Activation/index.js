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

import * as trackers from 'merchant/containers/Activation/ga_new';

let onAction = trackers;

const LOADING_STATES = {
  ERROR: -1, // Error = show error msg
  SUCCESS: 1, // Success = show success msg
  PENDING: 0, // Pending = show spinner
  INITIAL: null, // Initial = hide spinner
};

function defaultFieldProps(f) {
  const self = this;

  if (Array.isArray(f)) {
    return f.forEach(defaultFieldProps.bind(self));
  }
  if (!f._cmp) {
    f._cmp = Input;
  }
  if (!f.hasOwnProperty('required')) {
    f.required = true;
  }

  if (f.hasOwnProperty('description') && typeof f.description === 'function') {
    f.description = f.description.bind(self); // Dynamic description based on other fields must be able to access this.state.dirty and this.props
  }

  if (f.hasOwnProperty('validator') && typeof f.validator === 'function') {
    f.validator = f.validator.bind(self); // Dynamic description based on other fields must be able to access this.state.dirty and this.props
  }

  if (!f.hasOwnProperty('autoComplete')) {
    f.autoComplete = 'off';
  }
}

let DOCUMENT_UPLOAD_STEP; // To handle specific case for document step
const BUSINESS_TYPE_FORM_STEP = 1; // If NGO is selected, then Document Upload would have 2 more fields

let FORM_TABS; // Maintains naming of the tabs
let FORM_TABS_CONTENT; // Actual tab content corresponding to FORM_TABS

export default class ActivationWizard extends React.Component {
  state = {
    isSaving: LOADING_STATES.INITIAL,
    dirty: {},
    tabs: [],
    same_address:
      this.props.data &&
      this.props.data.business_operation_pin ==
        this.props.data.business_registered_pin
        ? '1'
        : '0', // '1' => checkbox ticked
    has_gstin: this.props.data && this.props.data.gstin ? '0' : '1', // '0' => value exists
    account_no: this.props.data && this.props.data.bank_account_number,
    activeTab: 0, // Fallback for all cases.
  };

  constructor(props) {
    super(props);
    this.prepareTabs(props);
    this.setInitialTab();

    if (props.accountId) {
      onAction = null; // Only tracking for main activation form
    }
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

      // Business Category in "Business Model" exists in main activation form. Setting value dynamically from props.
      FORM_TABS_CONTENT[1][3][0].options = ['--Select--'].concat(
        Object.keys(props.categories).map(c => ({
          name: c,
          label: props.categories[c].description,
        }))
      );
    }

    defaultFieldProps.call(this, FORM_TABS_CONTENT); // Set the default props for all tab content views

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
            return props.saveFile(a.name, file, progressTracker).then(resp => {
              this.markTabIfActive(DOCUMENT_UPLOAD_STEP);
            });
          })
      );
  }

  componentDidMount() {
    addDropShield('.Activation--wizard');
  }

  componentWillUnmount() {
    removeDropShield('.Activation--wizard');
    this.unMounted = true; // Used while using this.goto to update state while closing modal
  }

  setInitialTab() {
    let firstInValid = null;
    let isSubmitFormRemoved = isSubmitFormDisabled(this.props.data); // Linked accounts form can still be seen after activation.

    for (let i = 0; i < FORM_TABS.length; i++) {
      let tabStatusValid = this.tabValidity(i);

      if (!tabStatusValid && firstInValid === null) {
        firstInValid = i;
      }
      this.state.tabs[i] = tabStatusValid; // Mark tabs as valid-invalid (IMPORTANT STEP)
    }

    // If main Form is not touched ever, then set initial tab = 0
    if (!this.props.accountId && this.props.isFormTouched === false) {
      firstInValid = 0;
    }

    if (firstInValid === null) {
      firstInValid = FORM_TABS.length - 1; // In case all are filled then set last tab(which is actually filled)
      !isSubmitFormRemoved && (this.state.showSubmitLayer = true); // Directly show submit form if it's NOT activated/locked/submitted
    }

    this.state.activeTab = firstInValid;
  }

  markTabIfActive(updatedTabId) {
    let isValid = this.tabValidity(updatedTabId);
    let tabs = this.state.tabs.slice();

    tabs[updatedTabId] = isValid;

    // To handle case if user directly clicked on 'Submit Form' tab to send dirty data
    if (!isValid) {
      this.setState({
        showSubmitLayer: false,
      });
    }

    this.setState({
      tabs,
    });
  }

  saveCurrentTab = () => {
    const currenActiveTab = this.state.activeTab;

    const callBack =
      onAction &&
      function(result, error) {
        onAction.trackSave({
          tabId: currenActiveTab,
          type: result, // result = true for Success, false for Error, null for no api call
          error,
        });
      };

    this.goto(null, callBack);
  };

  next = e => {
    const currenActiveTab = this.state.activeTab;

    const callBack =
      onAction &&
      function(result, error) {
        onAction.trackSaveAndNext({
          tabId: currenActiveTab,
          type: result, // result = true for Success, false for Error, null for no api call
          error,
        });
      };

    this.goto(this.state.activeTab + 1, callBack);
  };

  prev = e => {
    const currenActiveTab = this.state.activeTab;

    const callBack =
      onAction &&
      function(result, error) {
        onAction.trackBack({
          tabId: currenActiveTab,
          type: result, // result = true for Success, false for Error, null for no api call
          error,
        });
      };

    this.goto(this.state.activeTab - 1, callBack);
  };

  changeTab = ({ target }) => {
    const tabId = parseInt(target.getAttribute('data-index'));
    const currentActiveTab = this.state.activeTab;

    const callBack =
      onAction &&
      function(result, error) {
        onAction.trackTabClick(tabId); // Tracks current tab clicked

        if (typeof result !== 'undefined') {
          onAction.trackSaveOnTabClick({
            tabId: currentActiveTab, // Tracks for tab that got saved
            type: result, // result = true for Success, false for Error, null for no api call
            error,
          });
        }
      };

    this.goto(tabId, callBack);
  };

  //newActiveTab = null -> clicked on Save btn / 'Submit Form' tab
  goto = (newActiveTab, cb) => {
    if (this.state.showSubmitLayer) {
      // Hide only if it's already visible. To handle if the person has clicked on 'Submit Form' to save dirty data, then submit layer should still be shown.
      // And since showSubmitLayer is set true in same cycle as click on 'Submit Form' handler, it will take previous value which is false.

      this.setState({
        showSubmitLayer: false,
      });
    }

    // cb is ignored if clicked on same tab. And no further action taken.
    if (newActiveTab === this.state.activeTab) {
      return; // No action if clicked on same Tab. (Click on Save sends newActiveTab = null, so it's not same as click on same tab)
    }

    let currentActive = this.state.activeTab; // currentActive = The tab of which dirty data is saved
    let tabs = this.state.tabs.slice();

    newActiveTab = newActiveTab != null ? newActiveTab : currentActive; // currentActive tab remains as newActiveTab (To handle Save btn click and 'Submit Form' tab click).

    let shouldSave = Object.keys(this.state.dirty).length ? true : null;

    this.setState({
      activeTab: newActiveTab,
    });

    if (!shouldSave) {
      this.updateFEOnlyValues();

      cb && cb(); // If clicked on Save/Save-Next btn without any change
      return;
    }

    this.setState({
      isSaving: LOADING_STATES.PENDING,
    });

    const currentDirty = this.state.dirty;
    const data = {};

    Object.keys(currentDirty).forEach(field => {
      if (currentDirty.hasOwnProperty(field)) {
        const fieldVal = currentDirty[field];
        data[field] = fieldVal === '' ? null : fieldVal; // '' -> null. DB has default values as NULL.
      }
    });

    this.setState({
      dirty: data,
    }); // '' -> null inside state.dirty for easy OLD and LATEST data comparison.

    /* Following is api call and post response handling */
    const savingWhichTab = currentActive;
    const savingDataOfWhichTab = { ...data };

    this.props.save(data).then(data => {
      if (this.unMounted) {
        return; // No further actions if component unmounted. To handle cross btn close, where only hit Api without doing then.
      }

      this.markTabIfActive(savingWhichTab); // Re-evaluate tab being saved tab.

      // After updating 'Business type' detail, now update dependent field on FE.
      // Can loop and re-evaluate all tabs, IF more dependent fields are there. But this is for optimization.
      if (DOCUMENT_UPLOAD_STEP && savingWhichTab === BUSINESS_TYPE_FORM_STEP) {
        if (this.state.dirty.business_type) {
          // Document fields are only dependent on business_type field.
          this.markTabIfActive(DOCUMENT_UPLOAD_STEP);
        }
      }

      if (data.errors) {
        cb && cb(false, data.errors);

        if (savingWhichTab && savingWhichTab !== this.state.activeTab) {
          // On error, "IF TAB IS CHANGED", then remove the 'savingFieldsOfWhichTab' keys from state.dirty.
          // To handle cases where Ghost of incorrect filled value in previous tab is not letting current tab being saved.
          // Also, person will have to fill all 'savingFieldsOfWhichTab' fields in dirty data again..

          const latestDirty = { ...this.state.dirty };

          // TODO: -> If connection is slow, user edited AND SAVED the different tab field before previous response(having error), then this SAVE will also fail.
          // TODO: + This could be handlded by each tab having its own dirty state.
          // TODO: + Note, this won't create problem for same tab field editing-saving, cuz anyways, dirty will get retained by default.
          Object.keys(savingDataOfWhichTab).forEach(key => {
            if (
              savingDataOfWhichTab.hasOwnProperty(key) &&
              savingDataOfWhichTab[key] == this.state.dirty[key]
            ) {
              /*
              * Remove only those set of fields whose api request failed, while retaining changes of new form edits(latest state.dirty).
              * Also, handles case where user edited same field whose request failed. But it's treated as fresh value.
              * Also, otherwise if deleted from state.dirty, DOM form view will display a value that's not present in state.dirty.
              * */

              delete latestDirty[key];
            }
          });

          // Don't set dirty = {}, cuz internet might be slow and user has already edited some other fields.
          this.setState({
            dirty: latestDirty,
          });
        }

        /*
        * We're not doing any change on dirty, if it's SAME tab.
        * Bcoz, for 1 api-errored field, all other fields must be retained for Saving again.
        * */

        this.setState({
          isSaving: LOADING_STATES.ERROR,
        });

        this.removeLoader();
      } else {
        cb && cb(true);

        const latestDirty = { ...this.state.dirty };
        Object.keys(savingDataOfWhichTab).forEach(key => {
          if (
            savingDataOfWhichTab.hasOwnProperty(key) &&
            savingDataOfWhichTab[key] == this.state.dirty[key]
          ) {
            /*
             * Remove only those set of fields whose api request is success, while retaining changes of new form edits(latest state.dirty).
             * Also, handles case where user edited same field whose request is success. But it's treated as fresh value.
             * Also, otherwise if deleted from state.dirty, DOM form view will display a value that's not present in state.dirty.
             * */
            delete latestDirty[key];
          }
        });

        // Don't set dirty = {}, cuz internet might be slow and user has already edited some other field.
        this.setState({
          dirty: latestDirty,
          isSaving: LOADING_STATES.SUCCESS,
        });

        this.removeLoader(3000);
      }
    });
  };

  /*
  * Fn. to keep _name fields(FE only fields) in sync with updated values(props.data) on tab change.
  * + Checking/Unchecking/Changing _name FE fields will remain as it is throughout(in state). But changing them might not always save data.
  * + Example: Changing 'has_gstin' from 1 -> 0 (not have-> have) but value is not filled, then tab change won't save anything. So next time, tab is selected, radio box must display as per saved value, not last state value.
  * + Example: If `same_address` ticked but values not saved due to some reason.
  * */
  updateFEOnlyValues() {
    // Step 1:
    // Handle case where user changed to 'no gst' option. But since we don't modify GST once filled, 1st radio box must get auto selected if GST value exists.
    // has_gstin  = 0 => selected 1st radio box => Has GSTIN
    this.setState({
      has_gstin: this.props.data.gstin ? '0' : '1', // '1' => no value
    });

    // Step 2:
    // Handle case where user changed to 'no gst' option. But since we don't modify GST once filled, 1st radio box must get auto selected if GST value exists.
    // has_gstin  = 0 => selected 1st radio box => Has GSTIN
    this.setState({
      same_address:
        this.props.data.business_operation_pin ==
        this.props.data.business_registered_pin
          ? '1'
          : '0', // '1' => checkbox ticked
    });
  }

  submitForm = () => {
    const promise = this.props.submitForm();

    onAction &&
      promise.then(data => {
        if (data.errors) {
          onAction.trackSubmit({
            error: data.errors,
            type: false,
          });
        } else {
          onAction.trackSubmit({
            type: true,
          });
        }
      });
  };

  /*
  * Fadeout based loader text.
  * Default delay = 7 sec
  * */
  removeLoader = delay => {
    setTimeout(_ => {
      this.setState({ isSaving: LOADING_STATES.INITIAL });
    }, delay || 7000); // Success states can be removed in 3sec.
  };

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

    /*
     * Step 2: If user marks no GSTIN from radio box
     * */
    if (stateName === 'has_gstin' && fieldValue === '1') {
      sideEffectFieldsToUpdate['gstin'] = null;
    }

    /* Step 3: If same_address is already ticked and any of business_registered fields are changed, then mark operational fields dirty;'.*/
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

    /* Step 4: Auto fill city and state based on pin */
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

    /* Step 5: Business category and sub category are always marked dirty in pairs. BE validates them in pair. */
    if (fieldName === 'business_category') {
      // Set first option in new set of subcategory. It remains '', it would convert to null before making api call.
      sideEffectFieldsToUpdate['business_subcategory'] = '';
      sideEffectFieldsToUpdate['business_model'] = ''; // Reset Business Model as well.

      // Update Business Subcategory in view
      let el = document.querySelector(
        `.form-container [name=business_subcategory]`
      );
      el && (el.value = '');

      // Update Business Model in view
      el = document.querySelector(`.form-container [name=business_model]`);
      el && (el.value = '');
    }

    if (fieldName === 'business_subcategory') {
      sideEffectFieldsToUpdate['business_category'] =
        dirty['business_category'] || data['business_category'];
    }

    /* Step 6: Business website must have http/https prepended */
    if (fieldName === 'business_website') {
      fieldValue = autoPrefixUrls(fieldValue); // Updating in view will happen if he comes to this tab again. Otherwise single backspace on 'http' must be handled as full word not single character.
    }

    /* Step 7: Empty the account_no if anything changed in bank_account_number */
    if (fieldName === 'bank_account_number') {
      document.querySelector(
        `.form-container [data-name=account_no]`
      ).value = null;
      this.setState({
        account_no: null,
      }); // We can have sideEffectStatesToUpdate also if more such cases are there
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
  * Toggles backdrop submit layer
  * - By default is opens the submit layer.
  * - Closes the layer if false passed explicitly
  * */
  toggleSubmitLayer = e => {
    if (!this.isAllTabsValid()) {
      return; // Now allowed to go to submit form unless all tabs are valid
    }

    if (this.state.showSubmitLayer) {
      this.setState({
        showSubmitLayer: false,
      });
    } else {
      this.setState({
        showSubmitLayer: true,
      });

      // Click on 'Submit Form' tab is tracked only when it's not current tab
      const currentActiveTab = this.state.activeTab;

      if (currentActiveTab === DOCUMENT_UPLOAD_STEP) {
        return; // There is nothing to auto save in Document Upload section
      }

      const callBack =
        onAction &&
        function(result, error) {
          onAction.trackSubmitFormTabClick();

          if (typeof result !== 'undefined') {
            onAction.trackSaveOnTabClick({
              tabId: currentActiveTab, // Tracks for tab that got saved
              type: result, // result = true for Success, false for Error, null for no api call
              error,
            });
          }
        };

      this.goto(null, callBack);
    }
  };

  render() {
    let isLinkedAccountForm = !!this.props.accountId; // Check if this activation wizard is invoked from Linked accounts.
    let isSubmitFormRemoved = isSubmitFormDisabled(this.props.data); // Submit form is removed if locked, activated or in submitted state

    let activeTab = this.state.activeTab;
    activeTab = activeTab < 0 || !activeTab ? 0 : activeTab; // Graceful failure in case activeTab becomes negative. To handle non-reproducible weird error.

    const isCurrentTabValid = this.state.tabs[activeTab];

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
          <side-title>Activation Form</side-title>
          {!isLinkedAccountForm &&
            !isSubmitFormRemoved && (
              <p>Complete and submit the form to start accepting payments.</p>
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
                    Complete the form to submit
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
            this.state.showSubmitLayer && 'block-scroll',
            isSubmitFormRemoved && 'main--full'
          )}
        >
          {/* Active tab title */}
          <main-title class="main-title">
            {activeTab != 0 && (
              <Button
                class="device--mobile btn--back"
                iconBefore="arrow-back"
                onClick={this.prev}
              />
            )}
            <span
              class={classList(
                'device--mobile main-title-icon',
                isCurrentTabValid && 'text-success '
              )}
            >
              <i class={classList('i-check', isCurrentTabValid && 'drishy')} />
              {FORM_TABS[activeTab]}
            </span>

            <span class="device--desktop">{FORM_TABS[activeTab]}</span>
          </main-title>

          {/* Alert: if linked account has been activated */}
          {isLinkedAccountForm &&
            !!this.props.data.activated && (
              <Alert.Info iconBefore="i-done-all">
                The account has been activated
              </Alert.Info>
            )}

          {/* Alert: if main activation form is in locked state */}
          {do {
            const showFormDisabledAlert =
              !isLinkedAccountForm &&
              (!!this.props.data.locked ||
                !!this.props.data.submitted ||
                !!this.props.data.activated); // Later activated condition to be removed as form will never be shown in this scenario.

            const { data } = this.props;
            let Component = Alert.Info;
            let icon, msg;

            let secondaryMsg =
              'For any clarifications, you can reach out to us at';
            const emailLink = (
              <a href="mailto:support@razorpay.com">support@razorpay.com</a>
            );

            if (showFormDisabledAlert) {
              if (!!data.activated) {
                // **1. Alert: Account Activated

                icon = 'i-done-all';
                msg = 'Your account is activated.';
                secondaryMsg = (
                  <React.Fragment>
                    For any changes, please write to {emailLink} from your
                    registered email.
                  </React.Fragment>
                );
              } else if (data.activation_status === 'needs_clarification') {
                // **2. Alert: Need clarification
                let clarificationMode = this.props.data.clarification_mode;
                let subMsg;
                if (clarificationMode.toLowerCase() === 'email') {
                  subMsg =
                    'Please check your mail and respond at the earliest.';
                } else {
                  subMsg = 'We will call you over phone for clarification.';
                }

                icon = 'i-warning';
                Component = Alert.Warning;
                msg = 'There are issues with your activation form. ' + subMsg;
                secondaryMsg = (
                  <React.Fragment>
                    In case of any queries, you can reach out to us at{' '}
                    {emailLink}
                  </React.Fragment>
                );
              } else if (data.activation_status === 'rejected') {
                // **3. Alert: Form Rejected

                icon = 'i-close';
                Component = Alert.Error;
                msg =
                  'Your activation form has been rejected by our partner banks. Hence, we would not be able support your business at this moment.';
                secondaryMsg = 'We have sent you an email with the details.';
              } else if (!!data.locked) {
                // **4. Alert: Form is Locked (for reasons other than above)
                // 'locked' status has more priority than 'submitted'

                icon = 'i-outline-lock';
                msg =
                  'Your activation form under review. We will let you know once your account gets activated.';
                secondaryMsg = (
                  <React.Fragment>
                    In case of any queries, you can reach out to us at{' '}
                    {emailLink}
                  </React.Fragment>
                );
              } else if (!!data.submitted) {
                // **5. Alert: Form is Submitted

                icon = 'i-check';
                msg =
                  'Your activation form is already submitted. It usually takes 2 to 3 working days for the review.';
                secondaryMsg =
                  'For any clarifications, we will reach out on your contact email.';
              }

              <Component iconBefore={icon}>
                {msg}
                <div class="side-description">{secondaryMsg}</div>
              </Component>;
            }
          }}

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
                closeActivationForm={() => {
                  this.goto(FORM_TABS.length - 1);
                }}
                submitActvationForm={this.submitForm}
              />
            </main>
          )}

        {/* Activation form footer, to show actions / saving state */}
        {!isSubmitFormRemoved && (
          <footer>
            {/* Spinner state */}
            <Loader isSaving={this.state.isSaving} />
            {!this.state.showSubmitLayer && (
              <React.Fragment>
                {/* Action Button 1 */}
                {activeTab != DOCUMENT_UPLOAD_STEP && (
                  <Button onClick={this.saveCurrentTab}>Save</Button>
                )}

                {/* Action Button 2 */}
                {isLastTab || (
                  <Button.Primary iconAfter="chevron-right" onClick={this.next}>
                    <span class="device--desktop">Save & Next</span>
                    <span class="device--mobile">Next</span>
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
              </React.Fragment>
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
            <span class="device--desktop">Saving Changes...</span>
            <span class="device--mobile">Saving</span>
          </React.Fragment>;
        } else if (isSaving === LOADING_STATES.SUCCESS) {
          <React.Fragment>
            <i class="i-check text-success" />
            <span class="text-success device--desktop">All changes saved</span>
            <span class="text-success device--mobile">Saved</span>
          </React.Fragment>;
        } else if (isSaving === LOADING_STATES.ERROR) {
          <React.Fragment>
            <i class="i-close text-danger" />
            <span class="text-danger device--desktop">
              Recent changes were not saved!
            </span>
            <span class="text-danger device--mobile">Not Saved!</span>
          </React.Fragment>;
        }
      }}
    </span>
  );
}

function ActivationField(field) {
  let {
    _cmp: Component,
    _name,
    _when,
    _optionsFn,
    _autoRenderImpure,
    _disabledWhen,
    ...rest
  } = field;

  if (_when && !_when(this)) {
    return null;
  }

  /*
   * NOTE:
   * Different types of Unique Fns.:
   * - Dependent on other fields, and "specific" to this Activation Wizard
   *  + Fields dependent on other fields, need states.dirty/props
   *  + So, they must be having unique fn. that can be called here whenever something gets changed.
   *  + This way, all such unique fns. will be evaluated once some field is changed.
   *  + Example: '_optionsFn' is 1 such unique fn.
   *
   * - Generalised Fns. Dependent on other fields or onChange of self.
   *  + Fields which change description/info on their own value change can be generalised and handled in onChange/onFocus, etc.
   *  + onChange and onFocus are automatically called by Input Field component.
   *  + Cases like description need to be called on onChange and also on render. So, this must be handled in render of Input Field component.
   *  + BUT, here, re-render of dirty won't automatically trigger description of other fields. Because it's PureComponent. So, _autoRenderImpure is used to watch the state of parent Component.
   * */

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

  let isComponentDisabled = isSubmitFormDisabled(this.props.data); // If form cannot be submitted, then all fields are disabled.

  // TODO: Ideally, what's disabled cannot be 'required = true'. Currently no such requirement. To handle, support 'required' as a function
  if (_disabledWhen && _disabledWhen(this)) {
    // Overiride the value if it's disabled
    isComponentDisabled = true;
  }

  return (
    <Component
      key={key}
      data-name={_name}
      defaultValue={defaultValue}
      disabled={isComponentDisabled}
      autoRender={_autoRenderImpure}
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
    field.autoFocus = true; // To autofocus first unfilled required field

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
      <div class="SubmitForm-modal">
        <main-title>
          <Button
            class="device--mobile btn--back"
            iconBefore="arrow-back"
            onClick={this.props.closeActivationForm}
          />
          SUBMIT FORM
        </main-title>

        <div class="SubmitForm-content">
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
                onClick={() =>
                  onAction && onAction.trackLinkClick('Terms of use')
                }
              >
                Terms & Conditions
              </a>,{' '}
              <a
                href="https://razorpay.com/agreement/"
                target="_blank"
                class="highlight"
                onClick={() =>
                  onAction && onAction.trackLinkClick('Merchant Agreement')
                }
              >
                Merchant Agreement
              </a>{' '}
              and the{' '}
              <a
                href="https://razorpay.com/privacy/"
                target="_blank"
                class="highlight"
                onClick={() =>
                  onAction && onAction.trackLinkClick('Privacy Policy')
                }
              >
                Privacy Policy
              </a>. By submitting the form, I agree to abide by the rules at all
              times.
            </p>
          </div>

          {/* Secondary copy */}
          <p class="text-fade">
            Please review the form before submitting. For any changes after
            submission, you can contact us at support@razorpay.com.
          </p>

          {/* Action button */}
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
