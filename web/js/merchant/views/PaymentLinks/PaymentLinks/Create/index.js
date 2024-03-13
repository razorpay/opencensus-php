import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';
import PropTypes from 'prop-types';
import ShowWhen from 'merchant/components/ShowWhen';
import Alert from 'common/new-ui/Alert';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button, { AsyncBtn } from 'common/new-ui/Button';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import PaymentLinkFormFields, { getCustomNotesOptions } from './Fields';

import moment from 'moment';
import { createPaymentLink } from 'merchant/views/PaymentLinks/PaymentLinks/model';
import { dateCalculator } from 'common/new-ui/Input/Calendar';
import { timeCalculator } from 'common/new-ui/Input/Time';

import { onChangeNotes } from 'common/new-ui/Input/PairList';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { updatePLInReduxList } from 'merchant/reducers/paymentlinks/list';
import { fetchPaymentLinkDetails } from 'merchant/reducers/paymentlinks/details';
import { fetchReminders, fetchRemindersMerchantConfigs } from 'merchant/reducers/reminders';
import { luminateRow } from 'merchant/reducers/app';

import Spinner from 'common/ui/Spinner';

import {
  getURLQueryParams,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  findBy,
  classList,
} from 'common/utils/rzp-utils';
import {
  trackOpenCreateForm,
  closePaymentLinkForm,
  trackSaveDuplicatePaymentLink,
} from 'merchant/views/PaymentLinks/PaymentLinks/ga';
import { generateField } from './Utils';
import track from './track';
import { transformPLDetails_NewToOld } from 'merchant/views/PaymentLinks/PaymentLinks/js/transformer';

const FORM_FIELDS = {
  title: 'Payment Link',
  desc: 'The link gets expired automatically once its paid.',
  url: '/paymentlinks/new',
  content: [...PaymentLinkFormFields],
  onCreate: createPaymentLink,
};

// this func causing issue if we move to constructor , need to rewrite this whole
// eslint-disable-next-line consistent-return
function defaultFieldProps(f) {
  // eslint-disable-next-line babel/no-invalid-this
  const self = this;

  if (Array.isArray(f)) {
    return f.forEach(defaultFieldProps.bind(self));
  } else if (f.hasOwnProperty('inlineFields') && Array.isArray(f.inlineFields)) {
    return f.inlineFields.forEach(defaultFieldProps.bind(self));
  }

  if (!f._cmp) {
    f._cmp = Input;
  }

  if (f.name === 'notes') {
    f.onChange = self.onChangeNotes;
    f.onAddNew = self.onAddNewNote;
  }

  if (f.name === 'first_payment_min_amount') {
    f.validator = f.validator.bind(self);
  }

  if (f._name === 'expire_by_date') {
    f.onChange = self.onDateChange.bind(self);
  }
  if (f.name === 'expire_by') {
    f.onChange = self.onTimeChange.bind(self);
  }
  if (f.name === 'receipt') {
    f.required = self.props.user.isInvoiceReceiptMandatory;
  }
}

function WizardFields(field) {
  const {
    _cmp: Component,
    _name,
    _when,
    _featureEnabled,
    _autoRenderImpure,
    _disabledWhen,
    required,
    ...rest
  } = field;

  if (_when && !_when(this)) {
    return null;
  }

  let defaultValue, key, isComponentDisabled;

  if (rest.name) {
    key = rest.name;
    // eslint-disable-next-line react/no-this-in-sfc
    defaultValue = this.state.dirty[key]; // Form state is stored in dirty

    // eslint-disable-next-line babel/no-unused-expressions
    key === 'expire_by' && defaultValue;
  } else if (_name) {
    // eslint-disable-next-line react/no-this-in-sfc
    defaultValue = this.state._name[_name];
    key = _name;
  }

  key += field.label;

  if (rest.description && typeof rest.description === 'function') {
    rest.description = rest.description(this);
  }

  // eslint-disable-next-line react/no-this-in-sfc
  if (this.state.parentFormLock || (_disabledWhen && _disabledWhen(this))) {
    isComponentDisabled = true;
  }

  let isRequired = required;
  if (typeof isRequired === 'function') {
    isRequired = isRequired(this);
  }

  let component = (
    <Component
      key={key}
      data-name={_name}
      defaultValue={defaultValue}
      autoRender={_autoRenderImpure}
      disabled={isComponentDisabled}
      required={isRequired}
      // eslint-disable-next-line react/no-this-in-sfc
      onBlur={this.onBlur}
      {...rest}
    />
  );

  if (_featureEnabled) {
    component = (
      <ShowWhen key={key} featureEnabled={_featureEnabled}>
        {component}
      </ShowWhen>
    );
  }

  return component;
}

