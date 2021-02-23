import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import AsyncButton from 'react-async-button';
import RTracking from 'react-tracking';

import ShowWhen from 'merchant/components/ShowWhen';
import Collapsible from 'merchant/components/Collapsible';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { showNotification } from 'merchant_common/reducers/notifications';
import { autoPrefixUrls, addPrefixToObjectKeys, prevent, classList } from 'common/utils/rzp-utils';
import { trackDiffInFormFields } from 'merchant/utils/track-utils';

import { merchantFetch } from 'merchant/utils/ajax';
import { updateSession } from 'merchant/reducers/session';
import User from 'merchant/models/User';
import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import { showInstantActivationSuccessModal, showKYCDetailsModal } from 'merchant/reducers/home';

import formFields, { BUSINESS_TYPE_OPTIONS } from './L1FormMap';
import { trackTnCClick } from './ga_new';
import {
  fireFormStartEvents,
  fireL1FormSuccessEvents,
  fireL1FormErrorEvents,
  updateHubSpotContactsProperties,
} from './ActivationFormMarketingEvents.js';

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

let FORM_TABS; // Maintains naming of the tabs
const BUSINESS_CATEGORY_FIELD = 1;

@RTracking(() => window.rzpQ.component('ActivationWizard'))
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
  },
)
export default class ActivationWizard extends React.Component {
  state = {
    dirty: {},
    tabs: [],
    same_address:
      this.props.data &&
      (this.props.data.business_operation_pin === this.props.data.business_registered_pin
        ? '1'
        : '0'), // '1' => checkbox ticked
    has_url: this.props.data && this.props.data.business_website === '' ? '0' : '1', // '0' => 0th radio button, value exists
  };

  constructor(props) {
    super(props);
    this.prepareTabs(props);

    if (props.rpc) {
      const { submitForm, notifyFormValidity, notifyWindowResize } = props.rpc;

      submitForm((reply) => {
        this.onActivationSuccess = reply;
        this.submitForm();
      });

      notifyFormValidity((reply) => {
        this.onFormValidityChange = reply;
      });
    }
  }

  prepareTabs(props) {
    FORM_TABS = [...formFields];

    // Business Category in "Business Model" exists in main activation form. Setting value dynamically from props.
    FORM_TABS[BUSINESS_CATEGORY_FIELD][0].options = ['--Select--'].concat(
      Object.keys(props.categories).map((c) => ({
        name: c,
        label: props.categories[c].description,
      })),
    );

    defaultFieldProps.call(this, FORM_TABS); // Set the default props for all tab content views
  }

  populateReqData(field, reqData, currentDirty) {
    const name = field.name;

    if (!name) {
      return;
    }

    const fieldVal = name in currentDirty ? currentDirty[name] : this.props.data[name];

    reqData[name] = fieldVal;

    // For business website empty string => user don't have website. null => user didn't attempt the field.
    const allowEmptyString = ['business_website', 'gstin'];
    if (allowEmptyString.indexOf(name) === -1) {
      reqData[name] = reqData[name] === '' ? null : fieldVal; // '' -> null. DB has default values as NULL.
    }
  }

  get formData() {
    const currentDirty = this.state.dirty;
    const reqData = {};

    FORM_TABS.forEach((field) => {
      if (Array.isArray(field)) {
        return field.forEach((field) => this.populateReqData(field, reqData, currentDirty));
      }

      return this.populateReqData(field, reqData, currentDirty);
    });

    if (!Object.keys(reqData).length) {
      return; // Nothing changed on the currentActive Tab, although the data do exist in dirty
    }

    return reqData;
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
      this.preloadSuccessAsset();
    }

    const {
      activation_progress,
      activated,
      activation_status,
      activation_flow,
      submitted,
      international,
      promoter_pan,
      business_type,
      poi_verification_status,
    } = data;

    // Updating % activation_progress (side bar) and other important activation fields
    const user = (this.user = new User({
      ...session.user,
      activation_progress,
      activated,
      activation_status,
      activation_flow,
      international,
      promoter_pan,
      business_type,
      submitted: +submitted,
      poi_verification_status,
    }));

