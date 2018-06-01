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

import { updateSession } from 'merchant/modules/session';

import PLFormFields from './PaymentLinks';
import RPLFormFields from './ReusableLinks';

const FORM_TABS = [
  {
    title: 'Payment Link',
    desc: 'The link gets expired automatically once its paid.',
    url: '/paymentlinks/new',
    content: PLFormFields,
  },
  {
    title: 'Reusable Link',
    desc: 'Accept payments multiple times on a single payment link.',
    url: '/paymentlinks/reusable/new',
    content: RPLFormFields,
  },
];

function defaultFieldProps(f) {
  const self = this;

  if (Array.isArray(f)) {
    return f.forEach(defaultFieldProps.bind(self));
  }

  if (!f._cmp) {
    f._cmp = Input;
  }
}
function WizardFields(field) {
  let {
    _cmp: Component,
    _name,
    _when,
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
    defaultValue =
      this.state.dirty[this.state.activeTab] &&
      this.state.dirty[this.state.activeTab][key]; // Form state is stored in dirty
  } else if (_name) {
    defaultValue = this.state[_name];
    key = _name;
  }

  let isComponentDisabled;
  if (_disabledWhen && _disabledWhen(this)) {
    isComponentDisabled = true;
  }

  return (
    <Component
      key={key}
      data-name={_name}
      defaultValue={defaultValue}
      autoRender={_autoRenderImpure}
      disabled={isComponentDisabled}
      {...rest}
    />
  );
}

@withRouter
@connect(state => state.session)
export default class CreateNewContainer extends React.Component {
  constructor(props) {
    super(props);

    let intent = 0; // intent = 0 => Payment Link (Order as per FORM_TABS)

    FORM_TABS.forEach((TAB, indx) => {
      if (props.location.pathname === TAB.url) {
        intent = indx;
      }

      defaultFieldProps.call(this, TAB.content); // Set the default props for fields of all tabs in Wizard
    });

    this.state = {
      activeTab: intent,
      dirty: {}, // Initialize with no edits in dirty. Object is maintained to keep dirty data of each tab separately.
      _name: {
        // Object, cuz dirty is also object
        '0': {
          expiry: '0', // 0 is unselected
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

    const activeTab = this.state.activeTab;
    const activeTabIndx = String(this.state.activeTab);
    const curDirty = this.state.dirty[activeTabIndx];

    if (stateName) {
      const _newName = { ...this.state._name };

      _newName[activeTabIndx][stateName] = fieldValue;
      this.setState({ _name: _newName });
    } else {
      const newDirty = [...this.state.dirty];

      newDirty[activeTabIndx] = {
        ...curDirty[activeTabIndx],
        [fieldName]: fieldValue,
      };

      this.setState({
        dirty: newDirty,
      });
    }
  };

  changeTab = ({ target }) => {
    const tabId = parseInt(target.getAttribute('data-index'));

    this.setState({
      activeTab: tabId,
    });

    this.props.history.replace(FORM_TABS[tabId].url);
  };

  render() {
    // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
    const IS_MODAL = this.props.onClose;
    const { activeTab } = this.state;

    const formFields = FORM_TABS[activeTab].content.map((field, i) => {
      if (Array.isArray(field)) {
        return (
          <Input.Group key={i}>{field.map(WizardFields, this)}</Input.Group>
        );
      }

      return WizardFields.call(this, field);
    });

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
      <div class="PaymentLinks--Create Wizard Wizard--broad">
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
          <Form onChange={this.props.onChange} layout="tabular">
            {this.props.content}
          </Form>
        </main>

        {/* FORM FOOTER */}
        <footer>
          {/* Action Button 1 */}
          <Button onClick={this.closeModal}>Cancel</Button>

          {/* Action Button 2 */}
          <Button.Primary iconAfter="chevron-right" onClick={this.next}>
            <span class="device--desktop">
              Create {FORM_TABS[activeTab].title}
            </span>
          </Button.Primary>
        </footer>
      </div>
    );
  }
}
