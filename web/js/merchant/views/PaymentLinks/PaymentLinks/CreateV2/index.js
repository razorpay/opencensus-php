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
import { createPaymentLinkV2 } from '../model';
import { getURLQueryParams, paiseToRupees } from 'common/utils/rzp-utils';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import track from './track';

const PAYMENT_LINK_FORMS = {
  base: BaseForm,
  standard: StandardForm,
  upi: UPIForm,
};

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    isTestMode: state.session.mode === 'test',
    reminders: state.reminders,
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

  constructor(props) {
    super();
    let linkType;
    const params = getURLQueryParams(props.location.search);

    // For PL duplication loading state
    const searchQuery = getURLQueryParams(props.location.search);
    if (searchQuery.duplicate_id) {
      linkType = 'base';
    } else if (params.link_type && PAYMENT_LINK_FORMS.hasOwnProperty(params.link_type)) {
      linkType = params.link_type;
    }

    this.isIntentDuplicate = !!searchQuery.duplicate_id;

    this.state = {
      isLoading: true,
      isFormLocked: false,
      linkType,
      formData: {},
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
            amount: paiseToRupees(data.amount),
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
    const reqPayload = {
      ...this.state.formData,
      ...data,
    };

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
    const showLinkTypeSelectionView = !linkType;
    const CurrentForm = PAYMENT_LINK_FORMS[linkType];

    const isModalView = props.onClose;
    return (
      <div class="PaymentLinks--CreateV2">
        {showLinkTypeSelectionView && (
          <PaymentLinkTypeSelector
            isTestMode={props.isTestMode}
            isModalView={isModalView}
            selectTemplate={this.selectTemplate}
          />
        )}

        {!showLinkTypeSelectionView && (
          <CurrentForm
            isIntentDuplicate={this.isIntentDuplicate}
            showAnimationOnLoading={!this.isIntentDuplicate}
            isModalView={isModalView}
            formData={state.formData}
            isLoading={state.isLoading}
            disabled={state.isFormLocked}
            remindersConfig={props.paymentLinkRemindersConfig}
            isDescriptionRequired={props.user.isPaymentLinkDescriptionRequired}
            onClose={this.onFormAbruptClose}
            onChange={this.onFieldChange}
            onSubmit={this.onFormSubmit}
            updateDate={this.updateDate}
            onChangeNotes={this.onChangeNotes}
            history={props.history}
          />
        )}
      </div>
    );
  }
}