    this.props.updateSession({
      user,
      mode: session.mode,
    });
  }

  @RTracking((props, state) => {
    const { tracking } = props;
    const fields = trackDiffInFormFields(props.data, state.dirty);
    return fields.forEach((field) =>
      tracking.trackEvent(
        window.rzpQ.onbr().initiated('act.provide_act_details', {
          ...field,
        }),
      ),
    );
  })
  submitForm = () => {
    const data = this.formData;
    const { tracking } = this.props;
    return merchantFetch({
      url: 'merchant/instant_activation',
      method: 'POST',
      mode: 'live',
      data,
      accountId: this.props.accountId,
    })
      .then((response) => {
        this.props.updateActivationData(response.data);
        this.setState({
          dirty: {
            ...this.state.dirty,
            promoter_pan: '',
          },
        });
        const { poi_verification_status } = response.data;
        let has_pan_error = poi_verification_status === 'incorrect_details';

        if (this.onActivationSuccess) {
          if (!has_pan_error) {
            this.onActivationSuccess(response);
          } else {
            this.onActivationSuccess({ success: false });
          }
        }

        this.updateSession(response.data); // Updating % activation_progress (side bar)

        const user = this.user;
        const { isWhitelistFlow, isBlacklistFlow, isGraylistFlow } = user.instantActivation;

        if (isWhitelistFlow) {
          this.props.showInstantActivationSuccessModal();
        } else if (isGraylistFlow) {
          this.props.showKYCDetailsModal();
        }

        fireL1FormSuccessEvents(user);
        tracking.trackEvent(window.rzpQ.onbr().initiated('act.submit_form'));
        this.props.sendEventsForSubMerchantView(
          window.rzpQ
            .routeActions()
            .success('route.linked_account.activate_account.business_details'),
        );

        if (!has_pan_error) {
          return this.props.history.replace('/');
        }
      })
      .catch((err) => {
        if (err.errors.length && err.errors[0]) {
          this.props.showNotification({
            type: 'error',
            message: err.errors,
          });
        }

        fireL1FormErrorEvents();

        if (this.onActivationSuccess) {
          this.onActivationSuccess({ success: false });
        }

        return err;
      });
  };

  onChange = ({ target }) => {
    const stateName = target.getAttribute('data-name');
    let fieldValue = target.value;
    const fieldName = target.name;

    const sideEffectFieldsToUpdate = {}; // Some fields might lead to other fields get dirty. So, they also needs to be updated alongside
    const { dirty } = this.state;
    const { data } = this.props;

    if (stateName === 'has_url') {
      if (fieldValue === '1') {
        this.prevBusinessWebsiteVal =
          'business_website' in dirty ? dirty.business_website : this.props.data.business_website;
        sideEffectFieldsToUpdate.business_website = '';
      } else {
        sideEffectFieldsToUpdate.business_website = this.prevBusinessWebsiteVal;
      }
    }

    /* Step 5: Business category and sub category are always marked dirty in pairs. BE validates them in pair. */
    if (fieldName === 'business_category') {
      // Set first option in new set of subcategory. It remains '', it would convert to null before making api call.
      sideEffectFieldsToUpdate.business_subcategory = '';
      sideEffectFieldsToUpdate.business_model = ''; // Reset Business Model as well.

      // Update Business Subcategory in view
      let el = document.querySelector('.form-container [name=business_subcategory]');
      el && (el.value = '');

      // Update Business Model in view
      el = document.querySelector('.form-container [name=business_model]');
      el && (el.value = '');
    }

    if (fieldName === 'business_subcategory') {
      sideEffectFieldsToUpdate.business_category =
        dirty.business_category || data.business_category;
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

  handleUIUpdate() {
    return this.props.handleUIUpdate && this.props.handleUIUpdate();
  }

  componentDidMount() {
    this.handleUIUpdate();
    fireFormStartEvents();
  }

  componentDidUpdate() {
    this.handleUIUpdate();
  }

  render() {
    const isFormLocked = !!this.props.data.locked;
    const { tracking } = this.props;

    const content = FORM_TABS.map((field, i) => {
      if (Array.isArray(field)) {
        if (field[0].compressed) {
          return (
            <Collapsible
              title={(collapsibleOpen) => (
                <span
                  className="text-primary"
                  onClick={() => {
                    tracking.trackEvent(window.rzpQ.onbr().initiated('act.view_signup_fields'));
                  }}
                >
                  {collapsibleOpen ? 'Hide' : 'Show'} previously filled details
                </span>
              )}
              childrenPosition="top"
              class="CollapsibleFields"
            >
              <Input.Group key={i}>{field.slice(1).map(ActivationField, this)}</Input.Group>
            </Collapsible>
          );
        }

        return <Input.Group key={i}>{field.map(ActivationField, this)}</Input.Group>;
      }

      return ActivationField.call(this, field);
    });

    return (
      <div className="Activation--wizard Wizard">
        <main className={classList('form-container', isFormLocked && 'main--full')}>
          <main-title class="main-title">Activate your account</main-title>
          <main-subtitle>
            <p>Enable live transactions by filling in a few more details</p>
          </main-subtitle>

          <Form onChange={this.onChange} layout="tabular">
            {content}
          </Form>
        </main>
        <footer>
          {renderTnCLink()}
          <AsyncButton
            type="button"
            className="btn btn-primary submit-btn m-l"
            onClick={this.submitForm}
            disabled={!this.tabValidity()}
            pendingText="Submitting..."
          >
            Activate Account
          </AsyncButton>
        </footer>
      </div>
    );
  }

  // returns validity
  tabValidity() {
    const data = this.formData;

    const isValid =
      data !== void 0 &&
      FORM_TABS.every((c) =>
        Array.isArray(c)
          ? c.every((d) => isFieldValid(d, this, data))
          : isFieldValid(c, this, data),
      );

    if (this.onFormValidityChange) {
      this.onFormValidityChange(isValid);
    }

    return isValid;
  }
}

function ActivationField(field) {
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
    /*
     * Dirty data is priority as user can switch tabs fast before api success, so dirty would have latest FE data but props not
     * */
    defaultValue = this.state.dirty[key] || this.props.data[key];
  } else if (_name) {
    defaultValue = this.state[_name];
    key = _name;
  }

  const isFormLocked = !!this.props.data.locked;

  // For LA, form is automatically locked when submitted(activated). For main form, it can be manually controlled.
  let isComponentDisabled = isFormLocked;

  // TODO: Ideally, what's disabled cannot be 'required = true'. Currently no such requirement. To handle, support 'required' as a function
  if (_disabledWhen && _disabledWhen(this)) {
    // Overiride the value if it's disabled
    isComponentDisabled = true;
  }

  // Show bank account number if it's activated/locked
  if (rest.hasOwnProperty('type') && rest.type === 'password' && isFormLocked) {
    rest.type = 'text';
  }

  if (rest.description && typeof rest.description === 'function') {
    rest.description = rest.description(this);
  }

  if (rest.checkValidityFromAPI) {
    const error = rest.checkValidityFromAPI(this);
    if (!this.state.dirty[rest.name] && error) rest.propagatedError = error;
    else rest.propagatedError = '';
  }

  return (
    <Component
      key={key}
      data-name={_name}
      defaultValue={defaultValue}
      disabled={isComponentDisabled}
      autoRender={_autoRenderImpure}
      required={typeof required === 'function' ? required(this) : required}
      {...rest}
    />
  );
}

