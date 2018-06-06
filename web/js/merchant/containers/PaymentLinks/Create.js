import { connect } from 'react-redux';
import { merchantFetch } from 'rzp/utils/ajax';
import { withRouter } from 'react-router-dom';
import { classList } from 'common/util';
import { activationDuration } from 'common/data';

import Form from 'component/Form';
import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';

import Alert from 'component/Alert';
import { Modal, ModalContent } from 'component/Modal';
import { ModalAsideNav } from 'component/Wizard';
import PaymentLinksFormFields from './Links/Create';
import ReusableLinksFormFields from './ReusableLinks/Create';

import { createPaymentLink } from './Links/model';
import { createReusableLink } from './ReusableLinks/model';
import ShowWhen from 'merchant/components/ShowWhen';

import { showNotification } from 'rzp/modules/notifications';

const FORM_TABS = [
  {
    title: 'Payment Link',
    desc: 'The link gets expired automatically once its paid.',
    url: '/paymentlinks/new',
    content: [...PaymentLinksFormFields],
    onCreate: createPaymentLink,
  },
  {
    title: 'Reusable Link',
    desc: 'Accept payments multiple times on a single payment link.',
    url: '/paymentlinks/reusable/new',
    content: [...ReusableLinksFormFields],
    onCreate: createReusableLink,
  },
];

