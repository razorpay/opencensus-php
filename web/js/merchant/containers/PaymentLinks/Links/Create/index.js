import { connect } from 'react-redux';
import { merchantFetch } from 'rzp/utils/ajax';
import { withRouter } from 'react-router-dom';
import { classList } from 'common/util';

import ShowWhen from 'merchant/components/ShowWhen';
import Alert from 'component/Alert';
import Form from 'component/Form';
import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';

import { Modal, ModalContent } from 'component/Modal';
import { ModalAsideNav } from 'component/Wizard';
import PaymentLinkFormFields from './Fields';

import moment from 'moment';
import { createPaymentLink } from '../model';
import { dateCalculator, timeCalculator } from 'component/Input/Calendar';
import { onChangeNotes } from 'component/Input/PairList';

import { closeModal, openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { updatePLInReduxList } from 'merchant/modules/invoices/list';
import { luminateRow } from 'merchant/modules/app';

import { trackOpenCreateForm, closePaymentLinkForm } from '../ga';

const FORM_FIELDS = {
  title: 'Payment Link',
  desc: 'The link gets expired automatically once its paid.',
  url: '/paymentlinks/new',
  content: [...PaymentLinkFormFields],
  onCreate: createPaymentLink,
};

function defaultFieldProps(f) {
  const self = this;

  if (Array.isArray(f)) {
    return f.forEach(defaultFieldProps.bind(self));
  } else if (
    f.hasOwnProperty('inlineFields') &&
    Array.isArray(f.inlineFields)
  ) {
    return f.inlineFields.forEach(defaultFieldProps.bind(self));
  }

  if (!f._cmp) {
    f._cmp = Input;
  }

  if (f.name === 'notes') {
    f.onChange = self.onChangeNotes;
  }

  if (f._name === 'expire_by_date') {
    f.onChange = self.onDateChange.bind(self);
  }
  if (f.name === 'expire_by') {
    f.onChange = self.onTimeChange.bind(self);
  }
}

function WizardFields(field) {
  let {
    _cmp: Component,
    _name,
    _when,
    _featureEnabled,
    _autoRenderImpure,
    _disabledWhen,
    ...rest
  } = field;

  if (_when && !_when(this)) {
    return null;
  }

  let defaultValue, key;

  if (rest.name) {
    key = rest.name;
    defaultValue = this.state.dirty[key]; // Form state is stored in dirty

    key === 'expire_by' && defaultValue;
  } else if (_name) {
    defaultValue = this.state._name[_name];
    key = _name;
  }

  if (rest.description && typeof rest.description === 'function') {
    rest.description = rest.description(this);
  }

  let isComponentDisabled;
  if (this.state.parentFormLock || (_disabledWhen && _disabledWhen(this))) {
    isComponentDisabled = true;
  }

  let component = (
    <Component
      key={key}
      data-name={_name}
      defaultValue={defaultValue}
      autoRender={_autoRenderImpure}
      disabled={isComponentDisabled}
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

@withRouter
@connect(state => state.session, {
  updatePLInReduxList,
  showNotification,
  openModal,
  closeModal,
  luminateRow,
})
export default class CreateNewContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);

    const self = this;

    defaultFieldProps.call(this, FORM_FIELDS.content); // Set the default props for fields of all tabs in Wizard

    this.state = {
      dirty: {}, // Initialize with no edits in dirty. Object is maintained to keep dirty data of each tab separately.
      _name: {
        // Object, cuz dirty is also object
        hasNoExpiry: '1', // 1 => selected
      },
    };

    // recording new payments links creation UI form in hotjar
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'payment_links_v2_form_open');
      window.hj('tagRecording', ['payment_links_v2_form_open']);
    }

    trackOpenCreateForm(); // Refactor this on basis of condition if more tabs are there in the view
  }

  componentDidMount() {
    this.toggleDisableState();
  }

  componentDidUpdate() {
    this.toggleDisableState();
  }

  toggleDisableState() {
    /*
    * Fields like: 'Time Payable' is required on checkbox. So, if value not selected, html marks it as ':invalid' which is tehnically valid in our case.
    * Hence, relying on is-invalid.
    * */
    // const invalidFields = document.querySelectorAll('.PaymentLinks--Create-Form :invalid');
    const invalidFields = document.querySelectorAll(
      '.PaymentLinks--Create-Form .Input.is-invalid'
    );
    const disableSubmit = invalidFields.length;

    if (this.state.disableSubmit !== disableSubmit) {
      this.setState({ disableSubmit });
    }
  }

  onChange = ({ target }) => {
    let stateName = target.getAttribute('data-name');
    let fieldValue = target.value;
    let fieldName = target.name;

    /* Step 0: */
    if (fieldName.indexOf('notes[') > -1) {
      return true;
    }

    let sideEffectFieldsToUpdate = {};

    const curDirty = this.state.dirty;

    /* Step 1: */
    if (fieldName === 'contact') {
      const isChecked = !!fieldValue;

      sideEffectFieldsToUpdate['sms_notify'] = isChecked ? '1' : '0';
      document.getElementsByName('sms_notify')[0].checked = isChecked;
    } else if (fieldName === 'email') {
      const isChecked = !!fieldValue;

      sideEffectFieldsToUpdate['email_notify'] = isChecked ? '1' : '0';
      document.getElementsByName('email_notify')[0].checked = isChecked;
    }

    /* Step Last */
    if (stateName) {
      const _newName = { ...this.state._name };

      this.setState({
        _name: {
          ...this.state._name,
          [stateName]: fieldValue,
        },
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
  updateDate = ts => {
    const newDate = moment(ts);

    this.setState({
      // Update expire_by
      dirty: {
        ...this.state.dirty,
        expire_by: newDate,
      },
      // Update expire_by_date
      _name: {
        ...this.state._name,
        expire_by_date: newDate,
      },
    });
  };

  /* Handle change of notes */
  onChangeNotes = pairs => {
    const newDirty = { ...this.state.dirty };
    const notes = onChangeNotes(pairs);

    if (!Object.keys(notes).length) {
      return;
    }

    this.setState({
      dirty: {
        ...this.state.dirty,
        notes: notes,
      },
    });
  };

  openRPLShareView = (id, shortUrl, title, description) => {
    this.props.openModal({
      size: 'medium',
      component: (
        <RPLShareView
          handleClose={this.props.closeModal}
          handleAction={sendLink.bind(null, id)}
          isNew={true}
          showNotification={this.props.showNotification}
          url={shortUrl}
          title={title}
          description={description}
        />
      ),
    });
  };

  onCreate = () => {
    const IS_MODAL_VIEW = this.props.onClose;

    this.setState({
      parentFormLock: true,
    });

    let notificationMSG = 'Payment link created successfully.',
      notifyMedium = [];

    if (this.state.dirty.sms_notify) {
      notifyMedium.push('SMS');
    }

    if (this.state.dirty.email_notify) {
      notifyMedium.push('Email');
    }

    if (notifyMedium.length > 0) {
      notificationMSG += ' Sending via ' + notifyMedium.join(' and ');
    }

    const reqPayload = { ...this.state.dirty };
    if (this.state._name.hasNoExpiry == '1') {
      delete reqPayload.expire_by;
    }

    return FORM_FIELDS.onCreate(reqPayload)
      .then(resp => {
        this.setState({
          parentFormLock: false,
        });

        if (resp.data) {
          this.props.showNotification({
            type: 'success',
            message: notificationMSG,
          });

          const entityId = resp.data.id;

          if (IS_MODAL_VIEW) {
            this.props.updatePLInReduxList(resp, true);
            this.props.luminateRow(entityId); // Make it promise based

            setTimeout(this.props.onClose, 50);
          } else {
            const redirectUrl = '/paymentlinks/' + entityId;

            this.props.history.push(redirectUrl);
          }
        } else {
          throw new Error(resp.errors);
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          errors.length &&
            errors.forEach(e => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some Network error occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });

        this.setState({
          parentFormLock: false,
        });
      });
  };

  getFormFields() {
    const fields = FORM_FIELDS.content;

    return fields.map((f, i) => {
      if (Array.isArray(f)) {
        return (
          <Input.Group key={i} disabled={this.state.parentFormLock}>
            {f.map(WizardFields, this)}
          </Input.Group>
        );
      } else if (
        f.hasOwnProperty('inlineFields') &&
        Array.isArray(f.inlineFields)
      ) {
        return (
          <Input.Group
            key={i}
            class={classList('InputGroup--inline', f.className)}
            label={f.label}
            disabled={this.state.parentFormLock}
          >
            <div class="Input-content">
              {f.inlineFields.map(WizardFields, this)}
            </div>
          </Input.Group>
        );
      }

      return WizardFields.call(this, f);
    });
  }

  onFormAbruptClose = e => {
    const curDirty = this.state.dirty;
    const formTabs = Object.keys(curDirty);

    let formUnsaved = false;

    if (formTabs.length) {
      formTabs.forEach(tabId => {
        const tab = this.state.dirty[tabId];

        /*
        * If >2 fields are touched in any one form, close-confirmation is asked before closing
        * */
        if (Object.keys(tab).length > 2) {
          formUnsaved = true;

          return false;
        }
      });
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
        ref={refId => (this.wizardContent = refId)}
        submitForm={this.submitForm}
        history={this.props.history}
        mode={this.props.mode}
        content={formFields}
        onChange={this.onChange}
        onCreate={this.onCreate}
        isModalView={IS_MODAL_VIEW}
        onFormAbruptClose={e => {
          this.onFormAbruptClose(e);
          closePaymentLinkForm('Cancel');
        }}
        disableSubmit={this.state.disableSubmit}
      />
    );

    return IS_MODAL_VIEW ? (
      <Modal
        class={classList('PaymentLinks', content && 'animate-down')}
        onClose={e => {
          this.onFormAbruptClose(e);
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
  closeModal = e => {
    this.props.onClose();
  };

  render() {
    const { disableSubmit, mode } = this.props;

    return (
      <div class="PaymentLinks--Create Wizard">
        <main class="form-container">
          <main-title class="main-title">CREATE {FORM_FIELDS.title}</main-title>

          {/* ALERTS */}
          {mode === 'test' && (
            <Alert.Warning>
              You are creating the link in <b>Test Mode</b>. So, only test
              payments can be made for this link.
            </Alert.Warning>
          )}

          {/* FORM */}
          <Form
            class="PaymentLinks--Create-Form"
            onChange={this.props.onChange}
            layout="tabular"
            key={FORM_FIELDS.title}
          >
            {this.props.content}
          </Form>
        </main>

        {/* FORM FOOTER */}
        <footer>
          {/* Action Button 1 */}
          {this.props.isModalView && (
            <Button onClick={this.props.onFormAbruptClose}>Cancel</Button>
          )}

          {/* Action Button 2 */}
          <AsyncBtn.Primary
            onClick={this.props.onCreate}
            pendingState={'Creating...'}
            disabled={disableSubmit}
          >
            Create {FORM_FIELDS.title}
          </AsyncBtn.Primary>
        </footer>
      </div>
    );
  }
}