function isFieldValid(field, activation, data) {
  if (!field.name || data[field.name] === void 0) {
    // what isn't submissible is valid
    return true;
  }
  if (field._when) {
    // what isn't visible is valid
    if (!field._when(activation)) {
      return true;
    }
  }

  const value = data[field.name];
  let isFieldRequired = field.required;

  if (typeof isFieldRequired === 'function') {
    isFieldRequired = isFieldRequired(activation);
  }

  if (isFieldRequired && !value) {
    field.autoFocus = true; // To autofocus first unfilled required field

    // value missing in required field
    return false;
  }

  if (typeof field.validator === 'function' && field.validator(value)) {
    return false;
  }

  return true;
}

function renderTnCLink() {
  return (
    <span>
      <small>
        By submitting this form you agree to our{' '}
        {/* only merchants of our can see the TnC link rest will only see label */}
        <ShowWhen additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}>
          <a
            className="text-primary"
            target="_blank"
            href="https://razorpay.com/terms/"
            onClick={trackTnCClick}
          >
            Terms and Conditions
          </a>
        </ShowWhen>
        <ShowWhen additionalCondition={(user) => !user.isOrgAllowedFunctionality('external_links')}>
          Terms and Conditions
        </ShowWhen>
      </small>
    </span>
  );
}
