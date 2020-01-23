import { Link } from 'react-router-dom';
import Form from 'common/new-ui/Form';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Alert from 'common/new-ui/Alert';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { autoPrefixUrls, isPresent, prevent } from 'common/utils/rzp-utils';
import { trackDiffInFormFields } from 'merchant/utils/track-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import { classList } from 'common/utils/rzp-utils';
import {
  addDropShield,
  removeDropShield,
} from 'merchant/components/File/Upload';
import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';

import mainFormTabsContent, {
  mainFormTabs,
  mainFormFieldNamesMeta,
  getBusinessTypeOptions,
} from './ActivationFormMap';
import accountFormTabsContent, {
  accountFormTabs,
  accountFormFieldNamesMeta,
} from './AccountActivationFormMap';
import BingDataObj from 'common/utils/bingDataObj';
import * as trackers from 'merchant/containers/Activation/ga_new';
import RTracking from 'react-tracking';
import L1FormFieldNames from './L1FormFieldNames';
import { trackTnCClick } from 'merchant/containers/Activation/ga_new';
import { updateSession } from 'merchant/reducers/session';
import {
  showInstantActivationSuccessModal,
  showKYCDetailsModal,
  showPANStatusModal,
  showKYCStatusModal,
} from 'merchant/reducers/home';
import {
  submitL1Form,
  submitL1FormSuccess,
} from 'merchant/reducers/activationWizard';
import User from 'merchant/models/User';
import { withRouter } from 'react-router-dom';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  validatePANCardUnregBiz,
  validateCompanyAB,
} from 'common/utils/validators';

import {
  L1FormSuccess,
  L1FormError,
  updateHubSpotContactsProperties,
  UNREGISTERED_TYPES,
  isL1Completed,
  hasSelectedBlacklistedCategory,
} from './ActivationUtils';
import QueryString from 'query-string';
import { getNeedsClarificationTabsData } from './NeedsClarificationFormMap';

/*
*             Main-form        LA-form
* Submited      E F ~S        ~E ~F ~S
* Activated     E F ~S        ~E ~F ~S
* Locked      ~E ~F ~S        ~E ~F ~S (Takes priority)
*
* E = Can edit
* F = Footer
* S = Show 'submit form' (view,) tab and button
* */

let onAction = trackers;

const LOADING = {
  ERROR: -1, // Error = show error msg
  SUCCESS: 1, // Success = show success msg
  PENDING: 0, // Pending = show spinner
  INITIAL: null, // Initial = hide spinner
  DEFAULT: 2, // Some custom message when form opens
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

  if (f.hasOwnProperty('validator') && typeof f.validator === 'function') {
    f.validator = f.validator.bind(self); // Field dependent on other field must auto update its validator. Recommended to use with `_autoRenderImpure` to auto show error simultaneously as the other fiels is being updated.
  }

  if (f.hasOwnProperty('onBlur') && typeof f.onBlur === 'function') {
    f.onBlur = f.onBlur.bind(self); // Control dependent field for auto-focus, etc.
  }

  if (f.hasOwnProperty('info') && typeof f.info === 'function') {
    f.info = f.info.bind(self); // Show different info based on other fields
  }

  if (!f.hasOwnProperty('autoComplete')) {
    f.autoComplete = 'off';
  }

  if (!f.hasOwnProperty('size')) {
    f.size = 'small';
  }
}

let NEEDS_CLARIFICATION_STEP; // To handle specific case for needs clarification screen
let DOCUMENT_UPLOAD_STEP; // To handle specific case for document step
let BANK_ACCOUNT_TAB; // To handle specific case for bank account step
const BUSINESS_TYPE_FORM_STEP = 1; // If NGO is selected, then Document Upload would have 2 more fields
const BUSINESS_DETAILS_STEP = 2;
let FORM_TABS; // Maintains naming of the tabs
let FORM_TABS_CONTENT; // Actual tab content corresponding to FORM_TABS
let FORM_TABS_NAMES; // All fields names in the FORM_TABS_CONTENT

const SAVE_BUTTON_DISABLED_STEPS = [BUSINESS_DETAILS_STEP];

