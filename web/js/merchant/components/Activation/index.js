/* eslint-disable */

import { Link, withRouter } from 'react-router-dom';
import Form from 'common/new-ui/Form';
import debounce from 'common/utils/debounce';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import Alert from 'common/new-ui/Alert';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import {
  autoPrefixUrls,
  isPresent,
  prevent,
  classList,
  checkIsObjectEmpty,
} from 'common/utils/rzp-utils';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { trackDiffInFormFields } from 'merchant/utils/track-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import LocalStorageService from 'common/utils/localStorage';
import { addDropShield, removeDropShield } from 'merchant/components/File/Upload';
import * as trackers from 'merchant/containers/Activation/ga_new';
import {
  fireFormStartEvents,
  fireKYCSubmitEvents,
  updateHubSpotContactsProperties,
} from 'merchant/containers/Activation/ActivationFormMarketingEvents';

import mainFormTabsContent, {
  mainFormTabs,
  mainFormFieldNamesMeta,
  getBusinessTypeOptions,
  tabToEventNames,
  bankAccountTabName,
  CIN_BusinessTypes,
  LLPIN_BusinessTypes,
} from './ActivationFormMap';
import accountFormTabsContent, {
  accountFormTabs,
  accountFormFieldNamesMeta,
} from './AccountActivationFormMap';
import RTracking from 'react-tracking';
import { updateSession } from 'merchant/reducers/session';
import {
  rxCaExp,
  rxKYCvisitedFlag,
  RX_HOTJAR_DATA,
} from 'merchant/containers/Home/OnboardingCard/data';
import {
  showInstantActivationSuccessModal,
  showKYCDetailsModal,
  showPANStatusModal,
  showKYCStatusModal,
  showFraudDetectionModal,
} from 'merchant/reducers/home';
import {
  submitL1Form,
  submitL1FormSuccess,
  setCurrentTab,
} from 'merchant/reducers/activationWizard';
import User from 'merchant/models/User';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  validatePersonalPAN,
  validateCompanyAB,
  validateCompanyPAN,
  validateCIN,
} from 'common/utils/validators';

import L1FormFieldNames from './L1FormFieldNames';
import {
  isL1Completed,
  hasSelectedBlacklistedCategory,
  doesHaveAdditionalDocs,
  getDefaultAdditionalDoc,
  getAdditionalDocOptions,
  hasAPIL1Error,
  displayCompanyPAN,
  doesHaveBusinessProofDocs,
  getDefaultBusinessProofDoc,
  isDedupe,
  isSourceRX,
  getBankTabHeader,
  isPanVerificationFailed,
} from './ActivationUtils';

import { fireL1FormSuccessEvents } from 'merchant/containers/Activation/ActivationFormMarketingEvents';
import QueryString from 'query-string';
import { getNeedsClarificationTabsData } from './NeedsClarificationFormMap';
import SubmitFormLayer from './components/SubmitFormLayer';
import Footer from './components/Footer';
import RxCaInterest from './components/RxCaInterest';
import { LOADING, FOOTER_BUTTONS } from './Constants';
import { TypeAhead } from 'react-power-select';
import EAadhard from './components/E-Aadhar';
import CustomEmail from './components/CustomEmail';
import SupportButton from 'merchant/components/Home/SupportButton';
import { GTAG_KEYS, invokeGtag } from 'merchant/components/OnBoarding/utils';
import { isValidGSTIN } from '../../../common/utils/rzp-utils';
import { trackEvents as trackEventsAction } from 'merchant/reducers/trackEvents';
import { capitalize } from 'common/utils/rzp-utils';

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

let NEEDS_CLARIFICATION_STEP, // To handle specific case for needs clarification screen
  DOCUMENT_UPLOAD_STEP, // To handle specific case for document step
  BANK_ACCOUNT_TAB; // To handle specific case for bank account step
let BUSINESS_TYPE_FORM_STEP = 1; // If NGO is selected, then Document Upload would have 2 more fields
const BUSINESS_DETAILS_STEP = 2;
let FORM_TABS, // Maintains naming of the tabs
  FORM_TABS_CONTENT, // Actual tab content corresponding to FORM_TABS
  FORM_TABS_NAMES; // All fields names in the FORM_TABS_CONTENT

const SAVE_BUTTON_DISABLED_STEPS = [BUSINESS_DETAILS_STEP];

