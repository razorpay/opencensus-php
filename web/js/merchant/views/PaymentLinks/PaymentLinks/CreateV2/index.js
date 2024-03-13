import React from 'react';

import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import moment from 'moment';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';

import { onChangeNotes } from 'common/new-ui/Input/PairList';
import {
  getURLQueryParams,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
} from 'common/utils/rzp-utils';
import { triggerHotjarRecording } from 'common/utils/hotjar';

import { withI18Service } from 'common/i18';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchReminders, fetchRemindersMerchantConfigs } from 'merchant/reducers/reminders';
import { updatePLInReduxList } from 'merchant/reducers/paymentlinks/list';
import { saveOnboarding } from 'merchant/reducers/onboarding';
import { updateFeatures } from 'merchant/reducers/config';
import { updateUserFeatures } from 'merchant/reducers/session';
import {
  fetchPaymentLinkV2Details,
  fetchPaymentLinkCustomFields,
} from 'merchant/reducers/paymentlinks/details';
import { luminateRow } from 'merchant/reducers/app';
import { createPaymentLinkV2 } from 'merchant/views/PaymentLinks/PaymentLinks/model';
import {
  showPayerNamePL,
  showNoExpiryPL,
  showDynamicFields,
} from 'merchant/views/PaymentLinks/utils';

import { CUSTOM_FIELDS } from './constants';
import PaymentLinkTypeSelector from './components/PaymentLinkTypeSelector';
import BaseForm from './Forms/BaseForm';
import StandardForm from './Forms/StandardForm';
import UPIForm from './Forms/UPIForm';
import track from './track';
import { getWhatsPLNotificationStatus } from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/Utils/whatsAppUtils';
import { fetchGenericFeatureStatus } from 'merchant/reducers/genericFeature';
import { FEATURE_WHATSAPP_PL } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';

const PAYMENT_LINKS_TYPES = {
  BASE: 'base',
  STANDARD: 'standard',
  UPI: 'upi',
};

const PAYMENT_LINK_FORMS = {
  [PAYMENT_LINKS_TYPES.BASE]: BaseForm,
  [PAYMENT_LINKS_TYPES.STANDARD]: StandardForm,
  [PAYMENT_LINKS_TYPES.UPI]: UPIForm,
};

export const CONTACT_PLACEHOLDER = {
  IN: '+91 9876543210',
  MY: '+60 132758792',
};

// eslint-disable-next-line react/no-unsafe
@connect(
  (state) => ({
    user: state.session.user,
    isTestMode: state.session.mode === 'test',
    reminders: state.reminders,
    isMobileResolution: state.app.isMobileResolution,
    paymentLinkRemindersConfig: state.reminders.product_configs.payment_link,
    featureStatus: state.genericFeature,
  }),
  {
    luminateRow,
    updatePLInReduxList,
    showNotification,
    saveOnboarding,
    updateFeatures,
    updateUserFeatures,
    fetchReminders,
    fetchRemindersMerchantConfigs,
    fetchGenericFeatureStatus,
  },
)
@RTracking(() => window.rzpQ.component('PaymentLinkCreateV2'))
class PaymentLinkCreateV2 extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  static getDerivedStateFromProps(nextProps, prevState) {
    const { formData } = prevState;
    const { merchant } = nextProps.user;

    // Currency value always present to support i18.
    // user.merchant.currency always available
    if (formData.currency === null) {
      return {
        ...prevState,
        formData: {
          ...prevState.formData,
          currency: merchant.currency,
        },
      };
    }