@withRouter
@connect(
  state => ({
    session: state.session,
    user: state.session.user,
  }),
  {
    showNotification,
    updateSession,
    showInstantActivationSuccessModal,
    showKYCDetailsModal,
    showPANStatusModal,
    submitL1Form,
    submitL1FormSuccess,
    showKYCStatusModal,
  }
)
@RTracking(() => window.rzpQ.component('ActivationWizard'))
export default class ActivationWizard extends React.Component {
  state = {
    isSaving: this.isLinkedAccountForm ? LOADING.DEFAULT : LOADING.INITIAL,
    dirty: {},
    tabs: [],
    same_address:
      this.props.data &&
      this.props.data.business_operation_pin ==
        this.props.data.business_registered_pin
        ? '1'
        : '0', // '1' => checkbox ticked
    has_url:
      this.props.data && this.props.data.business_website === '' ? '1' : '0', // '0' => 0th radio button, value exists
    has_gstin: this.props.data && this.props.data.gstin === '' ? '1' : '0', // '0' => 0th radio button, value exists
    account_no: this.props.data && this.props.data.bank_account_number,
    activeTab: 0, // Fallback for all cases.
    callingApi: false,
    address_proof: 'aadhar',
    needsClarification: {},
  };
  constructor(props) {
    super(props);
    this.prepareTabs(props);
    this.setInitialTab();

    if (this.isLinkedAccountForm) {
      onAction = null; // Only tracking for main activation form
    } else {
      // recording new activation form in hotjar for New accounts (non-LA account)
      if (typeof window.hj === 'function') {
        window.hj('trigger', 'activation_form_open');
        window.hj('tagRecording', ['activation_form_open']);
      }
    }

    this.formName =
      props.user.showInstantActivation &&
      props.user.instantActivation.isL1Submitted
        ? 'KYC Form'
        : 'Activation Form';

    this.formDescription =
      props.user.showInstantActivation &&
      props.user.instantActivation.isL1Submitted
        ? 'Complete and submit the form to enable settlements.'
        : 'Complete and submit the form to accept payments.';
  }
  isNeedsClarificationMode() {
    return this.props.data.activation_status === 'needs_clarification';
  }
  isOnKYCTab() {
    return this.state.activeTab === NEEDS_CLARIFICATION_STEP;
  }
  prepareTabs(props) {
    const { tracking } = props;
    if (this.isLinkedAccountForm) {
      // Activation form for linked account

      FORM_TABS = [...accountFormTabs];
      FORM_TABS_CONTENT = [...accountFormTabsContent];
      FORM_TABS_NAMES = [...accountFormFieldNamesMeta];
      BANK_ACCOUNT_TAB = 1;
      DOCUMENT_UPLOAD_STEP = 2;
      // Removing document upload
      if (!props.data.need_kyc) {
        FORM_TABS.splice(DOCUMENT_UPLOAD_STEP, 1);
        FORM_TABS_CONTENT.splice(DOCUMENT_UPLOAD_STEP, 1);
        FORM_TABS_NAMES.splice(DOCUMENT_UPLOAD_STEP, 1);

        DOCUMENT_UPLOAD_STEP = null; // To set 'Document Upload Step' is not available.
      }
    } else {
      // Main Activation form for merchant

      FORM_TABS = mainFormTabs;
      FORM_TABS_CONTENT = mainFormTabsContent;
      FORM_TABS_NAMES = mainFormFieldNamesMeta;

      BANK_ACCOUNT_TAB = 3;
      DOCUMENT_UPLOAD_STEP = 4;
      if (
        props.data['activation_status'] === 'needs_clarification' &&
        props.data['kyc_clarification_reasons']
      ) {
        if (FORM_TABS.indexOf('Needs Clarification') === -1) {
          FORM_TABS.push('Needs Clarification');
        }
        const ndcFields =
          getNeedsClarificationTabsData(
            mainFormTabsContent,
            props.data.kyc_clarification_reasons
          ) || [];
        FORM_TABS_CONTENT.push(ndcFields);
        FORM_TABS_NAMES.push(
          ndcFields.map(f => f.name).filter(f => Boolean(f))
        );
        NEEDS_CLARIFICATION_STEP = 5;
      }
      if (!isL1Completed(this)) {
        //Code needs some refactoring
        FORM_TABS = FORM_TABS.slice(0, BANK_ACCOUNT_TAB);
        FORM_TABS_CONTENT = FORM_TABS_CONTENT.slice(0, BANK_ACCOUNT_TAB);
        FORM_TABS_NAMES = FORM_TABS_NAMES.slice(0, BANK_ACCOUNT_TAB);
        DOCUMENT_UPLOAD_STEP = null;
      }

      // Business Category in "Business Model" exists in main activation form. Setting value dynamically from props.
      FORM_TABS_CONTENT[1][1][0].options = ['--Select--'].concat(
        Object.keys(props.categories).map(c => ({
          name: c,
          label: props.categories[c].description,
        }))
      );

      // Set Biz type options dynamically based on current activation stage
      FORM_TABS_CONTENT[1][0].options = getBusinessTypeOptions(this);
    }

    DOCUMENT_UPLOAD_STEP &&
      SAVE_BUTTON_DISABLED_STEPS.push(DOCUMENT_UPLOAD_STEP);
    defaultFieldProps.call(this, FORM_TABS_CONTENT); // Set the default props for all tab content views

    const prepareFileFields = a => {
      if (a._cmp === undefined || a._cmp === Input.File) {
        a._cmp = Input.File;
        a._accept = ['pdf', 'image'];
        a._showAcceptInfo = false;
        a._showStagedFileStatus = false;

        if (!a.hasOwnProperty('required')) {
          a.required = true;
        }

        a.onChange = (file, progressTracker) => {
          const filename = a.getName ? a.getName(this) : a.name;
          tracking.trackEvent(
            window.rzpQ.onbr().initiated(`kyc.upload_document_${filename}`, {
              name: filename,
            })
          );

          return props
            .saveFile(
              filename,
              file,
              progressTracker,
              a.destinationUrl || null,
              a.uploadAs || null
            )
            .then(() => {
              updateHubSpotContactsProperties({
                [filename]: true,
              });

              tracking.trackEvent(
                window.rzpQ.onbr().initiated('kyc.upload_document', {
                  name: filename,
                })
              );

              this.markTabIfActive(DOCUMENT_UPLOAD_STEP);
              this.updateFileInDirty(filename);
            });
        };
      }
    };
    /*
    * All document fields in activation form to have same footprint.
    * Adding onChange listener to all document upload fields.
    * */

    DOCUMENT_UPLOAD_STEP &&
      FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP].forEach(prepareFileFields);
    NEEDS_CLARIFICATION_STEP &&
      FORM_TABS_CONTENT[NEEDS_CLARIFICATION_STEP].forEach(prepareFileFields);
  }

  componentDidUpdate() {
    return this.props.handleUIUpdate && this.props.handleUIUpdate();
  }

  updateFileInDirty = filename => {
    this.setState(prevState => ({
      dirty: { ...prevState.dirty, [filename]: 'fakepath' },
    }));
  };

  componentDidMount() {
    addDropShield('.Activation--wizard');

    if (!this.props.user.isAccepted) {
      fireAnalyticsEvents({
        fbData: 'KYC_start',
        liData: 987420,
        twiData: 'o1ua3',
      });
      updateHubSpotContactsProperties({ started: true });
    }

    const query = QueryString.parse(this.props.location.search);
    this.handleActionBasedOnQuery(query);
  }

  handleActionBasedOnQuery = query => {
    if (query['auto-submit'] == 'l1-form') {
      const submitButton = document.querySelector(
        'footer button[name=submit-and-verify]'
      );
      if (submitButton) {
        submitButton.click();
      }
    }
  };

  componentWillUnmount() {
    removeDropShield('.Activation--wizard');
    this.unMounted = true; // Used while using this.goto to update state while closing modal
  }

  setInitialTab() {
    let firstInValid = null;
    let isFormSubmitted = !!this.props.data.submitted; // Linked accounts form can still be seen after activation.

    for (let i = 0; i < FORM_TABS.length; i++) {
      let tabStatusValid = this.tabValidity(i);

      if (!tabStatusValid && firstInValid === null) {
        firstInValid = i;
      }
      this.state.tabs[i] = tabStatusValid; // Mark tabs as valid-invalid (IMPORTANT STEP)
    }

    // If main Form is not touched ever, then set initial tab = 0
    if (!this.isLinkedAccountForm && this.props.isFormTouched === false) {
      firstInValid = 0;
    }

    if (firstInValid === null) {
      firstInValid = FORM_TABS.length - 1; // In case all are filled then set last tab(which is actually filled)

      if (!this.isLinkedAccountForm && this.isIndividualTypeLock) {
        // For non-LA account
        firstInValid = 1; // Business Overview tab
      } else {
        !isFormSubmitted &&
          isL1Completed(this) &&
          (this.state.showSubmitLayer = true); // Directly show submit form if it's NOT activated/locked/submitted
      }
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
    const tracker = () =>
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().initiated('kyc.save_modifications', {
          clickSource: 'save',
        })
      );
    const callBack =
      onAction &&
      function(result, error) {
        tracker();
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
    const tracker = () =>
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().initiated('kyc.save_modifications', {
          clickSource: 'save-next',
        })
      );
    const callBack =
      onAction &&
      function(result, error) {
        tracker();
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
    const tracker = () =>
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().initiated('kyc.nav_action', {
          clickSource: mainFormTabs[tabId],
        })
      );
    const callBack =
      onAction &&
      function(result, error) {
        onAction.trackTabClick(tabId); // Tracks current tab clicked
        tracker();
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
  @RTracking((props, state) => {
    try {
      const activation = { props, state };
      const { tracking } = props;
      if (isL1Completed(activation)) {
        const fields = trackDiffInFormFields(props.data, state.dirty);
        return fields.forEach(field =>
          tracking.trackEvent(
            window.rzpQ.onbr().initiated('kyc.provide_details', {
              ...field,
            })
          )
        );
      }
    } catch (err) {}
  })
  goto = async (newActiveTab, cb) => {
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

    const currentActive = this.state.activeTab; // currentActive = The tab of which dirty data is saved
    const savingWhichTab = currentActive;

    newActiveTab = newActiveTab != null ? newActiveTab : currentActive; // currentActive tab remains as newActiveTab (To handle Save btn click and 'Submit Form' tab click).

    this.setState({
      activeTab: newActiveTab,
    });

    let shouldSave = Object.keys(this.state.dirty).length ? true : null;
    if (!shouldSave) {
      this.updateFEOnlyValues();

      this.markTabIfActive(savingWhichTab); // To update changes happened due to FE-only fields change, like has_gstin and has_url.

      cb && cb(); // If clicked on Save/Save-Next btn without any change
      return;
    }

    const currentDirty = this.state.dirty;
    const reqData = {};

    /* Send only those fields which belongs to the TAB being saved */
    Object.keys(currentDirty).forEach(name => {
      if (
        currentDirty.hasOwnProperty(name) &&
        FORM_TABS_NAMES[currentActive].indexOf(name) !== -1
      ) {
        // Saving only the fields corresponding to currentActive tab.
        const fieldVal = currentDirty[name];
        reqData[name] = fieldVal;

        // For business website empty string => user don't have website. null => user didn't attempt the field.
        const allowEmptyString = ['business_website', 'gstin'];
        if (allowEmptyString.indexOf(name) === -1) {
          reqData[name] = reqData[name] === '' ? null : fieldVal; // '' -> null. DB has default values as NULL.
        }
      }
    });

    if (!Object.keys(reqData).length) {
      return; // Nothing changed on the currentActive Tab, although the data do exist in dirty
    }

    this.setState(
      {
        isSaving: LOADING.PENDING,
      },
      () => {
        window.clearTimeout(this.loaderTimeout); // Reset the previous removeLoader-call timer on each new Pending
      }
    );

    /* Check validity of 'Bank account no.' before saving */
    if (currentActive == BANK_ACCOUNT_TAB) {
      if (
        reqData.hasOwnProperty('bank_account_number') &&
        (!reqData.bank_account_number ||
          reqData.bank_account_number != this.state.account_no)
      ) {
        this.handleBankAccountMismatch(newActiveTab !== currentActive); // Tab is changed

        return; // Don't make api call if mismatch
      }
    }

    // '' -> null inside state.dirty for easy OLD and LATEST data comparison. Also, update dirty to the last saved
    this.setState({
      dirty: { ...currentDirty, ...reqData }, // dirty must be exact same as per above modifications
    });

    /* Following is api call and post response handling */
    const savingDataOfWhichTab = { ...reqData };

    this.props.save(reqData).then(data => {
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

        // Track session for any error on submission
        if (!this.isLinkedAccountForm && typeof window.hj === 'function') {
          window.hj('tagRecording', ['activation_form_save_error']);
        }

        // TODO: This is to avoid too many api calls and consequent ERROR even when user is not intending to save.
        if (savingWhichTab && savingWhichTab !== this.state.activeTab) {
          const latestDirty = { ...this.state.dirty };
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
          isSaving: LOADING.ERROR,
        });

        this.removeLoader();

        // Track abrupt state change
        if (this.state.isSaving !== LOADING.PENDING) {
          if (!this.isLinkedAccountForm && typeof window.hj === 'function') {
            window.hj('tagRecording', ['activation_form_save_abrupt']);
          }
        }
      } else {
        cb && cb(true);

        updateHubSpotContactsProperties(reqData);

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
          isSaving: LOADING.SUCCESS,
        });

        this.removeLoader(3000);

        // Track abrupt state change (non-LA account)
        if (this.state.isSaving !== LOADING.PENDING) {
          if (!this.isLinkedAccountForm && typeof window.hj === 'function') {
            window.hj('tagRecording', ['activation_form_save_abrupt']);
          }
        }
      }
    });
  };

  /*
  * Fn. to keep _name fields(FE-only fields) in sync with updated values(props.data) on tab change.
  * + Checking/Unchecking/Changing _name FE fields will remain as it is throughout(in state). But changing them might not always save data.
  * + Example: Changing 'has_gstin' from 1 -> 0 (not have-> have) but value is not filled, then tab change won't save anything. So next time, tab is selected, radio box must display as per saved value, not last state value.
  * + Example: If `same_address` ticked but values not saved due to some reason.
  * */
  updateFEOnlyValues() {
    // Step 1:
    /*
    *  Don't update FE-only values like has_gstin / has_url, cuz Input.
    *  Radio is not externally controlled, so updating state will just update has_gstin and has_url but not the Radio buttons' view and state.
    * */

    // Step 2:
    this.setState({
      same_address:
        this.props.data.business_operation_pin ==
        this.props.data.business_registered_pin
          ? '1'
          : '0', // '1' => checkbox ticked
    });
  }

  get isLinkedAccountForm() {
    return !!this.props.accountId;
  }

  get isIndividualTypeLock() {
    const { user } = this.props;
    const businessType =
      this.state.dirty.business_type || this.props.data.business_type;
    return (
      !!UNREGISTERED_TYPES[Number(businessType)] && !user.isUnregBizFlowEnabled
    );
  }

  get isUnregBiz() {
    const businessType =
      this.state.dirty.business_type || this.props.data.business_type;

    return businessType == 2 || businessType == 11;
  }

  get isFormLocked() {
    return !!this.props.data.locked || this.isNeedsClarificationMode();
  }

  get hasFilledClarificationDetails() {
    if (this.isOnKYCTab()) {
      const state = this.state;
      const dynamicFieldName = {
        address_proof_front: () => {
          return `${state.address_proof}_front`;
        },
        address_proof_back: () => {
          return `${state.address_proof}_back`;
        },
      };
      const hasFilledEverything = FORM_TABS_NAMES[
        NEEDS_CLARIFICATION_STEP
      ].every(field => {
        if (dynamicFieldName[field]) {
          field = dynamicFieldName[field]();
        }
        return Boolean(state.dirty[field]);
      });

      return hasFilledEverything;
    }
  }

  get canSubmitL1Form() {
    const promoterPan =
      this.state.dirty['promoter_pan'] || this.props.data['promoter_pan'];
    let showCompanyName = this.props.user.isCompanyNameHiddenRazorX,
      businessName =
        this.state.dirty['business_name'] || this.props.data['business_name'],
      contactName =
        this.state.dirty['contact_name'] || this.props.data['contact_name'];

    return (
      !hasSelectedBlacklistedCategory(this) &&
      (this.isUnregBiz
        ? promoterPan && !validatePANCardUnregBiz(promoterPan)
        : true) &&
      (this.isUnregBiz
        ? true
        : !validateCompanyAB(businessName, contactName, showCompanyName))
    );
  }

  /*
  * Handle Account No. re-enter match before saving.
  * It mimicks loader used for API to handle cases if tab is changed.
  * */
  handleBankAccountMismatch(isTabSwitched) {
    if (isTabSwitched) {
      const latestDirty = { ...this.state.dirty };

      delete latestDirty['bank_account_number']; // Remove 'bank_account_number', so there is no attempt to save repeatedly

      this.setState({
        dirty: latestDirty,
        account_no: this.props.data && this.props.data.bank_account_number, // Reset account_no
      });
    }

    /*
     Not resetting state.dirty / account_no if tab is not changed.
    * */

    // Track FE error for bank account mismatch (non-LA account)
    if (!this.isLinkedAccountForm && typeof window.hj === 'function') {
      window.hj('tagRecording', ['activation_form_save_error']);
    }

    setTimeout(() => {
      this.setState({
        isSaving: LOADING.ERROR, // Mimick api behavior on FE
      });

      this.removeLoader();
    }, 500); // Let loader be seen for 0.5 sec
  }

  updateSession(data) {
    const { session, accountId } = this.props;

    // Update data
    this.setState({ data });

    // Session need not be updated if it's linked account form
    if (accountId) {
      return;
    }

    if (data.can_submit) {
      this.preloadSuccessAsset(); // TODO: Check where is it declared
    }

    const {
      activation_progress,
      activated,
      activation_status,
      activation_flow,
      submitted,
      international,
      poi_verification_status,
      promoter_pan_name,
      promoter_pan,
      business_type,
    } = data;

    // Updating % activation_progress (side bar) and other important activation fields
    const user = (this.user = new User({
      ...session.user,
      activation_progress,
      activated,
      activation_status,
      activation_flow,
      international,
      poi_verification_status,
      promoter_pan,
      promoter_pan_name,
      business_type,
      submitted: +submitted,
    }));

    this.props.updateSession({
      user,
      mode: session.mode,
    });
  }

  trackSubmitL1 = data => {
    const { tracking } = this.props;
    try {
      isPresent(data) &&
        Object.keys(data).forEach(dataKey =>
          tracking.trackEvent(
            window.rzpQ.onbr().initiated('act.provide_act_details', {
              value: data[dataKey],
              name: dataKey,
            })
          )
        );
    } catch (e) {}
  };

  submitL1 = async currenActiveTab => {
    const data = this.formData;

    this.trackSubmitL1(data);

    this.setState({ callingAPI: true });

    try {
      let response = await this.props.submitL1Form({
        data,
        accountId: this.props.accountId,
      });

      this.props.submitL1FormSuccess({ data: response.data });

      if (this.onActivationSuccess) {
        return this.onActivationSuccess(response);
      }

      this.updateSession(response.data); // Updating % activation_progress (side bar)

      const {
        activation_flow,
        business_type,
        poi_verification_status,
        instantActivation,
      } = this.user;
      const {
        showPANStatusModal,
        showKYCDetailsModal,
        showInstantActivationSuccessModal,
        tracking,
      } = this.props;
      const props = {
        activation_flow,
        instantActivation,
        business_type,
        poi_verification_status,
        showKYCDetailsModal,
        showPANStatusModal,
        showInstantActivationSuccessModal,
        tracking,
      };

      L1FormSuccess(props);
      this.saveCurrentTab();

      this.setState({ callingAPI: false }, () => {
        if (
          poi_verification_status != 'incorrect_details' &&
          poi_verification_status != 'not_matched'
        ) {
          return this.props.history.replace(`/`);
        }
      });
      return response;
    } catch (err) {
      this.setState({ callingAPI: false });
      this.saveCurrentTab();
      if (err.errors && err.errors.length && err.errors[0]) {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      }

      L1FormError();

      // if (this.onActivationSuccess) {
      //   this.onActivationSuccess({ success: false });
      // }

      return err;
    }
  };

  get formData() {
    const currentDirty = this.state.dirty;
    const reqData = {};

    L1FormFieldNames.forEach(field =>
      this.populateReqData(field, reqData, currentDirty)
    );

    if (!Object.keys(reqData).length) {
      return; // Nothing changed on the currentActive Tab, although the data do exist in dirty
    }

    // If it is a registered biz then promoter_pan_name should not be sent
    if (!this.isUnregBiz) {
      delete reqData.promoter_pan_name;
    }

    return reqData;
  }

  populateReqData(field, reqData, currentDirty) {
    const name = field;

    if (!name) {
      return;
    }

    const fieldVal =
      name in currentDirty ? currentDirty[name] : this.props.data[name];

    reqData[name] = fieldVal;

    // For business website empty string => user don't have website. null => user didn't attempt the field.
    const allowEmptyString = ['business_website', 'gstin'];
    if (allowEmptyString.indexOf(name) === -1) {
      reqData[name] = reqData[name] === '' ? null : fieldVal; // '' -> null. DB has default values as NULL.
    }
  }

  submitForm = () => {
    return this.props.submitForm().then(data => {
      if (data.errors) {
        // Track session for any error on submission (non-LA account)
        if (!this.isLinkedAccountForm && typeof window.hj === 'function') {
          window.hj('tagRecording', ['activation_form_save_error']);
        }

        onAction &&
          onAction.trackSubmit({
            error: data.errors,
            type: false,
          });
      } else {
        let compAllData = new BingDataObj('kycform', 'complete', 'all', 1);

        /**
         * Fire fb, bing, linkedin, quora, reddit & twitter events
         */
        fireAnalyticsEvents({
          fbData: 'kyc_complete_all',
          bingData: compAllData,
          liData: 987452, //conversionId
          twiData: 'o1ua7', //twitter
          quoraData: 'kyc_complete_all',
          redditData: 'AddToCart',
        });

        let conversionId, txnId;
        if ('greylist' === data.data.activation_flow) {
          conversionId = 987428;
          txnId = 'o1ua4';
          let greylistData = new BingDataObj(
            'kycform',
            'complete',
            'greylist',
            1
          );
          fireAnalyticsEvents({
            fbData: `KYC_complete_greylist`,
            bingData: greylistData,
            liData: conversionId,
            twiData: txnId,
          });
        } else if ('whitelist' === data.data.activation_flow) {
          conversionId = 987436;
          txnId = 'o1ua5';
          let whitelistData = new BingDataObj(
            'kycform',
            'complete',
            'whitelist',
            1
          );
          fireAnalyticsEvents({
            fbData: `KYC_complete_whitelist`,
            bingData: whitelistData,
            liData: conversionId,
            twiData: txnId,
          });
        }

        updateHubSpotContactsProperties(
          {
            final_submission: true,
          },
          {
            account_status: data.data.activation_status,
          }
        );

        onAction &&
          onAction.trackSubmit({
            type: true,
            activationFlow: data.data && data.data.activation_flow,
          });

        window.hj && window.hj('trigger', 'L0_NPS_Post_KYC');
      }
    });
  };

  submitClarifications = async () => {
    const needsClarificationFields = FORM_TABS_CONTENT[this.state.activeTab];

    const hasFilledDetails = this.hasFilledClarificationDetails;
    const reqData = {
      submit: '1',
    };

    if (hasFilledDetails) {
      const fieldNames = needsClarificationFields.map(field => field.name);
      fieldNames.forEach(fieldName => {
        if (this.state.dirty[fieldName]) {
          reqData[fieldName] = this.state.dirty[fieldName];
        }
      });
    }

    // State will contain file fields which have already been uploaded
    // Delete file field from request data
    Object.keys(reqData).forEach(key => {
      if (reqData[key] === 'fakepath') {
        delete reqData[key];
      }
    });

    try {
      this.setState({ callingAPI: true });
      const response = await this.props.save(reqData);
      if (response.success) {
        this.props.showKYCStatusModal({
          modalType: 'KYC_CLARIFICATION_SUBMIT_MODAL',
        });
        this.props.history.replace(`/`);
      }
      return response;
    } catch (err) {
      if (err.errors && err.errors.length && err.errors[0]) {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      }
      return err;
    } finally {
      this.setState({ callingApi: false });
    }
  };

  /*
  * Fadeout based loader text.
  * Default delay = 7 sec
  * */
  removeLoader = delay => {
    this.loaderTimeout = setTimeout(() => {
      this.setState({ isSaving: LOADING.INITIAL });
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
      sideEffectFieldsToUpdate['gstin'] = '';
    } else if (stateName === 'has_url' && fieldValue === '1') {
      sideEffectFieldsToUpdate['business_website'] = '';
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
          [fieldName]: fieldValue,
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

    if (!this.isLinkedAccountForm && isValid && this.isIndividualTypeLock) {
      // For non-LA account
      isValid = false;
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
    const isFormLocked = this.isFormLocked;
    const isFormActivated = !!this.props.data.activated;
    const isFormSubmitted = !!this.props.data.submitted;

    const { tracking } = this.props;

    let activeTab = this.state.activeTab;
    activeTab = activeTab < 0 || !activeTab ? 0 : activeTab; // Graceful failure in case activeTab becomes negative. To handle non-reproducible weird error.

    const isCurrentTabValid = this.state.tabs[activeTab];

    let isLastTab = activeTab == FORM_TABS.length - 1;

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
    let moreTabs = [];
    if (this.props.user.instantActivation.isL1Submitted && !isFormSubmitted) {
      moreTabs.push(
        <li
          key="submit-tab"
          onClick={
            this.isIndividualTypeLock ? undefined : this.toggleSubmitLayer
          }
          class={classList(
            (!this.isAllTabsValid() || this.isIndividualTypeLock) && 'disabled',
            this.state.showSubmitLayer && 'active',
            'li--submit'
          )}
        >
          Submit Form
          {!this.isAllTabsValid() && (
            <div class="description small">Complete the form to submit</div>
          )}
        </li>
      );
    }

    return (
      <div class="Activation--wizard Wizard">
        {/* Activation form tabs */}
        <ModalAsideNav
          title={this.formName}
          description={
            !this.isLinkedAccountForm &&
            !isFormSubmitted && <p>{this.formDescription}</p>
          }
          tabs={FORM_TABS}
          moreTabs={moreTabs}
          tabsValidity={this.state.tabs}
          tabClickHandler={this.changeTab}
          activeTab={activeTab}
          activeTabContdition={!this.state.showSubmitLayer}
          disableTabCondition={tabId => {
            return (
              !this.isLinkedAccountForm &&
              this.isIndividualTypeLock &&
              [2, 3, 4].indexOf(tabId) > -1
            );
          }}
        />

        {/* Activation form Content */}
        <main
          class={classList(
            'form-container',
            this.state.showSubmitLayer && 'block-scroll',
            isFormLocked && 'main--full'
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

          {/* Alert: For linked account if activated */}
          {this.isLinkedAccountForm &&
            isFormActivated && (
              <Alert.Info iconBefore="i-done-all">
                The account has been activated
              </Alert.Info>
            )}

          {/* Alerts: for MAIN activation form */}
          {do {
            const showFormDisabledAlert =
              !this.isLinkedAccountForm && (isFormLocked || isFormSubmitted); // '|| isFormActivated' is redundant check. Always covered by isFormSubmitted;

            const { data } = this.props;
            let Component = Alert.Info;
            let icon, msg;

            let secondaryMsg = 'For any clarifications, you can';
            const ticketLink = <Link to="#ticket">write to support</Link>;

            if (showFormDisabledAlert && !this.isOnKYCTab()) {
              if (isFormActivated) {
                // **1. Alert: Account Activated

                icon = 'i-done-all';
                msg = 'Your account is activated.';
                secondaryMsg = (
                  <React.Fragment>
                    For any changes, please {ticketLink}.
                  </React.Fragment>
                );
              } else if (this.isNeedsClarificationMode()) {
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
                    In case of any queries, please {ticketLink}
                  </React.Fragment>
                );
              } else if (data.activation_status === 'rejected') {
                // **3. Alert: Form Rejected

                icon = 'i-close';
                Component = Alert.Error;
                msg =
                  'Your activation form has been rejected by our partner banks. Hence, we would not be able support your business at this moment.';
                secondaryMsg = 'We have sent you an email with the details.';
              } else if (isFormLocked && isFormSubmitted) {
                // **4. Alert: Form is Locked (for reasons other than above)
                // 'locked' status has more priority than 'submitted'
                // If admins locked form before submiddion, then this alert is not shown

                icon = 'i-outline-lock';
                msg =
                  'Your activation form is under review. We will let you know once your account gets activated.';
                secondaryMsg = (
                  <React.Fragment>
                    In case of any queries, please {ticketLink}
                  </React.Fragment>
                );
              } else if (isFormSubmitted) {
                // **5. Alert: Form is Submitted

                icon = 'i-check';
                msg = `Our team will review the form and submitted documents.`;
                secondaryMsg =
                  'We will reach out on your contact email for all updates.';
              }

              {
                msg && (
                  <Component iconBefore={icon}>
                    {msg}
                    <div class="side-description">{secondaryMsg}</div>
                  </Component>
                );
              }
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
          <ShowWhen
            additionalCondition={user =>
              user.isOrgAllowedFunctionality('external_links') &&
              user.isUnregBizFlowEnabled &&
              this.state.activeTab == 2 &&
              !this.props.user.instantActivation.isL1Submitted
            }
          >
            <div className="subfooter">
              By submitting this form you agree to our{' '}
              <a
                className="text-primary"
                target="_blank"
                href="https://razorpay.com/terms/"
                onClick={trackTnCClick}
              >
                Terms and Conditions
              </a>
            </div>
          </ShowWhen>
        </main>

        {/* Submit form overlay view, Lock check not necessary here. Just ensured, 'Submit Form' checkbox must be disabled if locked */}
        {!isFormSubmitted &&
          this.state.showSubmitLayer && (
            <main
              class={classList(
                'overlay-container',
                isFormLocked && 'main--full'
              )}
            >
              <SubmitForm
                closeActivationForm={() => {
                  this.goto(FORM_TABS.length - 1);
                }}
                isFormLocked={isFormLocked}
                isLinkedAccount={this.isLinkedAccountForm}
                submitActvationForm={this.submitForm}
              />
            </main>
          )}

        {/* Form Footer, to show actions btns / saving state */}
        {!isFormLocked && (
          <footer>
            {/* Spinner state */}
            <Loader
              isSaving={this.state.isSaving}
              defaultMsg={this.props.defaultMsg}
            />
            {!this.state.showSubmitLayer && (
              <React.Fragment>
                {/* Action Button 1 */}
                {SAVE_BUTTON_DISABLED_STEPS.indexOf(activeTab) === -1 && (
                  <Button onClick={this.saveCurrentTab}>Save</Button>
                )}

                {/* Action Button 2 */}
                {isLastTab ||
                  ((this.isLinkedAccountForm || !this.isIndividualTypeLock) && (
                    <Button.Primary
                      iconAfter="chevron-right"
                      onClick={this.next}
                    >
                      <span class="device--desktop">Save & Next</span>
                      <span class="device--mobile">Next</span>
                    </Button.Primary>
                  ))}

                {/* Action Button 3 */}
                {isLastTab &&
                  activeTab == BUSINESS_DETAILS_STEP && (
                    <AsyncBtn.Primary
                      disabled={!this.canSubmitL1Form || this.state.callingAPI}
                      onClick={this.submitL1}
                      pendingState={
                        this.isUnregBiz ? 'Verifying' : 'Submitting'
                      }
                      name={'submit-and-verify'}
                    >
                      {this.isUnregBiz ? 'Submit and Verify' : 'Submit'}
                    </AsyncBtn.Primary>
                  )}

                {/* Action Button 4 */}
                {isLastTab &&
                  !isFormSubmitted &&
                  this.props.user.instantActivation.isL1Submitted &&
                  !this.props.user.instantActivation.isBlacklistFlow && (
                    <Button.Primary
                      disabled={!this.isAllTabsValid()}
                      onClick={() => {
                        tracking.trackEvent(
                          window.rzpQ.onbr().initiated('kyc.save_documents')
                        );
                        this.toggleSubmitLayer();
                      }}
                    >
                      Submit Form
                    </Button.Primary>
                  )}
              </React.Fragment>
            )}
          </footer>
        )}
        {this.isOnKYCTab() && (
          <footer>
            <AsyncBtn.Primary
              disabled={
                !this.hasFilledClarificationDetails || this.state.callingApi
              }
              onClick={this.submitClarifications}
              pendingState={'Submitting...'}
              name={'Submit Clarifications'}
            >
              Submit Clarifications
            </AsyncBtn.Primary>
          </footer>
        )}
      </div>
    );
  }

  // returns validity
  tabValidity(i) {
    //Special handling for NDC tab
    if (i === NEEDS_CLARIFICATION_STEP) {
      return false;
    }
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
function Loader({ isSaving, defaultMsg }) {
  if (isSaving === LOADING.INITIAL) {
    return <span class="Loader" />;
  }

  return (
    <span class="Loader Loader--visible">
      {do {
        if (isSaving === LOADING.PENDING) {
          <React.Fragment>
            <span class="spin-btn" />
            <span class="device--desktop">Saving Changes...</span>
            <span class="device--mobile">Saving</span>
          </React.Fragment>;
        } else if (isSaving === LOADING.SUCCESS) {
          <React.Fragment>
            <i class="i-check text-success" />
            <span class="text-success device--desktop">All changes saved</span>
            <span class="text-success device--mobile">Saved</span>
          </React.Fragment>;
        } else if (isSaving === LOADING.ERROR) {
          <React.Fragment>
            <i class="i-close text-danger" />
            <span class="text-danger device--desktop">
              Recent changes were not saved!
            </span>
            <span class="text-danger device--mobile">Not Saved!</span>
          </React.Fragment>;
        } else if (isSaving === LOADING.DEFAULT) {
          {
            defaultMsg;
          }
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
    required,
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
    /*
    * Dirty data is priority as user can switch tabs fast before api success, so dirty would have latest FE data but props not
    * */
    if (this.isOnKYCTab()) {
      defaultValue = this.state.dirty[key] || null;
    } else {
      defaultValue = this.state.dirty[key] || this.props.data[key];
    }
  } else if (_name) {
    defaultValue = this.state[_name];
    key = _name;
  }

  const isFormLocked = this.isFormLocked || this.state.callingAPI;

  // For LA, form is automatically locked when submitted(activated). For main form, it can be manually controlled.
  let isComponentDisabled = isFormLocked;
  // TODO: Ideally, what's disabled cannot be 'required = true'. Currently no such requirement. To handle, support 'required' as a function
  if (_disabledWhen && _disabledWhen(this)) {
    // Overiride the value if it's disabled
    isComponentDisabled = true;
  }
  //Always keep fields on needs clarification unlocked
  if (this.isOnKYCTab()) {
    isComponentDisabled = false;
  }
  // Show bank account number if it's activated/locked
  if (rest.hasOwnProperty('type') && rest.type === 'password' && isFormLocked) {
    rest.type = 'text';
  }

  if (rest.description && typeof rest.description === 'function') {
    rest.description = rest.description(this);
  }

  if (rest.getLabel) {
    rest.label = rest.getLabel(this);
  }

  if (rest.getName) {
    rest.name = rest.getName(this);
    key = rest.name;
  }

  if (rest.getPlaceholder) {
    rest.placeholder = rest.getPlaceholder(this);
  }

  if (!this.isOnKYCTab() && rest._type == 'address_proof_upload_doc') {
    const { documents } = this.props.data;
    defaultValue =
      (documents &&
        documents[`${rest.name}`] &&
        documents[`${rest.name}`][0]['id']) ||
      null;
  } else if (rest._type == 'address_proof_upload_doc') {
    defaultValue = this.state.dirty[rest.name] || null;
  }

  if (rest.isDeletable) {
    rest.onCloseClick = () => {
      this.props.deleteFile(rest.name);
    };
  }

  if (rest.checkValidityFromAPI) {
    const error = rest.checkValidityFromAPI(this);
    if (!this.state.dirty[rest.name] && error) rest.propagatedError = error;
    else rest.propagatedError = '';
  }
  return (
    <>
      {this.isOnKYCTab() &&
        rest.reasons &&
        rest.reasons.length > 0 && (
          <div className="ndc-reasons">
            {rest.reasons.map((r, i) => (
              <div key={i}>
                <i class="i i-info-circle" />
                <div>{r}</div>
              </div>
            ))}
          </div>
        )}
      <Component
        key={key}
        data-name={_name}
        defaultValue={defaultValue}
        disabled={isComponentDisabled}
        autoRender={_autoRenderImpure}
        required={typeof required === 'function' ? required(this) : required}
        {...rest}
      />
    </>
  );
}

function isFieldValid(field, activation) {
  const { props } = activation;
  let data = props.data;
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

  const name = field.getName ? field.getName(activation) : field.name;
  let value =
    data[name] ||
    (data.documents && data.documents[name] && data.documents[name][0]['id']);

  let isFieldRequired = field.required;

  if (typeof isFieldRequired === 'function') {
    isFieldRequired = isFieldRequired(activation);
  }

  if (isFieldRequired && !value) {
    field.autoFocus = true; // To autofocus first unfilled required field

    // value missing in required field
    return false;
  }

  if (
    field.name == 'promoter_pan' &&
    props.business_type == 11 &&
    !props.user.instantActivation.isL1Submitted
  ) {
    return false;
  }

  return true;
}

/*
* Submit Form opens with backdrop inside Activation form's main content
* - The activeTab keeps showing in the background
* - @props
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
              disabled={this.props.isFormLocked}
              onChange={e => {
                this.setState({
                  allowSubmit: e.target.checked,
                });

                // Track session for submitting form activity (non-LA account)
                if (
                  !this.props.isLinkedAccount &&
                  typeof window.hj === 'function'
                ) {
                  window.hj('tagRecording', ['activation_form_submitted']);
                }
              }}
            />

            {/* Primary copy */}
            <p>
              I have read and understood the{' '}
              <ShowWhen
                additionalCondition={user =>
                  user.isOrgAllowedFunctionality('external_links')
                }
              >
                <a
                  href="https://razorpay.com/terms/"
                  target="_blank"
                  class="highlight"
                  onClick={() =>
                    onAction && onAction.trackLinkClick('Terms of use')
                  }
                >
                  Terms & Conditions
                </a>
              </ShowWhen>
              <ShowWhen
                additionalCondition={user =>
                  !user.isOrgAllowedFunctionality('external_links')
                }
              >
                <span class="highlight">Terms & Conditions</span>
              </ShowWhen>
              ,{' '}
              <ShowWhen
                additionalCondition={user =>
                  user.isOrgAllowedFunctionality('external_links')
                }
              >
                <a
                  href="https://razorpay.com/agreement/"
                  target="_blank"
                  class="highlight"
                  onClick={() =>
                    onAction && onAction.trackLinkClick('Merchant Agreement')
                  }
                >
                  Merchant Agreement
                </a>
              </ShowWhen>
              <ShowWhen
                additionalCondition={user =>
                  !user.isOrgAllowedFunctionality('external_links')
                }
              >
                <span class="highlight">Merchant Agreement</span>
              </ShowWhen>{' '}
              and the{' '}
              <ShowWhen
                additionalCondition={user =>
                  user.isOrgAllowedFunctionality('external_links')
                }
              >
                <a
                  href="https://razorpay.com/privacy/"
                  target="_blank"
                  class="highlight"
                  onClick={() =>
                    onAction && onAction.trackLinkClick('Privacy Policy')
                  }
                >
                  Privacy Policy
                </a>
              </ShowWhen>
              <ShowWhen
                additionalCondition={user =>
                  !user.isOrgAllowedFunctionality('external_links')
                }
              >
                <span class="highlight">Privacy Policy</span>
              </ShowWhen>
              . By submitting the form, I agree to abide by the rules at all
              times.
            </p>
          </div>

          {/* Secondary copy */}
          <p class="text-fade">
            Please review the form before submitting. For any changes after
            submission, you can <Link to="#ticket">write to support</Link>
          </p>

          {/* Action button */}
          <AsyncBtn.Primary
            disabled={!this.state.allowSubmit}
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