@withRouter
@connect(
  (state) => ({
    session: state.session,
    user: state.session.user,
  }),
  {
    showNotification,
    updateSession,
    showInstantActivationSuccessModal,
    showKYCDetailsModal,
    showPANStatusModal,
    showFraudDetectionModal,
    submitL1Form,
    submitL1FormSuccess,
    showKYCStatusModal,
    setCurrentTab,
    trackEventsAction,
  },
)
@RTracking(() => window.rzpQ.component('ActivationWizard'))
export default class ActivationWizard extends React.Component {
  state = {
    isSaving: this.isLinkedAccountForm ? LOADING.DEFAULT : LOADING.INITIAL,
    dirty: {},
    tabs: [],
    same_address:
      this.props.data &&
      this.props.data.business_operation_pin == this.props.data.business_registered_pin
        ? '1'
        : '0', // '1' => checkbox ticked
    has_url:
      this.props.data &&
      this.props.data.business_website === '' &&
      this.props.data.playstore_url === ''
        ? '0'
        : '1', // '0' => 0th radio button, value exists
    app_website_url: this.props.data && this.props.data.business_website === '' ? '0' : '1',
    app_url: this.props.data && this.props.data.playstore_url === '' ? '0' : '1',
    has_gstin:
      this.props.data && this.props.data.gstin === '' && !this.props.gstinDetails?.defaultGstin
        ? '1'
        : '0', // '0' => 0th radio button, value exists
    gstin: this.props.data && this.props.data.gstin,
    showGstinDescription: true,
    account_no: this.props.data && this.props.data.bank_account_number,
    activeTab: 0, // Fallback for all cases.
    callingAPI: false,
    address_proof: 'aadhar',
    needsClarification: {},
    additional_doc: '',
    business_name_options: [],
    business_name_selected_option: {},
    business_proof_type: 'gst_certificate',
    commentlist: {},
    bank_proof: 'cancelled_cheque',
    selected_aov: '',
    isAadharDocVisible:
      !this.props.data.stakeholder ||
      (this.props.data.stakeholder && !!this.props.data.stakeholder.aadhaar_linked),
    ischeck: !this.props.user.isSyncExperimentEnabled,
    showInfoHeader: false,
    tempContactEmail: '',
    isEmailNonMandatory: true,
    isEmailOnL2: true,
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
        window.hj('tagRecording', ['activation_form_open', this.props.user.current]);
      }
    }

    this.formName = 'KYC Form';
    this.formDescription = 'Complete and submit the form to accept payments.';
    this.trackingType = 'act';
    this.showFullGstinList = true;
    if (props.user.showInstantActivation && props.user.instantActivation.isL1Submitted) {
      this.formName = 'KYC Form';
      this.formDescription = 'Complete and submit the form to enable settlements.';
      this.trackingType = 'kyc';
    }
  }
  isNeedsClarificationMode() {
    return this.props.data.activation_status === 'needs_clarification';
  }
  isOnKYCTab() {
    return this.state.activeTab === NEEDS_CLARIFICATION_STEP;
  }
  prepareTabs(props) {
    const { tracking } = props;
    const canEmailVerify =
      (props.user.isEmailMandatoryOnL1 || props.user.isEmailNonMandatoryOnL1) &&
      !props.user.user?.signup_via_email;

    if (props.data.merchant_avg_order_value) {
      const aovValue = props.data.merchant_avg_order_value;
      if (aovValue.max_aov === 0) {
        this.state.selected_aov = 'More than ₹ 1,00,000';
      } else {
        this.state.selected_aov = `${aovValue.min_aov}-${aovValue.max_aov}`;
      }
    }
    this.mainTabs = mainFormTabs;

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
      let mainTabsContent = mainFormTabsContent;
      let mainFieldNamesMeta = mainFormFieldNamesMeta;
      if (canEmailVerify) {
        // for email verfication activation tab order has been changed.
        // contact tab will be asked in the last step
        BUSINESS_TYPE_FORM_STEP = 0; // If NGO is selected, then Document Upload would have 2 more fields

        this.mainTabs = mainFormTabs.filter((_i, idx) => idx > 0);
        this.mainTabs.splice(2, 0, mainFormTabs[0]);

        mainTabsContent = mainFormTabsContent.filter((_i, idx) => idx > 0);
        mainTabsContent.splice(2, 0, mainFormTabsContent[0]);

        mainFieldNamesMeta = mainFormFieldNamesMeta.filter((_i, idx) => idx > 0);
        mainFieldNamesMeta.splice(2, 0, mainFormFieldNamesMeta[0]);
      }

      FORM_TABS = this.mainTabs;
      FORM_TABS_CONTENT = mainTabsContent;
      FORM_TABS_NAMES = mainFieldNamesMeta;

      BANK_ACCOUNT_TAB = 3;
      DOCUMENT_UPLOAD_STEP = 4;

      if (
        props.data.activation_status === 'needs_clarification' &&
        props.data.kyc_clarification_reasons
      ) {
        if (FORM_TABS.indexOf('Needs Clarification') === -1) {
          FORM_TABS.push('Needs Clarification');
        }
        const ndcFields =
          getNeedsClarificationTabsData(mainTabsContent, props.data, props.clarificationReasons) ||
          [];
        FORM_TABS_CONTENT.push(ndcFields);
        FORM_TABS_NAMES.push(ndcFields.map((f) => f?.name).filter((f) => Boolean(f)));
        NEEDS_CLARIFICATION_STEP = 5;
      }
      const userCanSubmitForm =
        this.props.user.isUnregisteredBusiness &&
        this.props.user.activation_form_milestone === 'L1' &&
        !this.props.user.isL2AllowedForPoiInitiated
          ? this.props.user.poi_verification_status !== 'initiated'
          : true;

      if (
        this.props.user.isInstantActivationEnabled &&
        (!userCanSubmitForm ||
          !isL1Completed(this) ||
          (isDedupe(this.props.user) === 'blocked' &&
            this.props.data.activation_form_milestone === 'L1'))
      ) {
        //Code needs some refactoring
        FORM_TABS = FORM_TABS.slice(0, BANK_ACCOUNT_TAB);
        FORM_TABS_CONTENT = FORM_TABS_CONTENT.slice(0, BANK_ACCOUNT_TAB);
        FORM_TABS_NAMES = FORM_TABS_NAMES.slice(0, BANK_ACCOUNT_TAB);
        DOCUMENT_UPLOAD_STEP = null;
      }

      // Business Category in "Business Model" exists in main activation form. Setting value dynamically from props.
      FORM_TABS_CONTENT[canEmailVerify ? 0 : 1][1][0].options = ['--Select--'].concat(
        Object.keys(props.categories).map((c) => ({
          name: c,
          label: props.categories[c].description,
        })),
      );

      // Set Biz type options dynamically based on current activation stage
      FORM_TABS_CONTENT[canEmailVerify ? 0 : 1][0].options = getBusinessTypeOptions(this);

      if (isPresent(props.data.business_name)) {
        this.state.business_name_selected_option = {
          company_name: props.data.business_name,
          identity_type: '',
          identity_number: '',
        };
      }
    }

    if (DOCUMENT_UPLOAD_STEP) {
      SAVE_BUTTON_DISABLED_STEPS.push(DOCUMENT_UPLOAD_STEP);

      if (doesHaveAdditionalDocs(this)) {
        // set default additional doc
        const defaultAdditionalDoc = getDefaultAdditionalDoc(this);
        this.state.additional_doc = defaultAdditionalDoc || '';
        const ADDITIONAL_DOC_SELECT_FIELD_INDEX = 15;
        FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP][
          ADDITIONAL_DOC_SELECT_FIELD_INDEX
        ].options = getAdditionalDocOptions(this);
      }

      if (doesHaveBusinessProofDocs(this)) {
        // Set default business proof doc if there can be multiple choices for business proof
        // Only applicable for Proprietorship businesses currently
        const defaultBusinessProofDoc = getDefaultBusinessProofDoc(this);
        this.state.business_proof_type = defaultBusinessProofDoc;
      }
    }

    defaultFieldProps.call(this, FORM_TABS_CONTENT); // Set the default props for all tab content views

    const prepareFileFields = (a) => {
      if (!a) {
        return;
      }
      if (a._cmp === Input.File) {
        a._cmp = Input.File;
        a._accept = ['pdf', 'image'];
        a._showAcceptInfo = false;
        a._showStagedFileStatus = false;

        if (!a.hasOwnProperty('required')) {
          a.required = true;
        }

        a.onChange = (file, progressTracker) => {
          const filename = a.getName ? a.getName(this) : a.name;

          return props.saveFile(filename, file, progressTracker, a.uploadAs || null).then(() => {
            updateHubSpotContactsProperties({
              [filename]: true,
            });

            tracking.trackEvent(
              window.rzpQ.onbr().initiated('kyc.upload_document', {
                name: filename,
              }),
            );
            this.props.trackEventsAction({
              objectName: 'kyc upload document',
              actionName: 'clicked',
              screen: 'KYC Document',
              properties: {
                location: 'Activation page',
              },
            });
            if (!this.isOnKYCTab()) {
              this.markTabIfActive(DOCUMENT_UPLOAD_STEP);
            }
            this.updateFileInDirty(filename);
          });
        };
      }
    };
    /*
     * All document fields in activation form to have same footprint.
     * Adding onChange listener to all document upload fields.
     * */
    DOCUMENT_UPLOAD_STEP && FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP].forEach(prepareFileFields);

    /* This calls for all fields instead of just file fields */
    NEEDS_CLARIFICATION_STEP &&
      FORM_TABS_CONTENT[NEEDS_CLARIFICATION_STEP] &&
      FORM_TABS_CONTENT[NEEDS_CLARIFICATION_STEP].forEach(prepareFileFields);
  }

  handleComment = (e, key, removecomment) => {
    prevent(e);

    const prevCommentFromState = this.state.commentlist;

    const removeCommentFromList = Object.assign({}, prevCommentFromState);
    delete removeCommentFromList[key];

    const addCommentToList = Object.assign({}, prevCommentFromState, {
      [key]: e.target.value || '',
    });

    const comments = removecomment ? removeCommentFromList : addCommentToList;

    this.setState({
      commentlist: comments,
    });
  };

  componentDidUpdate(prevProps, prevState) {
    if (this.state.activeTab !== prevState.activeTab) {
      this.props.trackEventsAction({
        objectName: 'Activation Tab',
        actionName: 'Loaded',
        screen: 'KYC Document',
        properties: {
          tab: this.mainTabs[this.state.activeTab],
        },
        toCleverTap: true,
      });
    }

    if (prevProps.showL2Form !== this.props.showL2Form && this.props.showL2Form === true) {
      this.prepareTabs(this.props);
      this.setState({
        showInfoHeader: true,
      });
    }
    return this.props.handleUIUpdate && this.props.handleUIUpdate();
  }

  updateFileInDirty = (filename) => {
    this.setState((prevState) => ({
      dirty: { ...prevState.dirty, [filename]: 'fakepath' },
    }));
  };

  componentDidMount() {
    addDropShield('.Activation--wizard');

    if (!this.props.user.isAccepted && isL1Completed(this)) {
      trackers.trackKYCFormOpen();
    }

    fireFormStartEvents(isL1Completed(this));

    const query = QueryString.parse(this.props.location.search);
    this.handleActionBasedOnQuery(query);
    this.addVisitedFlag();
    this.props.trackEventsAction({
      objectName: 'Activation Tab',
      actionName: 'Loaded',
      screen: 'KYC Document',
      properties: {
        tab: this.mainTabs[this.state.activeTab],
      },
      toCleverTap: true,
    });
  }

  addVisitedFlag = () => {
    let activeTab = this.state.activeTab;
    activeTab = activeTab < 0 || !activeTab ? 0 : activeTab;
    const { user } = this.props;
    const { settings } = user.user;
    if (FORM_TABS[activeTab] === bankAccountTabName && !this.isSourceRX && this.isRxCaExpEnabled) {
      const { trigger, tags } = RX_HOTJAR_DATA.CA_KYC;
      triggerHotjarRecording(trigger, tags);
      // condition to show RX-Ca interest card
      if (!settings[rxKYCvisitedFlag] || settings[rxKYCvisitedFlag] === '0') {
        const _settings = { ...settings };
        _settings[rxKYCvisitedFlag] = '1';
        merchantFetch({
          url: 'users',
          mode: 'live',
          method: 'patch',
          data: { settings: _settings },
        }).then(() => {
          this.props.updateUser({ settings: _settings });
        });

        this.props.tracking.trackEvent(window.rzpQ.onbr().initiated('rx_KYC_ca_visited'));
      }
    }
  };

  handleActionBasedOnQuery = (query) => {
    if (query['auto-submit'] == 'l1-form') {
      const submitButton = document.querySelector('footer button[name=submit-and-verify]');
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
    const isFormSubmitted = !!this.props.data.submitted; // Linked accounts form can still be seen after activation.

    for (let i = 0; i < FORM_TABS.length; i++) {
      const tabStatusValid = this.tabValidity(i);

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

      if (!isFormSubmitted && isL1Completed(this)) {
        this.state.showSubmitLayer = true;
      }
    }

    this.state.activeTab = firstInValid;
    this.props.setCurrentTab({
      tab_name: this.mainTabs[firstInValid],
    });
  }

  markTabIfActive(updatedTabId) {
    const isValid = this.tabValidity(updatedTabId);
    const tabs = this.state.tabs.slice();

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
        window.rzpQ.onbr().initiated(`${this.trackingType}.save_modifications`, {
          clickSource: 'save',
        }),
      );
    let callBack =
      onAction &&
      function (result, error) {
        tracker();
        onAction.trackSave({
          tabId: currenActiveTab,
          type: result, // result = true for Success, false for Error, null for no api call
          error,
        });
      };

    if (!isL1Completed(this)) {
      callBack = () => {};
    }

    this.goto(null, callBack);
  };

  sendErrorMessageToSegment = (e, error) => {
    if (error) {
      const fieldLabel = capitalize(e.target.name.split('_').join(' '));
      this.props.trackEventsAction({
        objectName: 'Form Field',
        actionName: 'Validation Failed',
        screen: 'home page',
        eventAction: 'failed',
        properties: {
          error: error,
          fieldLabel: fieldLabel,
          tab: this.mainTabs[this.state.activeTab],
        },
      });
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().failed('Form Field Validation', {
          error: error,
          fieldLabel: fieldLabel,
          tab: this.mainTabs[this.state.activeTab],
        }),
      );
    }
  };

  next = (e) => {
    const currenActiveTab = this.state.activeTab;
    this.props.trackEventsAction({
      objectName: 'Save and Next',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        tab: this.mainTabs[currenActiveTab],
      },
      toCleverTap: true,
    });

    const tracker = () =>
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().initiated(`${this.trackingType}.save_modifications`, {
          clickSource: 'save-next',
          currentTabName: this.mainTabs[currenActiveTab],
        }),
      );
    let callBack =
      onAction &&
      function (result, error) {
        tracker();
        onAction.trackSaveAndNext({
          tabId: currenActiveTab,
          type: result, // result = true for Success, false for Error, null for no api call
          error,
        });
      };

    if (!isL1Completed(this)) {
      callBack = () => {};
    }

    this.goto(this.state.activeTab + 1, callBack);
  };

  prev = (e) => {
    const currenActiveTab = this.state.activeTab;
    let callBack =
      onAction &&
      function (result, error) {
        onAction.trackBack({
          tabId: currenActiveTab,
          type: result, // result = true for Success, false for Error, null for no api call
          error,
        });
      };

    if (!isL1Completed(this)) {
      callBack = () => {};
    }

    this.goto(this.state.activeTab - 1, callBack);
  };

  changeTab = ({ target }) => {
    const tabId = parseInt(target.getAttribute('data-index'));
    const currentActiveTab = this.state.activeTab;
    this.setState({ tempContactEmail: '' }); // remove temp email on changing tab
    this.setEnableAndDisableCheckbox(true);
    this.setState({ isEmailOnL2: true });

    this.props.trackEventsAction({
      objectName: 'Activation Tab',
      actionName: 'Clicked',
      screen: 'KYC Document',
      properties: {
        tab: this.mainTabs[tabId],
      },
      toCleverTap: true,
    });

    const tracker = () =>
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().initiated(`${this.trackingType}.nav_action`, {
          clickSource: this.mainTabs[tabId],
        }),
      );
    let callBack =
      onAction &&
      function (result, error) {
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

    if (!isL1Completed(this)) {
      callBack = () => {};
    }

    this.goto(tabId, callBack);
  };

  //newActiveTab = null -> clicked on Save btn / 'Submit Form' tab
  trackFieldsChange = (currentActive) => {
    try {
      const { tracking } = this.props;
      let eventName = this.trackingType + '.' + tabToEventNames[currentActive];
      const fields = trackDiffInFormFields(this.props.data, this.state.dirty);
      return fields.forEach((field) =>
        tracking.trackEvent(
          window.rzpQ.onbr().initiated(eventName, {
            ...field,
          }),
        ),
      );
    } catch (err) {}
  };

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
    this.props.setCurrentTab({
      tab_name: this.mainTabs[newActiveTab],
    });

    const shouldSave = Object.keys(this.state.dirty).length ? true : null;
    if (!shouldSave) {
      this.updateFEOnlyValues();

      this.markTabIfActive(savingWhichTab); // To update changes happened due to FE-only fields change, like has_gstin and has_url.

      cb && cb(); // If clicked on Save/Save-Next btn without any change
      return;
    }

    const currentDirty = this.state.dirty;
    const reqData = {};

    /* Send only those fields which belongs to the TAB being saved */
    Object.keys(currentDirty).forEach((name) => {
      if (
        currentDirty.hasOwnProperty(name) &&
        FORM_TABS_NAMES[currentActive].indexOf(name) !== -1 &&
        currentDirty[name] !== 'fakepath'
      ) {
        // Saving only the fields corresponding to currentActive tab.
        const fieldVal = currentDirty[name];
        reqData[name] = fieldVal;

        // For business website empty string => user don't have website. null => user didn't attempt the field.
        const allowEmptyString = [
          'business_website',
          'playstore_url',
          'gstin',
          'shop_establishment_number',
        ];
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
      },
    );

    /* Check validity of 'Bank account no.' before saving */
    if (currentActive == BANK_ACCOUNT_TAB && !this.props.user?.isUpdatedLiteOnboarding) {
      if (
        reqData.hasOwnProperty('bank_account_number') &&
        (!reqData.bank_account_number || reqData.bank_account_number != this.state.account_no)
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

    this.trackFieldsChange(currentActive);

    this.props.save(reqData).then((data) => {
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
          Object.keys(savingDataOfWhichTab).forEach((key) => {
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

        const prefix = isL1Completed(this) ? 'l2_' : 'l1_';

        updateHubSpotContactsProperties(reqData, {}, prefix);

        const latestDirty = { ...this.state.dirty };
        Object.keys(savingDataOfWhichTab).forEach((key) => {
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
        this.props.data.business_operation_pin == this.props.data.business_registered_pin
          ? '1'
          : '0', // '1' => checkbox ticked
    });
  }

  get isLinkedAccountForm() {
    return !!this.props.accountId;
  }

  get isUnregBiz() {
    const businessType = this.state.dirty.business_type || this.props.data.business_type;

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
        bank_proof_doc: () => {
          return state.bank_proof;
        },
        business_proof_type_doc: () => {
          return state.business_proof_type;
        },
      };
      const hasFilledEverything = FORM_TABS_NAMES[NEEDS_CLARIFICATION_STEP].every((field) => {
        if (field === 'bank_account_name' && this.props.user?.isUpdatedLiteOnboarding) {
          return true;
        }
        if (dynamicFieldName[field]) {
          field = dynamicFieldName[field]();
        }

        return Boolean(
          state.dirty[field] ||
            (this.state.commentlist.hasOwnProperty(field) && this.state.commentlist[field] !== ''),
        );
      });

      return hasFilledEverything;
    }
  }

  get canSubmitL1Form() {
    const promoterPan = this.state.dirty.promoter_pan || this.props.data.promoter_pan;
    const showCompanyName = this.props.user.isCompanyNameHiddenRazorX;
    const businessName = this.state.dirty.business_name || this.props.data.business_name;
    const contactName = this.state.dirty.contact_name || this.props.data.contact_name;
    const companyPAN = this.state.dirty.company_pan || this.props.data.company_pan;
    const promoterPANName = this.state.dirty.promoter_pan_name || this.props.data.promoter_pan_name;
    const currentBusinessType = this.state.dirty.business_type || this.props.data.business_type;
    const companyCin = this.state.dirty.company_cin || this.props.data.company_cin;
    const billingLabel = this.state.dirty.business_dba || this.props.data.business_dba;
    const hasSameAddress = this.state.dirty.same_address === '1';
    const registeredAddress =
      this.state.dirty.business_registered_address || this.props.data.business_registered_address;
    const registeredPin =
      this.state.dirty.business_registered_pin || this.props.data.business_registered_pin;
    const registeredCity =
      this.state.dirty.business_registered_city || this.props.data.business_registered_city;
    const registeredState =
      this.state.dirty.business_registered_state || this.props.data.business_registered_state;
    const operationAddress = hasSameAddress
      ? true
      : this.state.dirty.business_operation_address || this.props.data.business_operation_address;
    const operationCity = hasSameAddress
      ? true
      : this.state.dirty.business_operation_city || this.props.data.business_operation_city;
    const operationPin = hasSameAddress
      ? true
      : this.state.dirty.business_operation_pin || this.props.data.business_operation_pin;
    const operationState = hasSameAddress
      ? true
      : this.state.dirty.business_operation_state || this.props.data.business_operation_state;

    const isCompanyPANValid = displayCompanyPAN(this)
      ? companyPAN && !validateCompanyPAN(companyPAN)
      : true;
    const isPromoterPANValid = promoterPan && !validatePersonalPAN(promoterPan);
    const isCompanyNameValid = this.isUnregBiz
      ? true
      : !validateCompanyAB(businessName, contactName, showCompanyName);
    const businessCategory =
      this.state.dirty.business_category || this.props.data.business_category;
    const isCINValid =
      currentBusinessType && CIN_BusinessTypes.indexOf(Number(currentBusinessType)) !== -1
        ? companyCin && !validateCIN(companyCin)
        : true;
    const isLPINValid =
      currentBusinessType && LLPIN_BusinessTypes.indexOf(Number(currentBusinessType)) !== -1
        ? companyCin && !validateCIN(companyCin, 'LLPIN')
        : true;

    const isSyncExpEnable =
      !this.props.user.isSyncExperimentEnabled ||
      (billingLabel &&
        isCINValid &&
        isLPINValid &&
        registeredAddress &&
        registeredPin &&
        registeredCity &&
        registeredState &&
        registeredState &&
        operationAddress &&
        operationAddress &&
        operationCity &&
        operationPin &&
        operationState);

    const isEmailVerified =
      this.props.user?.user?.confirmed || !this.props.user.isEmailMandatoryOnL1;

    return (
      !hasSelectedBlacklistedCategory(this) &&
      isPromoterPANValid &&
      isCompanyPANValid &&
      isCompanyNameValid &&
      promoterPANName &&
      businessCategory &&
      isSyncExpEnable &&
      isEmailVerified
    );
  }

  /*
   * Handle Account No. re-enter match before saving.
   * It mimicks loader used for API to handle cases if tab is changed.
   * */
  handleBankAccountMismatch(isTabSwitched) {
    if (isTabSwitched) {
      const latestDirty = { ...this.state.dirty };

      delete latestDirty.bank_account_number; // Remove 'bank_account_number', so there is no attempt to save repeatedly

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
      company_pan_verification_status,
      promoter_pan_name,
      promoter_pan,
      business_type,
      locked,
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
      company_pan_verification_status,
      promoter_pan,
      promoter_pan_name,
      business_type,
      submitted: +submitted,
      locked,
    }));

    this.props.updateSession({
      user,
      mode: session.mode,
    });
  }

  trackSubmitL1 = (data) => {
    const { tracking } = this.props;
    try {
      const fields = trackDiffInFormFields(this.props.data, this.state.dirty);
      fields &&
        fields.forEach((field) =>
          tracking.trackEvent(
            window.rzpQ.onbr().initiated('act.business_details', {
              ...field,
            }),
          ),
        );
      tracking.trackEvent(window.rzpQ.onbr().initiated('act.submit_form'));
    } catch (e) {}
  };

  isValidL1Field = (field_name) => {
    let field;
    for (let i = 1; i < FORM_TABS_CONTENT.length; i++) {
      FORM_TABS_CONTENT[i].every((f) => {
        if (Array.isArray(f)) {
          f.every((_f) => {
            if (_f.name === field_name) {
              field = _f;
            }
            if (field) return false;
            return true;
          });
        } else if (f.name === field_name) {
          field = f;
          if (field) return false;
          return true;
        }
      });
      if (field) {
        break;
      }
    }

    if (field && field._when && !field._when(this)) {
      return false;
    }
    return true;
  };

  get formData() {
    const currentDirty = this.state.dirty;
    const reqData = {};

    L1FormFieldNames.forEach((field) => {
      if (this.isValidL1Field(field)) {
        this.populateReqData(field, reqData, currentDirty);
      }
    });

    if (!Object.keys(reqData).length) {
      return; // Nothing changed on the currentActive Tab, although the data do exist in dirty
    }

    return reqData;
  }

  populateReqData(field, reqData, currentDirty) {
    const name = field;

    if (!name) {
      return;
    }

    const fieldVal = name in currentDirty ? currentDirty[name] : this.props.data[name];

    reqData[name] = fieldVal;

    // For business website empty string => user don't have website. null => user didn't attempt the field.
    const allowEmptyString = ['business_website', 'gstin', 'playstore_url'];
    if (allowEmptyString.indexOf(name) === -1) {
      reqData[name] = reqData[name] === '' ? null : fieldVal; // '' -> null. DB has default values as NULL.
    }
  }

  submitForm = async () => {
    const businessCategories = await this.props.fetchBusinessCategory();

    const data =
      !this.props.data.activation_form_milestone && !this.isLinkedAccountForm
        ? (this.props.user.isEmailMandatoryOnL1 || this.props.user.isEmailNonMandatoryOnL1) &&
          !this.props.user.user?.signup_via_email
          ? Object.entries(this.state.dirty).reduce((a, c) => {
              if (c[0] !== '') {
                a[c[0]] = c[1];
              }
              return a;
            }, {})
          : this.formData
        : {};

    return this.props.submitForm({ data }).then((data) => {
      if (data?.errors) {
        // Track session for any error on submission (non-LA account)
        if (!this.isLinkedAccountForm && typeof window.hj === 'function') {
          window.hj('tagRecording', ['activation_form_save_error']);
        }

        const _data = {
          error: data.errors,
          type: false,
        };
        this.props.tracking.trackEvent(
          window.rzpQ
            .onbr()
            .failed(`${!isL1Completed(this) ? 'act.submit_form' : 'kyc.submit_form'}`, {
              error: data.errors[0] ? data.errors[0] : 'Failed',
            }),
        );
        fireKYCSubmitEvents(_data);
      } else {
        const isUnregisteredBusiness = this.isUnregBiz;
        let _data = { isUnregisteredBusiness };
        if (data?.data) {
          _data = {
            ...data.data,
            isUnregisteredBusiness,
          };
        }

        let activationFlow = '';
        if (businessCategories && businessCategories.data && !businessCategories.data.errors) {
          activationFlow =
            businessCategories.data[this.props.data.business_category]?.subcategories[
              this.props.data.business_subcategory
            ]?.activation_flow;
        }

        if (data?.data?.sumitted) {
          window.criteo_q = window.criteo_q || [];
          var deviceType = /iPad/.test(navigator.userAgent)
            ? 't'
            : /Mobile|iP(hone|od)| Android|BlackBerry|IEMobile|Silk/.test(navigator.userAgent)
            ? 'm'
            : 'd';
          window.criteo_q.push(
            { event: 'setAccount', account: 85314 },
            { event: 'setEmail', email: this.props.data.contact_email },
            { event: 'setZipcode', zipcode: '' },
            { event: 'setSiteType', type: deviceType },
            {
              event: 'trackTransaction',
              id: '',
              extra_data: 'KYC',
              item: [
                'www.criteo.com',
                { id: '456', price: 1, quantity: 1 },
                //add a line for each additional line in the basket
              ],
            },
          );
        }

        if (isUnregisteredBusiness) {
          invokeGtag(GTAG_KEYS.kycSubmitSuccessUnReg);
        } else if (activationFlow === 'whitelist') {
          invokeGtag(GTAG_KEYS.kycSubmitSuccessRegWhitelist);
        } else if (activationFlow === 'greylist') {
          invokeGtag(GTAG_KEYS.kycSubmitSuccessRegGreylist);
        }

        fireKYCSubmitEvents(_data);
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
      const fieldNames = needsClarificationFields.map((field) => field.name);
      fieldNames.forEach((fieldName) => {
        if (this.state.dirty[fieldName]) {
          reqData[fieldName] = this.state.dirty[fieldName];
        }
      });
    }

    if (!checkIsObjectEmpty(this.state.commentlist)) {
      reqData.kyc_clarification_reasons = {
        clarification_reasons: {},
      };
    }

    for (var prop in this.state.commentlist) {
      if (this.state.commentlist.hasOwnProperty(prop) && this.state.commentlist[prop] !== '') {
        {
          reqData.kyc_clarification_reasons.clarification_reasons[prop] = [
            {
              reason_type: 'custom',
              reason_code: this.state.commentlist[prop],
            },
          ];
        }
      }
    }

    // State will contain file fields which have already been uploaded
    // Delete file field from request data
    Object.keys(reqData).forEach((key) => {
      if (reqData[key] === 'fakepath') {
        delete reqData[key];
      }
    });

    try {
      this.setState({ callingAPI: true });
      const response = await this.props.save(reqData);

      if (response.data.activation_status === 'needs_clarification') {
        const poi_verification_status = response.data.poi_verification_status;
        const company_pan_verification_status = response.data.company_pan_verification_status;
        // remove errored field from state dirty to show API error
        const newStateDirty = Object.assign({}, this.state.dirty);
        if (
          (poi_verification_status === 'incorrect_details' ||
            poi_verification_status === 'not_matched') &&
          newStateDirty.hasOwnProperty('promoter_pan')
        ) {
          delete newStateDirty.promoter_pan;
        }

        if (
          (company_pan_verification_status === 'incorrect_details' ||
            company_pan_verification_status === 'not_matched') &&
          newStateDirty.hasOwnProperty('company_pan')
        ) {
          delete newStateDirty.company_pan;
        }

        this.setState({
          dirty: newStateDirty,
        });
      } else if (response.success) {
        if (this.props.user.isInstantActivationEnabled) {
          this.props.showKYCStatusModal({
            modalType: 'KYC_ACTIVATION_SUBMIT_MODAL',
          });
        } else {
          this.props.showKYCStatusModal({
            modalType: 'KYC_CLARIFICATION_SUBMIT_MODAL',
            activationDuration: '3 days',
          });
        }

        LocalStorageService.setItem(
          `rzp_onboarding--${this.props.user.current}--clarification_submitted`,
          true,
        );
        this.props.history.replace('/');
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
      this.setState({ callingAPI: false });
    }
  };
  //  in sync pan verification footer checkbox
  onCheckboxChange = (checked) => {
    this.setState({ ischeck: checked });
  };

  /*
   * Fadeout based loader text.
   * Default delay = 7 sec
   * */
  removeLoader = (delay) => {
    this.loaderTimeout = setTimeout(() => {
      this.setState({ isSaving: LOADING.INITIAL });
    }, delay || 7000); // Success states can be removed in 3sec.
  };

  getSingleSubcategory = (fieldValue) => {
    const subcategories = this.props.categories[fieldValue]?.subcategories;
    if (!subcategories) {
      return null;
    }
    let keys = Object.keys(subcategories);
    if (subcategories && keys.length !== 1) {
      return null;
    }
    return {
      value: keys[0],
      label: subcategories[keys[0]].description,
    };
  };

  fetchBusinessNames = async (searchString) => {
    try {
      const res = await merchantFetch(
        `merchant/activation/company_search?search_string=${searchString}`,
      );
      if (res.data && res.data.results) {
        if (res.data.results.length) {
          this.setState({ business_name_options: res.data.results });
        } else {
          this.props.tracking.trackEvent(
            window.rzpQ.onbr().failed(`act.company_search_no_results`, {
              search_string: searchString,
            }),
          );
        }
      }
    } catch (err) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().failed(`act.company_search_api`, {
          search_string: searchString,
          status_code: err.status_code,
          error_code: err.code,
          errors: err.errors,
        }),
      );
    }
  };

  debouncedFetchBusinessName = debounce(this.fetchBusinessNames.bind(this), 200);

  onChange = ({ target }) => {
    const placeholder = target.getAttribute('placeholder');
    const stateName = target.getAttribute('data-name');
    let fieldValue = target.value;
    const fieldName = target.name;
    const sideEffectFieldsToUpdate = {}; // Some fields might lead to other fields get dirty. So, they also needs to be updated alongside
    const { dirty, has_gstin } = this.state;
    const { data } = this.props;
    const {
      isEmailMandatoryOnL1,
      isEmailNonMandatoryOnL1,
      isEmailNonMandatoryOnL2Form,
    } = this.props.user;
    const currentBusinessType = dirty.business_type || data.business_type;
    if (this.props.user.isSyncExperimentEnabled) {
      this.setState({ ischeck: false });
    }

    if (
      fieldName === 'contact_email' &&
      !this.props.user.user?.signup_via_email &&
      !this.isOnKYCTab() &&
      (isEmailMandatoryOnL1 || isEmailNonMandatoryOnL1 || isEmailNonMandatoryOnL2Form)
    ) {
      //don't allow to save value in BE.
      this.setState({ tempContactEmail: fieldValue });
      return;
    }
    if (placeholder === 'Enter GSTIN') {
      this.showFullGstinList = false;
      this.setState((prevState) => ({
        ...prevState,
        gstin: fieldValue,
        showGstinDescription: false,
        dirty: {
          ...prevState.dirty,
          gstin: fieldValue,
        },
      }));
      return;
    }
    // Company Search only available for PG Activation
    if (
      placeholder === 'Business name as per PAN' &&
      !isSourceRX() &&
      (CIN_BusinessTypes.includes(Number(currentBusinessType)) ||
        LLPIN_BusinessTypes.includes(Number(currentBusinessType)))
    ) {
      const args = {
        option: { company_name: fieldValue, identity_number: '', identity_type: '' },
      };
      this.onOptionChange(args);
      if (fieldValue.length < 3) {
        this.setState({ business_name_options: [] });
      } else if (fieldValue.length < 20) {
        this.debouncedFetchBusinessName(fieldValue);
      }
      return;
    }

    /*
     * Step 1: These 4 fields are directly filled on user's behalf,
     * And marked dirty to be sent on click of Save
     * */
    if (stateName === 'same_address' && target.checked) {
      // Checking the box, sets the ALL operation fields also dirty.
      sideEffectFieldsToUpdate.business_operation_address =
        dirty.business_registered_address || data.business_registered_address;
      sideEffectFieldsToUpdate.business_operation_pin =
        dirty.business_registered_pin || data.business_registered_pin;
      sideEffectFieldsToUpdate.business_operation_city =
        dirty.business_registered_city || data.business_registered_city;
      sideEffectFieldsToUpdate.business_operation_state =
        dirty.business_registered_state || data.business_registered_state;
    }

    /*
     * Step 2: If user marks no GSTIN from radio box
     * */
    if (stateName === 'has_gstin' && fieldValue === '1') {
      sideEffectFieldsToUpdate.gstin = '';
    } else if (stateName === 'has_url' && fieldValue === '0') {
      sideEffectFieldsToUpdate.business_website = '';
      sideEffectFieldsToUpdate.playstore_url = '';
    } else if (stateName === 'app_website_url' && fieldValue === '0') {
      sideEffectFieldsToUpdate.business_website = '';
    } else if (stateName === 'app_url' && fieldValue === '0') {
      sideEffectFieldsToUpdate.playstore_url = '';
    }

    /* Step 3: If same_address is already ticked and any of business_registered fields are changed, then mark operational fields dirty;'.*/
    if (this.state.same_address == '1') {
      if (fieldName === 'business_registered_pin') {
        sideEffectFieldsToUpdate.business_operation_pin = fieldValue;
      } else if (fieldName === 'business_registered_city') {
        sideEffectFieldsToUpdate.business_operation_city = fieldValue;
      } else if (fieldName === 'business_registered_state') {
        sideEffectFieldsToUpdate.business_operation_state = fieldValue;
      } else if (fieldName === 'business_registered_address') {
        sideEffectFieldsToUpdate.business_operation_address = fieldValue;
      }
    }

    /* Step 4: Auto fill city and state based on pin */
    if (fieldName === 'business_operation_pin' || fieldName === 'business_registered_pin') {
      if (fieldValue.length === 6) {
        this.props.getPincodeDetails(fieldValue).then((data) => {
          if (data) {
            const cityField = `${fieldName.slice(0, -3)}city`; // It can be operation_ / registered_
            const stateField = `${fieldName.slice(0, -3)}state`;

            // Update dependent values
            sideEffectFieldsToUpdate[cityField] = data.city;
            sideEffectFieldsToUpdate[stateField] = data.state_code;
            if (this.state.same_address == '1') {
              sideEffectFieldsToUpdate.business_operation_city = data.city;
              sideEffectFieldsToUpdate.business_operation_state = data.state_code;
            }

            // Input fields are uncontrolled, so needs to be updated directly. Updating dependent field visible in view.
            document.querySelector(`.form-container [name=${cityField}]`).value = data.city;
            document.querySelector(`.form-container [name=${stateField}]`).value = data.state_code;

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

    if (fieldName === 'merchant_avg_order_value') {
      if (fieldValue === 'More than ₹ 1,00,000') {
        sideEffectFieldsToUpdate.merchant_avg_order_value = {
          min_aov: 100000,
          max_aov: 0,
        };
        this.setState({ selected_aov: 'More than ₹ 1,00,000' });
      } else {
        const value = fieldValue.split('-');
        const min = value[0];
        const max = value[1];
        sideEffectFieldsToUpdate.merchant_avg_order_value = {
          min_aov: min,
          max_aov: max,
        };
        this.setState({ selected_aov: `${min}-${max}` });
      }
    }

    /* Step 5: Business category and sub category are always marked dirty in pairs. BE validates them in pair. */
    if (fieldName === 'business_category') {
      // Set first option in new set of subcategory. It remains '', it would convert to null before making api call.

      const singleSubcategory = this.getSingleSubcategory(fieldValue);
      if (singleSubcategory) {
        sideEffectFieldsToUpdate.business_subcategory = singleSubcategory.value;
      } else {
        sideEffectFieldsToUpdate.business_subcategory = '';
        // Update Business Subcategory in view
      }
      let el = document.querySelector('.form-container [name=business_subcategory]');
      el && (el.value = '');
      // Reset Business Model as well.
      if (isSourceRX() || !this.props.user.isBDAndAovEnabled || !this.props.user.isOrgRZP) {
        sideEffectFieldsToUpdate.business_model = '';
        // Update Business Model in view
        el = document.querySelector('.form-container [name=business_model]');
        el && (el.value = '');
      }
    }

    if (fieldName === 'business_subcategory') {
      sideEffectFieldsToUpdate.business_category =
        dirty.business_category || data.business_category;

      const bizCatSubCatPair = [sideEffectFieldsToUpdate.business_category, fieldValue];

      if (doesHaveAdditionalDocs(this, bizCatSubCatPair)) {
        const additionalDoc = getDefaultAdditionalDoc(this, bizCatSubCatPair);
        const additionalDocOptions = getAdditionalDocOptions(this, bizCatSubCatPair);
        const ADDITIONAL_DOC_SELECT_FIELD_INDEX = 15;

        if (
          FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP] &&
          FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP][ADDITIONAL_DOC_SELECT_FIELD_INDEX]
        ) {
          FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP][
            ADDITIONAL_DOC_SELECT_FIELD_INDEX
          ].options = additionalDocOptions;
        }

        sideEffectFieldsToUpdate.additional_doc = additionalDoc;
      }
    }

    /* Step 6: Business website must have http/https prepended */
    if (fieldName === 'business_website') {
      fieldValue = autoPrefixUrls(fieldValue); // Updating in view will happen if he comes to this tab again. Otherwise single backspace on 'http' must be handled as full word not single character.
    }

    // auto-populate billing label
    if (
      fieldName === 'business_name' &&
      dirty.business_name !== data.business_name &&
      !isPresent(this.props.data.business_dba)
    ) {
      if (document.querySelector(`.form-container [name=business_dba]`)) {
        document.querySelector(`.form-container [name=business_dba]`).value = fieldValue;
      }
      sideEffectFieldsToUpdate.business_dba = fieldValue;
    }

    // update gstin radio button to show gstin input
    if (fieldName === 'gstin' && fieldValue && has_gstin === '1') {
      this.setState({ has_gstin: '0' });
    }

    /* Step Last: */
    if (stateName) {
      this.setState(
        {
          [stateName]: fieldValue,
        },
        () => {
          // re-validate the tab once state changes
          if (stateName === 'additional_doc') {
            this.markTabIfActive(DOCUMENT_UPLOAD_STEP);
          } else if (stateName === 'business_proof_type') {
            this.markTabIfActive(this.state.activeTab);
          }
        },
      );

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

  onOptionChange = (args) => {
    const currentBusinessType = this.state.dirty.business_type || this.props.data.business_type;
    let businessIdentityType = null;

    if (CIN_BusinessTypes.indexOf(Number(currentBusinessType)) !== -1) {
      businessIdentityType = 'cin';
    } else if (LLPIN_BusinessTypes.indexOf(Number(currentBusinessType)) !== -1) {
      businessIdentityType = 'llpin';
    } else businessIdentityType = null;
    if (this.props.user.isSyncExperimentEnabled) {
      this.setState({ ischeck: false });
    }

    const shouldAutoPopulateCompanyCin = businessIdentityType === args.option.identity_type;
    const shouldAutoPopulateBillingLabel =
      !isPresent(this.props.data.business_dba) &&
      args.option.company_name !== this.props.data.business_name;

    this.setState(
      (prevState) => ({
        ...prevState,
        business_name_selected_option: args.option,
        dirty: {
          ...prevState.dirty,
          business_name: args.option.company_name,
          ...(shouldAutoPopulateCompanyCin && { company_cin: args.option.identity_number }),
          ...(shouldAutoPopulateBillingLabel && { business_dba: args.option.company_name }),
        },
      }),
      () => {
        // dependent field CIN does not update automatically on updating state since its uncontrolled component
        // update field manually
        const cinField = document.querySelector('.form-container [name="company_cin"]');
        const billingLabelField = document.querySelector('.form-container [name="business_dba"]');

        if (cinField && shouldAutoPopulateCompanyCin) {
          cinField.value = args.option.identity_number;
          this.props.tracking.trackEvent(
            window.rzpQ.onbr().success(`act.company_search_CIN/LLPIN_Autopopulated`, {
              company_name: args.option.company_name,
              identity_number: args.option.identity_number,
              identity_type: args.option.identity_type,
            }),
          );
        }

        if (billingLabelField && shouldAutoPopulateBillingLabel) {
          billingLabelField.value = args.option.company_name;
        }
      },
    );
  };

  onEAadharCheckboxChange = (isChecked) => {
    this.props.save({ stakeholder: { aadhaar_linked: isChecked } }).then(() => {
      this.markTabIfActive(DOCUMENT_UPLOAD_STEP);
    });
    this.setState({ isAadharDocVisible: isChecked });
  };

  postSuccessfulEmailVerify = (payload) => {
    try {
      this.props.save(payload).then(() => {
        this.markTabIfActive(this.state.activeTab);
        //enable the checkbox post success verification
        this.setState({ isEmailNonMandatory: true });
        if (this.props.user.isEmailNonMandatoryOnL2) {
          this.setState({ isEmailOnL2: true });
        }
      });
    } catch (err) {}
  };

  setEnableAndDisableCheckbox = (isEmailNonMandatory) => {
    this.setState({ isEmailNonMandatory });
    this.markTabIfActive(this.state.activeTab);
  };

  setEnableAndDisableCheckboxOnL2 = (isEmailOnL2) => {
    this.setState({ isEmailOnL2 });
  };

  /* Find if all tabs are valid */
  isAllTabsValid = () => {
    if (!this.props.data.can_submit) {
      return false;
    }

    let isValid = true;

    for (let i = 0; i < this.state.tabs.length; i++) {
      if (!this.state.tabs[i]) {
        isValid = false;
        break;
      }
    }

    return isValid && (this.state.isEmailOnL2 || this.props.user.user?.confirmed);
  };

  /*
   * Toggles backdrop submit layer
   * - By default is opens the submit layer.
   * - Closes the layer if false passed explicitly
   * */
  toggleSubmitLayer = (e) => {
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

      const callBack =
        onAction &&
        function (result, error) {
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

  get isRxCaExpEnabled() {
    const { experiments } = this.props.user;
    return ((experiments || {})[rxCaExp] || {}).result === 'cohort-2';
  }

  get isSourceRX() {
    const query = QueryString.parse(window.location.search);
    const is_source_RX = !!(query && query.merchant && query.merchant === 'x');
    return is_source_RX;
  }

  get FooterButtons() {
    const footerButtons = [];
    const activeTab = this.state.activeTab;
    const isLastTab = activeTab === FORM_TABS.length - 1;
    const isFormLocked = this.isFormLocked;
    const isLinkedAccountForm = this.isLinkedAccountForm;
    const isFormSubmitted = !!this.props.data.submitted;
    const user = this.props.user;
    const isBusinessDetailsStep = activeTab === BUSINESS_DETAILS_STEP;
    const showSubmitLayer = this.state.showSubmitLayer;

    if (this.isOnKYCTab()) {
      footerButtons.push(FOOTER_BUTTONS.SUBMIT_CLARIFICATIONS);
      return footerButtons;
    }

    if (isFormLocked || showSubmitLayer) {
      return [];
    }

    if (!SAVE_BUTTON_DISABLED_STEPS.includes(activeTab)) {
      footerButtons.push(FOOTER_BUTTONS.SAVE);
    }

    if (!isLastTab) {
      footerButtons.push(FOOTER_BUTTONS.SAVE_AND_NEXT);
    }

    if (
      isLastTab &&
      isBusinessDetailsStep &&
      !isLinkedAccountForm &&
      !user.instantActivation.isL1Submitted
    ) {
      footerButtons.push(FOOTER_BUTTONS.SUBMIT_L1_FORM);
    }

    if (isLinkedAccountForm && isLastTab) {
      footerButtons.push(FOOTER_BUTTONS.SUBMIT_KYC_FORM);
    }

    if (
      !isLinkedAccountForm &&
      isLastTab &&
      !isFormSubmitted &&
      user.instantActivation.isL1Submitted &&
      !user.instantActivation.isBlacklistFlow
    ) {
      footerButtons.push(FOOTER_BUTTONS.SUBMIT_KYC_FORM);
    }

    return footerButtons;
  }

  get bankTabHeader() {
    const { title, subtitle } = getBankTabHeader(
      Number(this.state.dirty.business_type || this.props.data.business_type),
    );
    const isFieldDisabled =
      this.props.user.isSyncBankVerificationEnabled && this.props.bvsApiCount == 10;

    let error = '';
    if (
      this.props.data.bank_details_verification_status &&
      !['initiated', 'verified'].includes(this.props.data.bank_details_verification_status) &&
      this.props.user.isSyncBankVerificationEnabled
    ) {
      if (isFieldDisabled) {
        error = 'You have reached maximum limit to changed the bank account details';
      } else if (this.isUnregBiz) {
        error =
          'Kindly make sure you enter your Personal Bank Account details. Beneficiary Name of this bank account should match your Personal Pan Name';
      } else {
        error =
          'Make sure you enter your Company Bank Account details. Beneficiary Name of this bank account should match your Company Pan Name';
      }
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().failed('kyc.karza_bank_verification', {
          bvs_attempt_count: this.props.bvsApiCount,
        }),
      );
      this.props.trackEventsAction({
        objectName: 'Insync Karza Bank Verification',
        actionName: 'Failed',
        screen: 'KYC Bank screen',
        properties: {
          bvs_attempt_count: this.props.bvsApiCount,
        },
        toLumberjack: false,
      });
    } else if (
      this.props.data?.bank_details_verification_status === 'verified' &&
      this.props.user.isSyncBankVerificationEnabled
    ) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().success('kyc.karza_bank_verification', {
          bvs_attempt_count: this.props.bvsApiCount,
        }),
      );
      this.props.trackEventsAction({
        objectName: 'Insync Karza Bank Verification',
        actionName: 'success',
        screen: 'submit screen',
        properties: {
          bvs_attempt_count: this.props.bvsApiCount,
        },
        toLumberjack: false,
      });

      if (error) {
        sendErrorMessageToSegment(error);
      }
    }
    return (
      <>
        {title}
        {error ? (
          <div className="onboarding-tab-subtitle-error">{error}</div>
        ) : (
          <div className="onboarding-tab-subtitle">{subtitle}</div>
        )}
      </>
    );
  }

  render() {
    const isFormLocked = this.isFormLocked;
    const isFormActivated = !!this.props.data.activated;
    const isFormSubmitted = !!this.props.data.submitted;

    const userCanSubmitForm =
      this.props.user.isUnregisteredBusiness &&
      this.props.user.activation_form_milestone === 'L1' &&
      !this.props.user.isL2AllowedForPoiInitiated
        ? this.props.user.poi_verification_status !== 'initiated'
        : true;

    let activeTab = this.state.activeTab;
    activeTab = activeTab < 0 || !activeTab ? 0 : activeTab; // Graceful failure in case activeTab becomes negative. To handle non-reproducible weird error.

    const isCurrentTabValid = this.state.tabs[activeTab];

    const footerButtons = this.FooterButtons;

    let content, documentContent; // Document content will always be shown so that upload progress is maintained in DOM

    if (activeTab !== DOCUMENT_UPLOAD_STEP) {
      content =
        FORM_TABS_CONTENT[activeTab] &&
        FORM_TABS_CONTENT[activeTab].map((field, i) => {
          if (Array.isArray(field)) {
            return <Input.Group key={i}>{field.map(ActivationField, this)}</Input.Group>;
          }

          return ActivationField.call(this, field);
        });
    }

    documentContent =
      DOCUMENT_UPLOAD_STEP &&
      FORM_TABS_CONTENT[DOCUMENT_UPLOAD_STEP].map((field, i) => {
        if (Array.isArray(field)) {
          return <Input.Group key={i}>{field.map(ActivationField, this)}</Input.Group>;
        }

        return ActivationField.call(this, field);
      });
    const moreTabs = [];

    if (
      (this.isLinkedAccountForm && !isFormSubmitted) ||
      (isL1Completed(this) &&
        !isFormSubmitted &&
        isDedupe(this.props.user) !== 'blocked' &&
        userCanSubmitForm &&
        this.isAllTabsValid())
    ) {
      moreTabs.push(
        <li
          key="submit-tab"
          onClick={this.toggleSubmitLayer}
          className={classList(
            !this.isAllTabsValid() && 'disabled',
            this.state.showSubmitLayer && 'active',
            'li--submit',
          )}
        >
          Submit Form
          {!this.isAllTabsValid() && (
            <div className="description small">Complete the form to submit</div>
          )}
        </li>,
      );
    }

    let showRxCA = false;
    // show the option on KYC form for PG users who've experiment enabled
    if (FORM_TABS[activeTab] === bankAccountTabName && !this.isSourceRX && this.isRxCaExpEnabled) {
      showRxCA = true;
    }
    // track hubspot event
    if (this.state.tabs[1] && window.trackHubs) {
      window.trackHubs({
        name: 'update_property',
        data: {
          business_overview_submitted: true,
        },
      });
    }
    if (this.state.tabs[2] && window.trackHubs) {
      window.trackHubs({
        name: 'update_property',
        data: {
          business_verification_submitted: true,
        },
      });
    }

    // penny testing changes for Linked Accounts
    const isFormVerificationPending = this.props.data?.activation_status === 'verification_pending';
    const isFormVerificationFailed = this.props.data?.activation_status === 'verification_failed';
    const verificationFailureError = this.props.data?.bank_details_verification_error;

    return (
      <div className="Activation--wizard Wizard">
        {/* Activation form tabs */}
        {/* show spinner loader only when poi status is initiated on submit of L1 and activation form in modal form */}
        {this.props.isModalView && (
          <div className="poi-initiated-loader">
            <div className="spin-btn extra-large extra-width visible activation-spinner"></div>
            <div className="text">
              Hey {this.props.data.contact_name}, we are verifying your entered details, this may
              take a minute.
            </div>
          </div>
        )}
        <div className={this.props.isModalView ? 'activation-container' : ''}>
          <ModalAsideNav
            title={this.formName}
            description={
              !this.isLinkedAccountForm && !isFormSubmitted && <p>{this.formDescription}</p>
            }
            tabs={FORM_TABS}
            moreTabs={moreTabs}
            tabsValidity={this.state.tabs}
            tabClickHandler={this.changeTab}
            activeTab={activeTab}
            activeTabContdition={!this.state.showSubmitLayer}
            isPanVerificationFailed={
              isPanVerificationFailed(
                this.props.user.poi_verification_status,
                this.props.user.company_pan_verification_status,
              ) &&
              this.props.user.isSyncExperimentEnabled &&
              FORM_TABS[2] === 'Business Details'
            }
            isBankVerificationFailed={
              FORM_TABS[3] === 'Bank Account' &&
              this.props.data.bank_details_verification_status &&
              !['initiated', 'verified'].includes(
                this.props.data.bank_details_verification_status,
              ) &&
              this.props.user.isSyncBankVerificationEnabled
            }
          />

          {/* Activation form Content */}
          <main
            className={classList(
              'form-container',
              this.state.showSubmitLayer && 'block-scroll',
              isFormLocked && 'main--full',
            )}
          >
            {this.state.showInfoHeader && (
              <div class="activation-info-container">
                For your business type, we need a few more details for activation
              </div>
            )}
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
                className={classList(
                  'device--mobile main-title-icon',
                  isCurrentTabValid && 'text-success ',
                )}
              >
                <i className={classList('i-check', isCurrentTabValid && 'drishy')} />
                {FORM_TABS[activeTab]}
              </span>

              <span className="device--desktop">
                {FORM_TABS[activeTab] === bankAccountTabName
                  ? this.bankTabHeader
                  : FORM_TABS[activeTab]}
                {FORM_TABS[activeTab] === 'Documents Verification' && (
                  <div className="onboarding-tab-subtitle">
                    {this.isUnregBiz
                      ? ''
                      : 'You can upload JPG/PNG of max. size 4MB or PDF of max. size 2 MB'}
                  </div>
                )}
              </span>
            </main-title>
            {FORM_TABS[activeTab] === 'Needs Clarification' && (
              <span className="sub-text-nc">
                You can add comments in case you have any doubts or questions regarding any issue
                (max 200 chars)
              </span>
            )}
            {/* Alerts: For linked account */}
            {this.isLinkedAccountForm && (
              <>
                {(isFormVerificationFailed && (
                  <Alert.Warning iconBefore="i-info-outline">
                    {verificationFailureError || 'Bank account verification failed'}
                  </Alert.Warning>
                )) ||
                  (isFormVerificationPending && (
                    <Alert.Warning iconBefore="i-info-outline">
                      Bank account verification is in process
                    </Alert.Warning>
                  )) ||
                  (isFormActivated && (
                    <Alert.Info iconBefore="i-done-all">The account has been activated</Alert.Info>
                  ))}
              </>
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
                if (isFormActivated && data.activation_status === 'activated') {
                  // **1. Alert: Account Activated

                  icon = 'i-done-all';
                  msg = 'Your account is activated.';
                  secondaryMsg = (
                    <React.Fragment>For any changes, please {ticketLink}.</React.Fragment>
                  );
                } else if (this.isNeedsClarificationMode()) {
                  // **2. Alert: Need clarification
                  icon = 'i-warning';
                  Component = Alert.Warning;
                  msg = `There are issues with your activation form. Please check your mail and respond at the earliest.`;
                  secondaryMsg = '';
                } else if (data.activation_status === 'rejected') {
                  // **3. Alert: Form Rejected

                  icon = 'i-close';
                  Component = Alert.Error;
                  msg =
                    'Your activation form has been rejected by our partner banks. Hence, we would not be able support your business at this moment.';
                  secondaryMsg = 'We have sent you an email with the details.';
                } else if (
                  !!this.props.user.locked &&
                  !this.props.user.isActivated &&
                  this.props.user.merchant.hold_funds
                ) {
                  icon = 'i-warning';
                  Component = Alert.Warning;
                  msg = (
                    <React.Fragment>
                      We need more information regarding your submitted details. Please{' '}
                      <SupportButton
                        type="anchor"
                        buttonLabel="Contact Support"
                        category="merchant"
                        openSection="account-activation"
                      />{' '}
                      to complete your activation.
                    </React.Fragment>
                  );
                  secondaryMsg = '';
                } else if (isFormLocked && isFormSubmitted) {
                  // **4. Alert: Form is Locked (for reasons other than above)
                  // 'locked' status has more priority than 'submitted'
                  // If admins locked form before submiddion, then this alert is not shown

                  icon = 'i-outline-lock';
                  msg =
                    'Your activation form is under review. We will let you know once your account gets activated.';
                  secondaryMsg = '';
                } else if (isFormSubmitted) {
                  // **5. Alert: Form is Submitted

                  icon = 'i-check';
                  msg = 'Our team will review the form and submitted documents.';
                  secondaryMsg = 'We will reach out on your contact email for all updates.';
                }

                {
                  msg && (
                    <Component iconBefore={icon}>
                      {msg}
                      <div className="side-description">{secondaryMsg}</div>
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
              <div style={{ display: content ? 'none' : 'inherit' }}>{documentContent}</div>
              {showRxCA && (
                <RxCaInterest
                  disabled={isFormSubmitted}
                  value={this.props.rxCaCheckboxSelect}
                  onChange={this.props.handleRxCaCheckboxChange}
                />
              )}
            </Form>
            <ShowWhen
              additionalCondition={(user) =>
                user.isOrgAllowedFunctionality('external_links') &&
                this.state.activeTab == 2 &&
                !this.props.user.instantActivation.isL1Submitted &&
                !user.isSyncExperimentEnabled
              }
            >
              <div className="subfooter">
                By submitting this form you agree to our{' '}
                <a
                  className="text-primary"
                  target="_blank"
                  href="https://razorpay.com/terms/"
                  onClick={() => onAction && onAction.trackTnCClick()}
                >
                  Terms and Conditions
                </a>
              </div>
            </ShowWhen>
          </main>

          {/* Submit form overlay view, Lock check not necessary here. Just ensured, 'Submit Form' checkbox must be disabled if locked */}
          {!isFormSubmitted &&
            this.state.showSubmitLayer &&
            (this.isLinkedAccountForm || isDedupe(this.props.user) !== 'blocked') &&
            userCanSubmitForm &&
            this.isAllTabsValid() && (
              <main className={classList('overlay-container', isFormLocked && 'main--full')}>
                <SubmitFormLayer
                  closeActivationForm={() => {
                    this.goto(FORM_TABS.length - 1);
                  }}
                  isFormLocked={isFormLocked}
                  isLinkedAccount={this.isLinkedAccountForm}
                  submitActivationForm={this.submitForm}
                  isBankVerificationFailed={
                    !this.isLinkedAccountForm &&
                    this.props.data.bank_details_verification_status &&
                    !['initiated', 'verified'].includes(
                      this.props.data.bank_details_verification_status,
                    ) &&
                    this.props.user.isSyncBankVerificationEnabled
                  }
                  fetchMerchantData={this.props.fetchMerchantDetails}
                  isSyncBankVerificationEnabled={
                    !this.isLinkedAccountForm && this.props.user.isSyncBankVerificationEnabled
                  }
                  bvsApiCount={this.props.bvsApiCount}
                  tracking={this.props.tracking}
                  trackEventsAction={this.props.trackEventsAction}
                />
              </main>
            )}

          {/* Form Footer, to show actions btns / saving state */}
          <Footer
            isSaving={this.state.isSaving}
            defaultMsg={this.state.defaultMsg}
            footerButtons={footerButtons}
            canSubmitL1Form={
              this.canSubmitL1Form && this.state.isEmailNonMandatory && !this.state.callingAPI
            }
            canSubmitNeedsClarification={
              this.hasFilledClarificationDetails && !this.state.callingAPI
            }
            isUnregBiz={this.isUnregBiz}
            isAllTabsValid={this.isAllTabsValid}
            submitL1={this.submitForm}
            saveCurrentTab={this.saveCurrentTab}
            next={this.next}
            toggleSubmitLayer={this.toggleSubmitLayer}
            tracking={this.props.tracking}
            submitClarifications={this.submitClarifications}
            activeTab={this.state.activeTab}
            isL1Submitted={this.props.user.instantActivation.isL1Submitted}
            onAction={onAction}
            isCheck={this.state.ischeck}
            fetchData={this.props.fetchMerchantDetails}
            onCheckboxChange={this.onCheckboxChange}
          />
        </div>
      </div>
    );
  }

  // returns validity
  tabValidity(i) {
    //Special handling for NDC tab
    if (
      this.props.data.activation_status === 'needs_clarification' &&
      i !== NEEDS_CLARIFICATION_STEP
    ) {
      return true;
    }

    if (i === NEEDS_CLARIFICATION_STEP) {
      return false;
    }
    return FORM_TABS_CONTENT[i].every((c) =>
      Array.isArray(c) ? c.every((d) => isFieldValid(d, this)) : isFieldValid(c, this),
    );
  }
}

export function defaultFieldProps(f) {
  const self = this;
  if (!f) {
    return;
  }
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

export function ActivationField(field) {
  if (!field) {
    return;
  }
  const {
    _cmp: Component,
    _name,
    _when,
    _optionsFn,
    _autoRenderImpure,
    _disabledWhen,
    required,
    ...rest
  } = field;
  const { documents, activation_status } = this.props.data;

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
    if (field.name === 'merchant_avg_order_value') {
      rest.options = field._optionsFn(this);
      rest.defaultValue = this.state.selected_aov;
    }
    if (field._name === 'has_url') {
      rest.options = field._optionsFn(this);
    }
  }

  if (typeof rest.customField === 'function') {
    rest.customField = rest.customField(this);
  }
  // Attach addition properties only if field is Custom Field i.e PowerSelect
  // Business Name has customField = true only for PG Activation (Not for RX Activation)

  if (field.name === 'contact_email' && rest.customField) {
    const {
      activation_form_milestone,
      isEmailNonMandatoryOnL1,
      contact_name,
      contact_email,
      user,
    } = this.props.user;

    rest.contactEmail = this.state.tempContactEmail || contact_email;
    rest.emailVerified = user?.confirmed;
    rest.isEmailNonMandatoryOnL1 = isEmailNonMandatoryOnL1;
    rest.postSuccessfulEmailVerify = this.postSuccessfulEmailVerify;
    rest.setEnableAndDisableCheckbox = this.setEnableAndDisableCheckbox;
    rest.setEnableAndDisableCheckboxOnL2 = this.setEnableAndDisableCheckboxOnL2;
    rest.isChecked = this.props.user.user?.confirmed || !this.state.isEmailOnL2;
    rest.contactName = this.state.dirty.contact_name || contact_name;
    rest.activationMilestone = activation_form_milestone;
    rest.sendErrorMessageToSegment = this.sendErrorMessageToSegment;
  }

  if (field.name === 'business_name' && rest.customField) {
    rest.options = this.state.business_name_options || [];
    rest.selected = this.state.business_name_selected_option;
    rest.onChange = this.onOptionChange;
    rest.companyPanError =
      rest.checkValidityFromAPI && !this.isOnKYCTab() && rest.checkValidityFromAPI(this);
  }

  if (field.name === 'gstin' && rest.customField) {
    const {
      props: { data, gstinDetails },
    } = this;
    const defaultGstin = gstinDetails?.defaultGstin;
    if (!this.state.gstin && defaultGstin && this.showFullGstinList) {
      this.setState((prevState) => ({
        ...prevState,
        gstin: defaultGstin,
        dirty: {
          ...prevState.dirty,
          gstin: defaultGstin,
        },
      }));
    }
    rest.options = gstinDetails?.gstinList || [];
    rest.selected = this.state.gstin;
    rest.onChange = ({ option }) => {
      this.showFullGstinList = true;
      this.setState((prevState) => ({
        ...prevState,
        gstin: option,
        showGstinDescription: false,
        dirty: {
          ...prevState.dirty,
          gstin: option,
        },
      }));
    };
    rest.description = rest.description(this);
    rest.gstinInputError =
      (rest.checkValidityFromAPI && !this.isOnKYCTab() && rest.checkValidityFromAPI(this)) ||
      (!this.state.gstin && 'Please fill out this field');
    rest.showFullGstinList = this.showFullGstinList;
    rest.gstinInputValue = this.state.gstin;
  }

  if (field.name === 'e_aadhar' && rest.customField) {
    const { isAadharEkycMandatory } = this.props.user;
    rest.isAadharEkycMandatory = isAadharEkycMandatory && this.isUnregBiz;
    rest.aadharStatus =
      this.props.data.stakeholder && this.props.data.stakeholder.aadhaar_esign_status;
    rest.isAadharLinked = this.props.data.stakeholder
      ? !!this.props.data.stakeholder.aadhaar_linked
      : true;
    rest.mobileLinkedOnChange = this.onEAadharCheckboxChange;
    rest.activeTab = this.state.activeTab;
  }

  if (typeof rest.onBlur === 'function') {
    rest.onBlur = rest.onBlur.bind(this);
  }

  if (rest.getName) {
    rest.name = rest.getName(this);
  }

  let defaultValue, key;
  if (rest.name) {
    key = rest.name;
    /*
     * Dirty data is priority as user can switch tabs fast before api success, so dirty would have latest FE data but props not
     * */
    if (this.isOnKYCTab()) {
      defaultValue = this.state.dirty[key] || null;
    } else if (Component === Input.File) {
      defaultValue = this.state.dirty[key] || (documents && documents[key] && documents[key][0].id);
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

  if (rest.addonAfter) {
    rest.addonAfter = rest.addonAfter(this);
  }

  if (rest.getPlaceholder) {
    rest.placeholder = rest.getPlaceholder(this);
  }

  if (!rest.isNotDeletable) {
    rest.onCloseClick = () => {
      this.props.deleteFile(rest.name, () => {
        this.markTabIfActive(DOCUMENT_UPLOAD_STEP);
      });
    };
  }

  if (rest.checkValidityFromAPI) {
    const error = rest.checkValidityFromAPI(this);
    if (!this.state.dirty[rest.name] && error) rest.propagatedError = error;
    else rest.propagatedError = '';
  }

  if (typeof rest.className === 'function') {
    rest.className = rest.className(this);
  }

  // temp solution for business category on NC flow
  let isNCFlowComponentDisabled = false;
  if (
    rest.name === 'business_category' ||
    rest.name === 'business_subcategory' ||
    rest.name === 'business_website' ||
    rest.name === 'business_type'
  ) {
    isNCFlowComponentDisabled = this.isOnKYCTab() ? true : false;
    defaultValue = this.props.data[rest.name];
  }

  const partnerActivationStatus = this.props?.partnerActivationData?.partner_activation
    ?.activation_status;
  if (
    !this.isOnKYCTab() && // don't check for NC tab, as we need to keep fields unlocked for NC tab
    this.props?.user?.isIndependentPartnerKYCEnabled &&
    rest.name &&
    (this.props.data?.lock_common_fields || []).includes(rest.name)
  ) {
    // disable common fields which are either under review or activated in Partner KYC
    rest.disabled = true;

    if (['activated', 'under_review'].includes(partnerActivationStatus)) {
      switch (partnerActivationStatus) {
        case 'activated':
          rest.description = 'Verified under Partner KYC';
          break;
        case 'under_review':
          rest.description = `Cannot edit this field because it's under review in Partner KYC`;
          break;
      }
    }
  }

  const _Component = rest.customField ? CustomField : Component;

  return (
    <>
      {this.isOnKYCTab() && rest.reasons && rest.reasons.length > 0 && (
        <div className="ndc-reasons">
          {rest.reasons.map((r, i) => (
            <>
              <div class="reason-container">
                <div key={i} class="reason-wrapper">
                  <i className="i i-info-circle" />
                  <div>{r}</div>
                </div>
                <button class="add-comment" onClick={(e) => this.handleComment(e, key)}>
                  Add Comment
                </button>
              </div>
              {this.state.commentlist.hasOwnProperty(key) && (
                <div class="comment-box">
                  <input
                    type="text"
                    className="form-control input-elm"
                    value={this.state.commentlist[key]}
                    placeholder="Enter your comment"
                    onChange={(e) => this.handleComment(e, key)}
                    maxlength="200"
                  />
                  <button class="delete-button" onClick={(e) => this.handleComment(e, key, true)}>
                    <i className="i i-delete" />
                  </button>
                </div>
              )}
            </>
          ))}
        </div>
      )}
      <_Component
        key={key}
        data-name={_name}
        defaultValue={defaultValue}
        disabled={isComponentDisabled || isNCFlowComponentDisabled}
        autoRender={_autoRenderImpure}
        required={
          activation_status === 'needs_clarification'
            ? false
            : typeof required === 'function'
            ? required(this)
            : required
        }
        {...rest}
      />
    </>
  );
}

function isFieldValid(field, activation) {
  const { props } = activation;
  const name = field.getName ? field.getName(activation) : field.name;
  const data = props.data;

  if (!name) {
    // what isn't submissible is valid
    return true;
  }

  if (field._when) {
    // what isn't visible is valid
    if (!field._when(activation)) {
      return true;
    }
  }

  const value =
    data[name] || (data.documents && data.documents[name] && data.documents[name][0].id);

  let isFieldRequired = field.required;

  if (typeof isFieldRequired === 'function') {
    isFieldRequired = isFieldRequired(activation);
  }

  if (name === 'e_aadhar') {
    return field.isFieldValid(activation);
  }

  if (name === 'contact_email') {
    return field.isFieldValid(activation);
  }

  if (isFieldRequired && !value) {
    field.autoFocus = true; // To autofocus first unfilled required field

    // value missing in required field
    return false;
  }

  if (
    field.name == 'promoter_pan' &&
    data.business_type == 11 &&
    hasAPIL1Error({
      poi_verification_status: data.poi_verification_status,
      is_unreg: true,
    }) &&
    !props.user.canSkipPoiValidation
  ) {
    return false;
  }

  return true;
}

function handleInstantActivationSuccess(props) {
  if (props.user.business_type == 11) {
    const { poi_verification_status, locked, isActivated, merchant } = props.user;
    if (poi_verification_status == 'verified' && !locked) {
      props.showPANStatusModal();
      fireL1FormSuccessEvents(props.user);
    } else if (!!locked && !isActivated && merchant.hold_funds) {
      fireL1FormSuccessEvents(props.user);
    }
  } else {
    const {
      isWhitelistFlow,
      isBlacklistFlow,
      isGraylistFlow,
      isL1Submitted,
    } = props.user.instantActivation;
    if (isWhitelistFlow && isL1Submitted) {
      props.showInstantActivationSuccessModal();
      fireL1FormSuccessEvents(props.user);
    } else if (isGraylistFlow && isL1Submitted) {
      props.showKYCDetailsModal();
      fireL1FormSuccessEvents(props.user);
    }
  }
}

function CustomField(props) {
  const {
    name,
    disabled,
    selected,
    validator,
    aadharStatus,
    isAadharLinked,
    companyPanError,
    gstinInputError,
    description,
    gstinInputValue = '',
    showFullGstinList = true,
    contactEmail,
    emailVerified = false,
  } = props;
  let error = '';

  switch (name) {
    case 'business_name':
      const businessName = selected.company_name;

      if (typeof validator === 'function' && isPresent(businessName)) {
        error = validator(businessName);
      }
      if (companyPanError) {
        error = companyPanError;
      }
      return (
        <div
          className={classList(
            'Input Input--required Input--small',
            disabled && 'Input--disabled',
            error && 'PowerSelectError is-mature is-invalid',
          )}
        >
          <div className="Input-label">Business Name</div>
          <div className="Input-content">
            <TypeAhead
              {...props}
              showClear={false}
              optionComponent={({ option }) => (
                <div className="activation-power-select-option">{option.company_name}</div>
              )}
              matcher={matcher}
              onBlur={(e) => props.onBlur(e, error)}
            />
            {error && <div className="Input-error">{error}</div>}
          </div>
        </div>
      );
    case 'gstin':
      let updatedOptions = props.options;
      if (typeof validator === 'function' && isPresent(selected)) {
        error = validator(selected);
      }
      if (gstinInputError) {
        error = gstinInputError;
      }
      if (!showFullGstinList) {
        updatedOptions = props.options.filter((el) => el.includes(gstinInputValue));
      }
      return (
        <div
          className={classList(
            'Input Input--required Input--small',
            disabled && 'Input--disabled',
            error && 'PowerSelectError is-mature is-invalid',
          )}
        >
          <div className="Input-content">
            <TypeAhead
              {...props}
              options={updatedOptions}
              showClear={false}
              optionComponent={({ option }) => (
                <div className="activation-power-select-option">{option}</div>
              )}
              matcher={matcher}
              onBlur={(e) => props.onBlur(e, error)}
            />
            {!error && description && <div className="Input-desc">{description}</div>}
            {error && <div className="Input-error d-block">{error}</div>}
          </div>
        </div>
      );
    case 'e_aadhar':
      return (
        <div className={classList(disabled && 'Input--disabled')}>
          <EAadhard {...props} aadharStatus={aadharStatus} isAadharLinked={isAadharLinked} />
        </div>
      );
    case 'contact_email':
      return (
        <div className={classList(disabled && 'Input--disabled')}>
          <CustomEmail {...props} emailVerified={emailVerified} contactEmail={contactEmail} />
        </div>
      );
    default:
      return null;
  }
}

function matcher({ option, searchTerm = '', searchIndices }) {
  return true;
}