@connect(
  (state) => {
    const namespace = state.session.user.isPaymentlinksV2Enabled
      ? 'payment_link_v2'
      : 'payment_link';

    const paymentLinksRemindersSettings =
      findBy(state.reminders.reminders.items, 'namespace', namespace) || {};

    let withExpireRemindersCount = 0;
    let withOutExpireRemindersCount = 0;

    state.reminders.merchant_config.items.forEach((ele) => {
      if (ele.reminder_config.config_template.attr_key === 'expire_by') {
        withExpireRemindersCount += 1;

        return;
      }

      withOutExpireRemindersCount += 1;
    });

    return {
      ...state.session,
      paymentLinksRemindersSettings: {
        isEnabled: paymentLinksRemindersSettings.active,
        count: {
          withExpireRemindersCount,
          withOutExpireRemindersCount,
        },
      },
      reminders: state.reminders,
    };
  },
  {
    updatePLInReduxList,
    showNotification,
    fetchReminders,
    fetchRemindersMerchantConfigs,
    fetchPaymentLinkDetails,
    openModal,
    closeModal,
    luminateRow,
  },
)
@RTracking(() => window.rzpQ.component('CreateNewContainer'))
class CreateNewContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);

    const timestamp = Date.now();

    this.UUID = `payment_link_creation_${timestamp}`;

    defaultFieldProps.call(this, FORM_FIELDS.content); // Set the default props for fields of all tabs in Wizard

    this.state = {
      dirty: {
        reminder_enable: props.paymentLinksRemindersSettings.isEnabled ? '1' : '0', // 1 => selected
      }, // Initialize with no edits in dirty. Object is maintained to keep dirty data of each tab separately.
      _name: {
        // Object, cuz dirty is also object
        hasNoExpiry: props.user.isExpireByRequired ? '0' : '1', // 1 => selected
      },
      isLoading: true,
    };

    // recording new payments links creation UI form in hotjar
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'payment_links_v2_form_open');
      window.hj('tagRecording', ['payment_links_v2_form_open']);
    }

    trackOpenCreateForm(); // Refactor this on basis of condition if more tabs are there in the view

    const defaultPLExpiryByTime = this.props.user.plDefaultExpiryTime;

    if (defaultPLExpiryByTime) {
      const nextDate = moment(new Date()).add(defaultPLExpiryByTime, 'hours');

      this.state.dirty.expire_by = nextDate;
      this.state._name.expire_by_date = nextDate;
      this.state._name.hasNoExpiry = '0';
    }
  }

  trackPaymentLinkCreation = (event, options) => {
    return this.props.tracking.trackEvent(
      window.rzpQ.paymentLinks().interaction(event, {
        ...options,
        origin: 'dashboard',
        clone: this.isIntentDuplicate ? 1 : 0,
        uuid: this.UUID,
      }),
    );
  };

  fetchIfIntentDuplicate(invoiceId) {
    return this.props
      .fetchPaymentLinkDetails(invoiceId)
      .then((data) => {
        // Transform data from new format to old as per
        data = this.props.user.isPaymentlinksV2Enabled ? transformPLDetails_NewToOld(data) : data;

        this.isIntentDuplicate = true;
        let expire_by = data.expire_by && moment(data.expire_by * 1000);

        // If null or is before current time
        if (!expire_by || expire_by.diff(moment()) < 0) {
          expire_by = '';
        }

        const defaultValueNotes = Object.keys(data.notes).map((key) => ({
          key,
          value: data.notes[key],
        }));

        const newState = {
          dirty: {
            currency: data.currency,
            description: data.description,
            amount: i18CurrencyConversionFromMinorUnitToCommonUnit(data.amount, data.currency),
            partial_payment: Number(data.partial_payment),
            sms_notify: Number(data.sms_notify),
            email_notify: Number(data.email_notify),
            email: data.customer_details.email,
            contact: data.customer_details.contact,
            customer_name: data.customer_details.name,
            expire_by,
            notes: defaultValueNotes,
            reminder_enable: data.reminder_status && !(data.reminder_status === 'disabled'),
          },
          _name: {
            hasNoExpiry: expire_by ? '0' : '1',
            expire_by_date: expire_by ? expire_by : null,
          },
        };

        const defaultPLExpiryByTime = this.props.user.plDefaultExpiryTime;

        if (defaultPLExpiryByTime) {
          const nextDate = moment(new Date()).add(defaultPLExpiryByTime, 'hours');

          newState.dirty.expire_by = nextDate;
          newState._name.expire_by_date = nextDate;
          newState._name.hasNoExpiry = '0';
        }

        this.setState(newState);

        // state.dirty.notes of this component has different structure than defaultValue of notes component. So, after defaultValue is set, updating notes value in state.dirty
        setTimeout((_) => this.onChangeNotes(defaultValueNotes), 0);
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  }

  componentDidMount() {
    this.toggleDisableState();

    this.trackPaymentLinkCreation('pl.create.initiate');

    if (this.isIntentDuplicate) {
      this.props.tracking.trackEvent(
        window.rzpQ.paymentLinks().interaction('pl.clone.start', {
          origin: 'dashboard',
        }),
      );
      track.segment.cloneStart();
    }

    this.prepareDataForPaymentLinkCreation()
      .then(() => {
        this.setState((prevState) => ({
          isLoading: false,
          dirty: {
            ...prevState.dirty,
            reminder_enable: this.props.paymentLinksRemindersSettings.isEnabled ? '1' : '0', // 1 => selected
          },
        }));
      })
      .catch(() => {
        this.setState({
          isLoading: false,
        });
      });
  }

  componentDidUpdate() {
    this.toggleDisableState();
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

  toggleDisableState() {
    /*
     * Fields like: 'Time Payable' is required on checkbox. So, if value not selected, html marks it as ':invalid' which is tehnically valid in our case.
     * Hence, relying on is-invalid.
     * */
    // const invalidFields = document.querySelectorAll('.PaymentLinks--Create-Form :invalid');
    const invalidFields = document.querySelectorAll('.PaymentLinks--Create-Form .Input.is-invalid');
    const disableSubmit = invalidFields.length;

    if (this.state.disableSubmit !== disableSubmit) {
      this.setState({ disableSubmit });
    }
  }
  // we can return a null explicitly but want to know BU logic here before
  // eslint-disable-next-line consistent-return
  onChange = ({ target }) => {
    const stateName = target.getAttribute('data-name');
    const fieldValue = target.value;
    const fieldName = target.name;

    /* Step 0: */
    if (fieldName.indexOf('notes[') > -1) {
      return true;
    }

    const sideEffectFieldsToUpdate = {};

    /* Step 1: */
    if (fieldName === 'contact') {
      const isChecked = !!fieldValue;

      sideEffectFieldsToUpdate.sms_notify = isChecked ? '1' : '0';
      document.getElementsByName('sms_notify')[0].checked = isChecked;
    } else if (fieldName === 'email') {
      const isChecked = !!fieldValue;

      sideEffectFieldsToUpdate.email_notify = isChecked ? '1' : '0';
      document.getElementsByName('email_notify')[0].checked = isChecked;
    }

    /* Step Last */
    if (stateName) {
      this.setState((prevState) => ({
        _name: {
          ...prevState._name,
          [stateName]: fieldValue,
        },
      }));

      if (Object.keys(sideEffectFieldsToUpdate).length) {
        this.setState((prevState) => ({
          dirty: {
            ...prevState.dirty,
            ...sideEffectFieldsToUpdate,
          },
        }));
      }
    } else {
      this.setState((prevState) => ({
        dirty: {
          ...prevState.dirty,
          [fieldName]: fieldValue,
          ...sideEffectFieldsToUpdate,
        },
      }));
    }
  };

  onBlur = (event) => {
    if (!event) return;
    const fieldName = event?.target?.name;

    if (!fieldName) return;

    this.trackPaymentLinkCreation(`pl.create.${fieldName}`, {
      modified: this.isIntentDuplicate ? 1 : 0,
    });

    track.segment.fields(fieldName, !!this.isIntentDuplicate);
  };

  /* Handle change of time from time picker */
  onTimeChange(date) {
    const curDate = this.state._name.expire_by_date;

    timeCalculator(date, curDate, this.updateDate);
  }

  /* Handle change of time from time picker */
  onDateChange(date) {
    const curExpiryByTime = this.state.dirty.expire_by;

    dateCalculator(date, curExpiryByTime, this.updateDate);
  }

  /* Handle change of date from calendar */
  updateDate = (ts) => {
    const newDate = moment(ts);

    this.setState((prevState) => ({
      // Update expire_by
      dirty: {
        ...prevState.dirty,
        expire_by: newDate,
      },
      // Update expire_by_date
      _name: {
        ...prevState._name,
        expire_by_date: newDate,
      },
    }));
  };

  /* Handle change of notes */
  onChangeNotes = (pairs) => {
    if (this.props.user.isCustomNotesDropdownEnabled) {
      this.onChange(pairs);

      return;
    }

    const notes = onChangeNotes(pairs);
    /* If refering from prevState this is breaking UI
    on prod as prevState.dirty is coming null or
    undefined , reverting back to previous code. */
    this.setState({
      dirty: {
        // eslint-disable-next-line react/no-access-state-in-setstate
        ...this.state.dirty,
        notes,
      },
    });
  };

  onAddNewNote = () => {
    this.trackPaymentLinkCreation('pl.create.notes');
  };

  onCreate = () => {
    const clone = this.isIntentDuplicate ? 1 : 0;
    if (this.isIntentDuplicate) {
      trackSaveDuplicatePaymentLink();
    }

    const IS_MODAL_VIEW = this.props.onClose;
    const { tracking, user } = this.props;

    this.setState({
      parentFormLock: true,
    });

    let notificationMSG = 'Payment link created successfully.';
    const notifyMedium = [];

    if (this.state.dirty.sms_notify && !user.isPlV2DisableAllSmsEnabled) {
      notifyMedium.push('SMS');
    }

    if (this.state.dirty.email_notify && !user.isPlV2DisableAllEmailEnabled) {
      notifyMedium.push('Email');
    }

    if (notifyMedium.length > 0) {
      notificationMSG += ` Sending via ${notifyMedium.join(' and ')}`;
    }

    const reqPayload = { ...this.state.dirty };

    /* Removing unrequired fields */

    if (this.state._name.hasNoExpiry == '1') {
      delete reqPayload.expire_by;
    }

    if (this.state.dirty.notes && !Object.keys(this.state.dirty.notes).length) {
      delete reqPayload.notes;
    }

    if (!this.state.dirty.receipt) {
      delete reqPayload.receipt;
    }

    if (reqPayload.reminder_enable === '1') {
      reqPayload.reminder_enable = true;
    } else {
      delete reqPayload.reminder_enable;
    }

    if (user.isCustomNotesDropdownEnabled) {
      const { type } = getCustomNotesOptions();

      reqPayload.notes = {
        [type]: reqPayload.notes,
      };
    }

    const extraFields = user.paymentLinkCreationFormExtraFields;

    extraFields.forEach((field) => {
      if (field.addAt.as === 'prefix') {
        reqPayload[field.addAt.fieldName] = `${reqPayload[field.name]} : ${
          reqPayload[field.addAt.fieldName]
        }`;

        delete reqPayload[field.name];
      }
    });

    this.trackPaymentLinkCreation('pl.create.issue');
    track.segment.paymentLinkIssue(clone);
    return FORM_FIELDS.onCreate(reqPayload)
      .then((resp) => {
        this.setState({
          parentFormLock: false,
        });

        if (resp.data) {
          track.segment.paymentLinkCreate();
          this.props.showNotification({
            type: 'success',
            message: notificationMSG,
            onCloseClick: () => {
              this.trackPaymentLinkCreation('pl.create.success', {
                close: 1,
              });
              track.segment.successToast(clone, 1);
            },
            onTimeOutClose: () => {
              this.trackPaymentLinkCreation('pl.create.success', {
                close: 0,
              });
              track.segment.successToast(clone, 0);
            },
          });

          tracking.trackEvent(
            window.rzpQ.onbr().success('dash.pl_action', {
              action: 'PL_Creation_Successful',
            }),
          );

          if (this.isIntentDuplicate) {
            this.props.tracking.trackEvent(
              window.rzpQ.paymentLinks().interaction('pl.clone.complete', {
                origin: 'dashboard',
              }),
            );
            track.segment.cloneComplete();
          }

          track.segment.form.success(resp, !!this.isIntentDuplicate);

          const entityId = resp.data.id;

          if (IS_MODAL_VIEW) {
            this.props.updatePLInReduxList(resp, true);
            this.props.luminateRow(entityId); // Make it promise based

            setTimeout(this.props.onClose, 50);
          } else {
            const redirectUrl = `/paymentlinks/${entityId}`;

            this.props.history.push(redirectUrl);
          }
        } else {
          tracking.trackEvent(
            window.rzpQ.onbr().success('dash.pl_action', {
              action: 'PL_Creation_Failed',
            }),
          );

          throw new Error(resp.errors);
        }
      })
      .catch(({ errors }) => {
        let err = errors;
        if (Array.isArray(err)) {
          err = [];

          errors.forEach((e) => {
            if (e && e.toLowerCase().indexOf('status code') === -1) {
              err.push(e);

              this.trackPaymentLinkCreation('pl.create.fail', {
                response: e,
              });
              track.segment.paymentLinkFail(clone, e);
            }
          });

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some network error has occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });

        track.segment.form.fail(errors, !!this.isIntentDuplicate);

        this.setState({
          parentFormLock: false,
        });
      });
  };

  getFormFields(fields = FORM_FIELDS.content) {
    const formFields = fields.map((f, i) => {
      if (Array.isArray(f)) {
        return (
          <Input.Group key={i} disabled={this.state.parentFormLock}>
            {f.map(WizardFields, this)}
          </Input.Group>
        );
      } else if (f.hasOwnProperty('inlineFields') && Array.isArray(f.inlineFields)) {
        let label = f.label;
        if (typeof label === 'function') {
          label = f.label(this);
        }

        let className = f.className;
        if (typeof className === 'function') {
          className = f.className(this);
        }

        let isRequired = f.required;
        if (typeof isRequired === 'function') {
          isRequired = isRequired(this);
        }

        return (
          <Input.Group
            key={i}
            class={classList('InputGroup--inline', className)}
            label={label}
            disabled={this.state.parentFormLock}
            required={!!isRequired}
          >
            <div class="Input-content">{f.inlineFields.map(WizardFields, this)}</div>
          </Input.Group>
        );
      }

      const options = f.options;
      if (typeof options === 'function') {
        f.options = options(this);
      }

      const label = f.label;
      if (typeof label === 'function') {
        f.label = f.label(this);
      }

      const placeholder = f.placeholder;
      if (typeof placeholder === 'function') {
        f.placeholder = f.placeholder(this);
      }

      return WizardFields.call(this, f);
    });

    if (this.props.user.paymentLinkCreationFormExtraFields.length) {
      const extraFields = this.props.user.paymentLinkCreationFormExtraFields.map((meta) => {
        const newField = generateField(meta);

        return WizardFields.call(this, newField);
      });

      formFields.push(extraFields);
    }

    return formFields;
  }

  onFormAbruptClose = () => {
    const clone = this.isIntentDuplicate ? 1 : 0;
    const curDirty = this.state.dirty;
    const dirtyFields = Object.keys(curDirty);

    let formUnsaved = false;

    let count = 0;
    const dirtyFieldCheck = (data) => {
      if (typeof curDirty[data] !== 'undefined') {
        count++;
      }
      /*
       * If >2 fields are touched in the form, close-confirmation is asked before closing
       * */
      const closeConfirmationCheck = () => {
        formUnsaved = true;
        return false;
      };
      return count > 2 ? closeConfirmationCheck() : null;
    };
    dirtyFields.forEach(dirtyFieldCheck);
    this.trackPaymentLinkCreation('pl.create.cancel');
    track.segment.paymentLinkCancel(clone);
    if (this.isIntentDuplicate) {
      this.props.tracking.trackEvent(
        window.rzpQ.paymentLinks().interaction('pl.clone.close', {
          origin: 'dashboard',
        }),
      );
      track.segment.cloneClose();
    }

    if (formUnsaved) {
      this.context
        .confirm({
          header: 'Do you want to close this form?',
          message: 'Changes that you made will be discarded.',
          affirmativeLabel: 'Leave',
          abortLabel: 'Stay',
          action: () => {
            this.props.onClose();

            this.trackPaymentLinkCreation('pl.create.close');

            closePaymentLinkForm('Confirmed');
          },
        })
        .catch(() => {});
    } else {
      this.props.onClose();
    }
  };

  render() {
    // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
    const IS_MODAL_VIEW = this.props.onClose;
    const formFields = this.getFormFields();

    const content = (
      <CreateWizard
        ref={(refId) => (this.wizardContent = refId)}
        submitForm={this.submitForm}
        history={this.props.history}
        mode={this.props.mode}
        content={formFields}
        onChange={this.onChange}
        onCreate={this.onCreate}
        isModalView={IS_MODAL_VIEW}
        onFormAbruptClose={(e) => {
          this.onFormAbruptClose(e);
          track.segment.form.close('cancel');
          closePaymentLinkForm('Cancel');
        }}
        disableSubmit={this.state.disableSubmit}
        isLoading={this.state.isLoading}
      />
    );

    return IS_MODAL_VIEW ? (
      <Modal
        class={classList('PaymentLinks', content && 'animate-down')}
        onClose={(e) => {
          this.onFormAbruptClose(e);
          track.segment.form.close('close');
          closePaymentLinkForm('Cross');
        }}
      >
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{content}</div>
    );
  }
}

class CreateWizard extends React.Component {
  closeModal = () => {
    this.props.onClose();
  };

  render() {
    const { disableSubmit, mode, isLoading } = this.props;

    return (
      <div class="PaymentLinks--Create Wizard">
        <main class="form-container">
          <main-title class="main-title">Create {FORM_FIELDS.title}</main-title>

          {/* ALERTS */}
          {mode === 'test' && (
            <Alert.Warning>
              You are creating the link in <b>Test Mode</b>. So, only test payments can be made for
              it.
            </Alert.Warning>
          )}

          {isLoading ? (
            <div className="page-center">
              <Spinner />
            </div>
          ) : (
            /* FORM */
            <Form
              class="PaymentLinks--Create-Form"
              onChange={this.props.onChange}
              layout="tabular"
              key={FORM_FIELDS.title}
            >
              {this.props.content}
            </Form>
          )}
        </main>

        {!isLoading && (
          /* FORM FOOTER */
          <footer>
            {/* Action Button 1 */}
            {this.props.isModalView && (
              <Button onClick={this.props.onFormAbruptClose}>Cancel</Button>
            )}

            {/* Action Button 2 */}
            <AsyncBtn.Primary
              onClick={this.props.onCreate}
              pendingState="Creating..."
              disabled={disableSubmit}
            >
              Create {FORM_FIELDS.title}
            </AsyncBtn.Primary>
          </footer>
        )}
      </div>
    );
  }
}

export default withRouter(CreateNewContainer);
