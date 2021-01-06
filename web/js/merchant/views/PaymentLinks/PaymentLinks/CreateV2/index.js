import { connect } from 'react-redux';
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
    // For PL duplication loading state
    const searchQuery = getURLQueryParams(props.location.search);
    if (searchQuery.duplicate_id) {
      linkType = 'base';
    }

    this.isIntentDuplicate = !!searchQuery.duplicate_id;

    this.state = {
      isLoading: true,
      isFormLocked: false,
      linkType,
      formData: {},
    };
  }

  componentWillMount() {
    track.lj.init({
      track: this.props.tracking.trackEvent,
      clone: this.isIntentDuplicate,
    });

    triggerHotjarRecording('payment_link_v2_creation');
  }

  componentDidMount() {
    this.prepareDataForPaymentLinkCreation()
      .then((resp) => {
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
      .catch((err) => {
        // TODO: Handle Error case
        this.setState({
          isLoading: false,
        });
      });
  }

  prepareDataForPaymentLinkCreation() {
    const promiseList = [];

    if (!this.props.user.isVirtualAccountsEnabled) {
      this.enableVAFeature();
    }

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

  enableVAFeature = () => {
    // To Use UPI BE internally uses the VA
    const FEATURE = 'virtual_accounts';

    if (this.props.isTestMode) {
      return this.props
        .updateFeatures(
          {
            features: {
              [FEATURE]: 1,
            },
          },
          this.props.user.current,
        )
        .then(() => {
          updateUserFeatures(FEATURE, true);
        });
    }

    return this.props
      .saveOnboarding(FEATURE, {
        business_model: this.props.user.business_model,
      })
      .then(() => {
        updateUserFeatures(FEATURE, true);
      });
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
      notificationMSG += ' Sending via ' + notifyMedium.join(' and ');
    }

    track.lj.form.create();

    return createPaymentLinkV2(reqPayload)
      .then((resp) => {
        this.props.showNotification({
          type: 'success',
          message: notificationMSG,
        });

        track.lj.form.success();

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
          const redirectUrl = '/paymentlinks/' + entityId;

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

        this.setState({
          isFormLocked: false,
        });
      });
  };

  updateDate = (newDate) => {
    this.setState({
      formData: {
        ...this.state.formData,
        expire_by: newDate,
      },
    });
  };

  onChangeNotes = (pairs) => {
    const notes = onChangeNotes(pairs);
    this.setState({
      formData: {
        ...this.state.formData,
        notes,
      },
    });
  };

  onFieldChange = (event) => {
    const fieldValue = event.target.value;
    const fieldName = event.target.name;

    const isInvalidField = !fieldName || fieldName.indexOf('notes[') > -1;
    if (isInvalidField) return true;

    const formData = {
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
          },
        })
        .catch(() => {
          track.lj.form.cancelConfirm({
            status: 'leave',
          });
        });
    } else {
      this.props.onClose();

      track.lj.form.cancel();
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
            onClose={this.onFormAbruptClose}
            onChange={this.onFieldChange}
            onSubmit={this.onFormSubmit}
            updateDate={this.updateDate}
            onChangeNotes={this.onChangeNotes}
          />
        )}
      </div>
    );
  }
}
