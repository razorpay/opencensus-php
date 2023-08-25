import React from 'react';

import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import moment from 'moment';
import { withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';

import PaymentLinkTypeSelector from './components/PaymentLinkTypeSelector';
import BaseForm from './Forms/BaseForm';
import StandardForm from './Forms/StandardForm';
import UPIForm from './Forms/UPIForm';
import { onChangeNotes } from 'common/new-ui/Input/PairList';

import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchReminders, fetchRemindersMerchantConfigs } from 'merchant/reducers/reminders';
import { updatePLInReduxList } from 'merchant/reducers/paymentlinks/list';
import { saveOnboarding } from 'merchant/reducers/onboarding';
import { updateFeatures } from 'merchant/reducers/config';
import { updateUserFeatures } from 'merchant/reducers/session';
import { fetchPaymentLinkV2Details } from 'merchant/reducers/paymentlinks/details';
import { luminateRow } from 'merchant/reducers/app';
import { createPaymentLinkV2 } from 'merchant/views/PaymentLinks/PaymentLinks/model';
import {
  getURLQueryParams,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
} from 'common/utils/rzp-utils';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import track from './track';
import { showPayerNamePL, showNoExpiryPL } from 'merchant/views/PaymentLinks/utils';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

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
  MY: '+60 60132758792',
};

// eslint-disable-next-line react/no-unsafe
@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    isTestMode: state.session.mode === 'test',
    reminders: state.reminders,
    isMobileResolution: state.app.isMobileResolution,
    paymentLinkRemindersConfig: state.reminders.product_configs.payment_link,
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
  },
)
@RTracking(() => window.rzpQ.component('PaymentLinkCreateV2'))
export default class PaymentLinkCreateV2 extends React.Component {
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

    if (props.user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PAYMENT_LINKS.UPIPaymentLink)) {
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
    this.prepareDataForPaymentLinkCreation()
      .then(() => {
        this.setState(
          {
            isLoading: false,
          },
          () => {
            if (this.isIntentDuplicate && this.state.formData.notes) {
              this.onChangeNotes(this.state.formData.notes);
            }
          },
        );
      })
      .catch(() => {
        // TODO: Handle Error case
        this.setState({
          isLoading: false,
        });
      });
  }

  prepareDataForPaymentLinkCreation() {
    const promiseList = [];

    const searchQuery = getURLQueryParams(this.props.location.search);
    if (searchQuery.duplicate_id) {
      promiseList.push(this.fetchIfIntentDuplicate(searchQuery.duplicate_id));
    }

    if (!this.props.reminders.reminders.items.length) {
      promiseList.push(this.props.fetchReminders());
    }

    if (!this.props.reminders.merchant_config.items.length) {
      promiseList.push(this.props.fetchRemindersMerchantConfigs());
    }

    return Promise.all(promiseList);
  }

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
      ...data,
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

    let notificationMSG = 'Payment link created successfully.';
    const notifyMedium = [];

    if (reqPayload.sms_notify) {
      notifyMedium.push('SMS');
    }

    if (reqPayload.email_notify) {
      notifyMedium.push('Email');
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
    const fieldValue = event.target.value;
    const fieldName = event.target.name;

    const isInvalidField = !fieldName || fieldName.indexOf('notes[') > -1;
    if (isInvalidField) return true;

    const formData = {
      // some case is breaking in UI , TODO need to check and remove this
      // eslint-disable-next-line react/no-access-state-in-setstate
      ...this.state.formData,
      [fieldName]: fieldValue,
    };

    const isChecked = !!event.target.value;
    if (fieldName === 'contact') {
      formData.sms_notify = isChecked ? '1' : '0';
      document.querySelector(`[name=sms_notify]`).checked = isChecked;
    } else if (fieldName === 'email') {
      formData.email_notify = isChecked ? '1' : '0';
      document.querySelector(`[name=email_notify]`).checked = isChecked;
    }

    this.setState({
      formData,
    });
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
    const { linkType } = state;
    const { user } = props;
    const { merchant } = user;

    // i18: Hide the payment link type selection for  based on the tag, currently we are only allowing the standard form.
    let showLinkTypeSelectionView = !linkType;
    if (user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PAYMENT_LINKS.UPIPaymentLink)) {
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
            showPayerName={showPayerNamePL()}
            contactPlaceholder={CONTACT_PLACEHOLDER[merchant.country_code]}
          />
        )}
      </div>
    );
  }
}