/* Order as per FORM_TABS */
const PAYMENT_LINK = 0;
const REUSABLE_PAYMENT_LINK = 1;

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
    f.onChange = self.onDateChange.bind(self);
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
  const activeTabIndx = String(this.state.activeTab);

  if (rest.name) {
    key = rest.name;
    defaultValue = this.state.dirty[activeTabIndx][key]; // Form state is stored in dirty

    key === 'expire_by' && defaultValue;
  } else if (_name) {
    defaultValue =
      this.state._name[activeTabIndx] && this.state._name[activeTabIndx][_name];
    key = _name;
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
@connect(state => state.session, { showNotification })
export default class CreateNewContainer extends React.Component {
  constructor(props) {
    super(props);

    let intent = PAYMENT_LINK;
    const self = this;

    FORM_TABS.forEach((TAB, indx) => {
      if (props.location.pathname === TAB.url) {
        intent = indx;
      }

      defaultFieldProps.call(this, TAB.content); // Set the default props for fields of all tabs in Wizard
      TAB.onCreate = TAB.onCreate.bind(self);
    });

    this.state = {
      activeTab: intent,
      dirty: {
        [PAYMENT_LINK]: {},
        [REUSABLE_PAYMENT_LINK]: {},
      }, // Initialize with no edits in dirty. Object is maintained to keep dirty data of each tab separately.
      _name: {
        // Object, cuz dirty is also object
        [PAYMENT_LINK]: {
          expiry: '1', // 1 => selected
        },
        [REUSABLE_PAYMENT_LINK]: {
          noLimit: '1', // 1 => selected
          expiry: '1', // 1 => selected
        },
      },
    };
  }

  saveDirtyState = e => {
    console.log(
      'Some changes are unsaved. Check ref.state for content.',
      this.wizardContent && this.wizardContent.state
    );
  };

  onChange = ({ target }) => {
    let stateName = target.getAttribute('data-name');
    let fieldValue = target.value;
    let fieldName = target.name;

    /* Step 0: */
    if (fieldName.indexOf('notes[') > -1) {
      return true;
    }

    let sideEffectFieldsToUpdate = {};

    const activeTab = this.state.activeTab;
    const activeTabIndx = String(this.state.activeTab);
    const curDirty = this.state.dirty[activeTabIndx];

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

      _newName[activeTabIndx][stateName] = fieldValue;
      this.setState({ _name: _newName });

      if (Object.keys(sideEffectFieldsToUpdate).length) {
        const newDirty = { ...this.state.dirty };

        newDirty[activeTabIndx] = {
          ...newDirty[activeTabIndx],
          ...sideEffectFieldsToUpdate,
        };

        this.setState({
          dirty: newDirty,
        });
      }
    } else {
      const newDirty = { ...this.state.dirty };

      newDirty[activeTabIndx] = {
        ...newDirty[activeTabIndx],
        [fieldName]: fieldValue,
        ...sideEffectFieldsToUpdate,
      };

      this.setState({
        dirty: newDirty,
      });
    }
  };

  onDateChange(date) {
    if (date && date.target) {
      // Check if date is not of event type
      return;
    }

    const activeTabIndx = String(this.state.activeTab);
    const newDirty = { ...this.state.dirty };

    let expiryTime =
      newDirty[activeTabIndx] && newDirty[activeTabIndx].expire_by; // If expiry_by already set by user

    if (date) {
      if (expiryTime) {
        const offsetExpiryTime =
          expiryTime -
          moment(expiryTime)
            .startOf('day')
            .valueOf();
        expiryTime = date.valueOf() + offsetExpiryTime;
      } else {
        expiryTime = date.endOf('day').valueOf();
      }
    } else {
      // Remove time field when date field is unset
      expiryTime = null;
    }
    newDirty[activeTabIndx] = {
      ...newDirty[activeTabIndx],
      expire_by: expiryTime,
    };

    const _newName = { ...this.state._name };

    _newName[activeTabIndx]['expire_by_date'] = date;
    this.setState({
      dirty: newDirty,
      _name: _newName,
    });

    if (expiryTime) {
      setTimeout(() => {
        // document.getElementsByName('expire_by')[0].value = expiryTime;
        document.getElementsByName('expire_by')[0].focus();
      }, 100);
    }
  }

  onChangeNotes = pairs => {
    const newDirty = { ...this.state.dirty };
    const activeTabIndx = String(this.state.activeTab);

    const notes = {};

    pairs.forEach(p => {
      if (p.key || p.value) {
        notes[p.key] = p.value;
      }
    });

    newDirty[activeTabIndx] = {
      ...newDirty[activeTabIndx],
      notes: notes,
    };

    this.setState({
      dirty: newDirty,
    });
  };

  changeTab = ({ target }) => {
    const activeTabIndx = parseInt(target.getAttribute('data-index'));

    this.setState({
      activeTab: activeTabIndx,
    });

    this.props.history.replace(FORM_TABS[activeTabIndx].url);
  };

  onCreate = () => {
    const activeTabIndx = String(this.state.activeTab);

    this.setState({
      parentFormLock: true,
    });

    const promise = FORM_TABS[activeTabIndx].onCreate();

    if (activeTabIndx == PAYMENT_LINK) {
      let notificationMSG = 'Payment link created successfully.',
        notifyMedium = [];

      if (this.state.dirty[activeTabIndx].sms_notify) {
        notifyMedium.push('SMS');
      }

      if (this.state.dirty[activeTabIndx].email_notify) {
        notifyMedium.push('Email');
      }

      if (notifyMedium.length > 0) {
        notificationMSG += ' Sending via ' + notifyMedium.join(' and ');
      }

      return promise
        .then(resp => {
          if (resp.data) {
            this.props.showNotification({
              type: 'success',
              message: notificationMSG,
            });

            this.props.history.push('/paymentlinks/' + resp.data.id);
          }

          this.setState({
            parentFormLock: false,
          });
        })
        .catch(err => {
          this.props.showNotification({
            type: 'error',
            message: err.errors,
          });

          this.setState({
            parentFormLock: false,
          });
        });
    }

    // In case of other tabs, simply return promise;
    return promise.then(resp => {
      this.setState({
        parentFormLock: false,
      });
    });
  };

  getFormFields() {
    const fields = FORM_TABS[this.state.activeTab].content;

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

  render() {
    // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
    const IS_MODAL = this.props.onClose;
    const { activeTab } = this.state;

    const formFields = this.getFormFields();

    const content = (
      <CreateWizard
        ref={refId => (this.wizardContent = refId)}
        activeTab={activeTab}
        submitForm={this.submitForm}
        history={this.props.history}
        mode={this.props.mode}
        content={formFields}
        changeTab={this.changeTab}
        onChange={this.onChange}
        onCreate={this.onCreate}
      />
    );

    return IS_MODAL ? (
      <Modal
        class={classList('PaymentLinks', content && 'animate-down')}
        onClose={this.props.onClose}
        onCloseCB={this.saveDirtyState}
      >
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{content}</div>
    );
  }
}

class CreateWizard extends React.Component {
  render() {
    const { activeTab } = this.props;

    return (
      <div class="PaymentLinks--Create Wizard">
        <ModalAsideNav
          title="Create Link"
          tabs={FORM_TABS}
          tabClickHandler={this.props.changeTab}
          activeTab={activeTab}
        />

        <main class="form-container">
          {/* ACTIVE TAB TITLE */}
          <main-title class="main-title">
            {FORM_TABS[activeTab].title}
          </main-title>

          {/* ALERTS */}
          {this.props.mode === 'test' && (
            <Alert.Warning>
              You are creating the link in <b>Test Mode</b>. So, only test
              payments can be made for this link.
            </Alert.Warning>
          )}

          {/* FORM */}
          <Form
            onChange={this.props.onChange}
            layout="tabular"
            key={FORM_TABS[activeTab].title}
          >
            {this.props.content}
          </Form>
        </main>

        {/* FORM FOOTER */}
        <footer>
          {/* Action Button 1 */}
          <Button onClick={this.closeModal}>Cancel</Button>

          {/* Action Button 2 */}
          <AsyncBtn.Primary
            onClick={this.props.onCreate}
            pendingState={'Creating...'}
          >
            Create {FORM_TABS[activeTab].title}
          </AsyncBtn.Primary>
        </footer>
      </div>
    );
  }
}