    return null;
  }

  constructor(props) {
    super();
    let linkType;
    const params = getURLQueryParams(props.location.search);

    // For PL duplication loading state
    const searchQuery = getURLQueryParams(props.location.search);
    if (searchQuery.duplicate_id) {
      linkType = PAYMENT_LINKS_TYPES.BASE;
    }

    if (props.i18.isConfigTagEnabled('payment_links.upi_payment_link')) {
      linkType = PAYMENT_LINKS_TYPES.STANDARD;
    }

    if (params.link_type && PAYMENT_LINK_FORMS.hasOwnProperty(params.link_type)) {
      linkType = params.link_type;
    }

    this.isIntentDuplicate = !!searchQuery.duplicate_id;

    this.state = {
      isLoading: true,
      isFormLocked: false,
      linkType,
      formData: {
        currency: props.user.merchant.currency,
      },
      dynamicFields: [],
    };
  }

  UNSAFE_componentWillMount() {
    track.lj.init({
      track: this.props.tracking.trackEvent,
      clone: this.isIntentDuplicate,
    });

    triggerHotjarRecording('payment_link_v2_creation');
  }

  componentDidMount() {
    const {
      fetchGenericFeatureStatus,
      user: { id },
    } = this.props;
    fetchGenericFeatureStatus(id, FEATURE_WHATSAPP_PL);
    this.prepareDataForPaymentLinkCreation()
      .then(() => {
        this.setState({ isLoading: false }, () => {
          if (this.isIntentDuplicate && this.state.formData.notes) {
            this.onChangeNotes(this.state.formData.notes);
          }
        });
      })
      .catch(() => {
        // TODO: Handle Error case
        this.setState({
          isLoading: false,
        });
      });
  }

  prepareDataForPaymentLinkCreation() {
    const { location, fetchReminders, reminders, fetchRemindersMerchantConfigs } = this.props;

    const promiseList = [];
    const searchQuery = getURLQueryParams(location.search);

    if (searchQuery.duplicate_id) {
      promiseList.push(this.fetchIfIntentDuplicate(searchQuery.duplicate_id));
    }

    if (!reminders.reminders.items.length) {
      promiseList.push(fetchReminders());
    }

    if (!reminders.merchant_config.items.length) {
      promiseList.push(fetchRemindersMerchantConfigs());
    }

    if (showDynamicFields()) {
      promiseList.push(this.fetchDynamicFields());
    }

    return Promise.allSettled(promiseList);
  }

  fetchDynamicFields = async () => {
    try {
      const res = await fetchPaymentLinkCustomFields();
      const fields = res?.data?.configurations || [];

      if (fields.length > 0) {
        this.setState({ dynamicFields: fields });
      }
    } catch (error) {
      this.props.showNotification({
        type: 'error',
        message: error?.errors?.join(' '),
      });
    }
  };

  // Duplicate Payment Link
  fetchIfIntentDuplicate = (duplicatePLId) => {
    return fetchPaymentLinkV2Details(duplicatePLId)
      .then(({ data }) => {
        this.isIntentDuplicate = true;
        let expire_by = data.expire_by && moment(data.expire_by * 1000);

        // If null or is before current time
        if (!expire_by || expire_by.diff(moment()) < 0) {
          expire_by = '';
        }

        const defaultValueNotes = Object.keys(data.notes || {}).map((key) => ({
          key,
          value: data.notes[key],
        }));

        const isReminderEnabled = data.reminder_status && !(data.reminder_status === 'disabled');

        const newState = {
          linkType: data.upi_link ? 'upi' : 'standard',
          formData: {
            currency: data.currency,
            description: data.description,
            amount: i18CurrencyConversionFromMinorUnitToCommonUnit(data.amount, data.currency),
            accept_partial: data.accept_partial ? '1' : '0',
            sms_notify: data.notify && data.notify.sms ? '1' : '0',
            email_notify: data.notify && data.notify.email ? '1' : '0',
            email: data.customer.email,
            contact: data.customer.contact,
            notes: defaultValueNotes,
            expire_by,
            reminder_enable: isReminderEnabled ? '1' : '0',
          },
        };

        if (showPayerNamePL()) {
          newState.formData.name = data.customer?.name ?? '';
        }

        this.setState(newState);
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];

        this.props.showNotification({
          type: 'error',
          message: error,
        });
      });
  };

  selectTemplate = (linkType) => this.setState({ linkType });

  onFormSubmit = (data) => {
    const { showNotification } = this.props;
    const reqPayload = {
      ...this.state.formData,
      ...(data || {}),
    };

    if (!showNoExpiryPL() && !reqPayload?.expire_by) {
      showNotification({
        type: 'error',
        message: 'Expire By is mandatory!',
      });
      return false;
    }

    this.setState({
      isFormLocked: true,
    });

    const { user, splitz, featureStatus } = this.props;

    const { isNotificationEnabled } = getWhatsPLNotificationStatus({
      user,
      splitz,
      featureStatus,
    });

    let notificationMSG = 'Payment link created successfully.';
    const notifyMedium = [];

    if (reqPayload.sms_notify && !user.isPlV2DisableAllSmsEnabled) {
      notifyMedium.push('SMS');
    }

    if (reqPayload.email_notify && !user.isPlV2DisableAllEmailEnabled) {
      notifyMedium.push('Email');
    }

    if (isNotificationEnabled) {
      reqPayload.whatsapp_notify = '1';
      notifyMedium.push('WhatsApp');
    }

    if (notifyMedium.length > 0) {
      notificationMSG += ` Sending via ${notifyMedium.join(' and ')}`;
    }

    reqPayload.amount = i18CurrencyConversionFromCommonUnitToMinorUnit(
      reqPayload.amount,
      reqPayload.currency,
    );

    track.lj.form.create();

    return createPaymentLinkV2(reqPayload)
      .then((resp) => {
        this.props.showNotification({
          type: 'success',
          message: notificationMSG,
        });

        track.lj.form.success();
        track.segment.form.success(resp, !!this.isIntentDuplicate);

        this.setState({
          isFormLocked: false,
        });

        const IS_MODAL_VIEW = !!this.props.onClose;
        const entityId = resp.data.id;

        if (IS_MODAL_VIEW) {
          this.props.updatePLInReduxList(resp, true);
          this.props.luminateRow(entityId);

          setTimeout(this.props.onClose, 50);
        } else {
          const redirectUrl = `/paymentlinks/${entityId}`;

          this.props.history.push(redirectUrl);
        }
      })
      .catch((err) => {
        const error = (err.errors || [])[0];
        this.props.showNotification({
          type: 'error',
          message: error,
        });

        track.lj.form.fail({ response: error });
        track.segment.form.fail(error, !!this.isIntentDuplicate);

        this.setState({
          isFormLocked: false,
        });
      });
  };

  updateDate = (newDate) => {
    this.setState((prevState) => {
      return {
        formData: {
          ...prevState.formData,
          expire_by: newDate,
        },
      };
    });
  };

  onChangeNotes = (pairs) => {
    const notes = onChangeNotes(pairs);
    this.setState((prevState) => {
      return {
        formData: {
          ...prevState.formData,
          notes,
        },
      };
    });
  };

  // eslint-disable-next-line consistent-return
  onFieldChange = (event) => {
    const { value, name, id } = event.target;

    const fieldValue = value;
    const fieldName = name;

    const isInvalidField = !fieldName || fieldName.indexOf('notes[') > -1;
    if (isInvalidField) return true;

    const formData = {
      // some case is breaking in UI , TODO need to check and remove this
      // eslint-disable-next-line react/no-access-state-in-setstate
      ...this.state.formData,
    };

    const isChecked = !!value;

    if (fieldName === 'contact') {
      formData.sms_notify = isChecked ? '1' : '0';
      document.querySelector(`[name=sms_notify]`).checked = isChecked;
      formData[fieldName] = fieldValue;
    } else if (fieldName === 'email') {
      formData.email_notify = isChecked ? '1' : '0';
      document.querySelector(`[name=email_notify]`).checked = isChecked;
      formData[fieldName] = fieldValue;
    } else if (id.includes(CUSTOM_FIELDS)) {
      formData[CUSTOM_FIELDS] = { ...(formData[CUSTOM_FIELDS] || {}), [fieldName]: fieldValue };
    } else {
      formData[fieldName] = fieldValue;
    }

    this.setState({ formData });
  };

  // Get confirmation before user closes the creation form
  onFormAbruptClose = () => {
    const curFormData = this.state.formData;
    const dirtyFields = Object.keys(curFormData);

    let formUnsaved = false;

    let count = 0;
    dirtyFields.forEach((field) => {
      if (typeof curFormData[field] !== 'undefined') {
        count++;
      }

      if (count > 2) {
        formUnsaved = true;
      }
    });

    if (formUnsaved) {
      this.context
        .confirm({
          className: 'pl-creation-confirm-modal',
          header: 'Do you want to close this form?',
          message: 'Changes that you made will be discarded.',
          affirmativeLabel: 'Leave',
          abortLabel: 'Stay',
          action: () => {
            this.props.onClose();

            track.lj.form.cancelConfirm({
              status: 'stay',
            });
            track.segment.form.cancelConfirm({
              status: 'stay',
            });
          },
        })
        .catch(() => {
          track.lj.form.cancelConfirm({
            status: 'leave',
          });
          track.segment.form.cancelConfirm({
            status: 'leave',
          });
        });
    } else {
      this.props.onClose();

      track.lj.form.cancel();
      track.segment.form.close();
    }
  };

  render() {
    const { props, state } = this;
    const { linkType, dynamicFields } = state;
    const {
      user,
      featureStatus,
      i18: { isConfigTagEnabled },
    } = props;
    const { merchant } = user;
    // i18: Hide the payment link type selection for  based on the tag, currently we are only allowing the standard form.
    let showLinkTypeSelectionView = !linkType;
    if (isConfigTagEnabled('payment_links.upi_payment_link')) {
      showLinkTypeSelectionView = false;
    }
    const CurrentForm = PAYMENT_LINK_FORMS[linkType];

    const isModalView = props.onClose;
    return (
      <div className="PaymentLinks--CreateV2">
        {showLinkTypeSelectionView && (
          <PaymentLinkTypeSelector
            isTestMode={props.isTestMode}
            isModalView={isModalView}
            selectTemplate={this.selectTemplate}
            history={props.history}
          />
        )}

        {!showLinkTypeSelectionView && (
          <CurrentForm
            disableCurrencySelect={!user.isInttCurrenciesEnabled}
            isIntentDuplicate={this.isIntentDuplicate}
            showAnimationOnLoading={!this.isIntentDuplicate}
            isModalView={isModalView}
            formData={state.formData}
            isLoading={state.isLoading}
            disabled={state.isFormLocked}
            remindersConfig={props.paymentLinkRemindersConfig}
            isDescriptionRequired={user.isPaymentLinkDescriptionRequired}
            onClose={this.onFormAbruptClose}
            onChange={this.onFieldChange}
            onSubmit={this.onFormSubmit}
            updateDate={this.updateDate}
            onChangeNotes={this.onChangeNotes}
            isMobileResolution={props.isMobileResolution}
            history={props.history}
            contactPlaceholder={CONTACT_PLACEHOLDER[merchant.country_code]}
            dynamicFields={dynamicFields}
            featureStatus={featureStatus}
          />
        )}
      </div>
    );
  }
}

export default withRouter(withI18Service(PaymentLinkCreateV2));
