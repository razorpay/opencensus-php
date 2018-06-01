import { connect } from 'react-redux';
import { merchantFetch } from 'rzp/utils/ajax';
import { withRouter } from 'react-router-dom';
import { classList } from 'common/util';
import { activationDuration } from 'common/data';

import Form from 'component/Form';
import Alert from 'component/Alert';
import { Modal, ModalContent } from 'component/Modal';
import { LinkCard } from 'component/Cards';
import { ModalAsideNav } from 'component/Wizard';

import { updateSession } from 'merchant/modules/session';

import Button, { AsyncBtn } from 'component/Button';

const FORM_TABS = [
  {
    title: 'Payment Link',
    desc: 'The link gets expired automatically once its paid.',
    url: '/paymentlinks/new',
    fields: [],
  },
  {
    title: 'Reusable Link',
    desc: 'Accept payments multiple times on a single payment link.',
    url: '/paymentlinks/reusable/new',
    fields: [],
  },
];

/*
 * ActivationContainer is used in:
 * 1. '/activation' route for Activation form for merchant, and
 * 2. Marketplace > Accounts for linked account (AccoundDetails)
 *
 * @props {onClose, Function, optional}. Without this modal would not be opened. Also, this would be used to close the modal
 * @props {accountId, String, optional}. Needed if the ActivationWizard is opened for Linked Account
 * */

@withRouter
@connect(state => state.session)
export default class ActivationContainer extends React.Component {
  constructor(props) {
    super(props);

    let intent = 0; // intent = 0 => Payment Link (Order as per FORM_TABS)

    FORM_TABS.forEach((t, indx) => {
      if (props.location.pathname === t.url) {
        intent = indx;
      }
    });

    this.state = {
      intent,
    };
  }

  componentWillMount() {
    this.fetchActivationDetails(this.props.accountId); // accountId = undefined if not present
  }

  fetchActivationDetails(accountId) {
    merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      accountId,
    }).then(data => {
      this.setState({
        data: data.data,
      });
    });
  }

  saveDirtyState = e => {
    console.log(
      'Some changes are unsaved. Check ref.state for content.',
      this.wizardContent && this.wizardContent.state
    );
  };

  render() {
    // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
    const IS_MODAL = this.props.onClose;

    const content = (
      <CreateWizard
        ref={refId => (this.wizardContent = refId)}
        selectedTab={this.state.intent}
        submitForm={this.submitForm}
        history={this.props.history}
        mode={this.props.mode}
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
  state = {
    activeTab: this.props.selectedTab || 0,
  };

  changeTab = ({ target }) => {
    const tabId = parseInt(target.getAttribute('data-index'));

    this.setState({
      activeTab: tabId,
    });

    this.props.history.replace(FORM_TABS[tabId].url);
  };

  render() {
    console.log(FORM_TABS[this.state.activeTab]);
    return (
      <div class="PaymentLinks--Create Wizard Wizard--broad">
        <ModalAsideNav
          title="Create Link"
          tabs={FORM_TABS}
          tabClickHandler={this.changeTab}
          activeTab={this.state.activeTab}
        />

        <main class="form-container">
          {/* ACTIVE TAB TITLE */}
          <main-title class="main-title">
            Create {FORM_TABS[this.state.activeTab].title}
          </main-title>

          {/* ALERTS */}
          {this.props.mode === 'test' && (
            <Alert.Warning>
              You are creating the link in <b>Test Mode</b>. So, only test
              payments can be made for this link.
            </Alert.Warning>
          )}

          {/* FORM */}
          <Form onChange={this.onChange} layout="tabular" />
        </main>

        {/* FORM FOOTER */}
        <footer>
          {/* Action Button 1 */}
          <Button onClick={this.closeModal}>Cancel</Button>

          {/* Action Button 2 */}
          <Button.Primary iconAfter="chevron-right" onClick={this.next}>
            <span class="device--desktop">
              Create {FORM_TABS[this.state.activeTab].title}
            </span>
          </Button.Primary>
        </footer>
      </div>
    );
  }
}
