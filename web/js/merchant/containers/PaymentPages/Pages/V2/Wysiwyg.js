import { connect } from 'react-redux';
import { render } from 'react-dom';

import Button from 'component/Button';
import { ModalMask, Modal, ModalContent } from 'component/Modal';
import Svelte from './Svelte';
import DetailsView from './views/Details/index';
import FormView from './views/Form/index';

@connect(state => ({
  user: state.session.user,
  mode: state.session.mode,
  config: state.config.config,
  ...state.wysiwyg,
}))
export default class PaymentPagesWysiwyg extends React.PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = { isPageReady: false, isIntroOpened: true }; // isIntroOpened = true only when it's a paymentpages is NEW

  componentDidMount() {
    // Insert script in local

    const script = document.createElement('script');

    script.onload = () => {
      // Init the Svelte App in wysiwyg-root;
      this.setState({
        isPageReady: true,
      });
    };

    script.src = 'http://127.0.0.1:7999/static/hosted/wysiwyg.js';

    document.head.appendChild(script);
  }

  handleClose = () => {
    console.log('Handle close button');
  };

  initSubApps() {
    render(<DetailsView />, document.getElementById('details-section'));
    render(<FormView />, document.getElementById('form-section'));
  }

  handleCreate = () => {
    console.log('Handle Create..', this.props.paymentPageEntity);
  };

  handleIntroClose = () => {
    this.setState({ isIntroOpened: false });

    setTimeout(function() {
      const titleEle = document.querySelector(
        '#description-details input[name="title"]'
      );
      titleEle && titleEle.focus();
    }, 100);
  };

  render() {
    const { isPageReady } = this.state;
    const { paymentPageEntity } = this.props;

    const merchantData = {
      name: this.props.user.name,
      brand_color: this.props.config.brand_color,
      image:
        'https://cdn.razorpay.com/logos/AjkWrnqhycTNfR_medium.png' ||
        this.props.user.logo_url,
    };

    const isAllowedToCreate =
      paymentPageEntity.hasOwnProperty('amount') && paymentPageEntity.title;
    const actionBtns = (
      <React.Fragment>
        <Button.Primary
          onClick={this.handleCreate}
          disabled={!isAllowedToCreate}
        >
          Create and Publish Page
        </Button.Primary>
      </React.Fragment>
    );

    return (
      <div class="payment-pages-v2">
        {this.state.isIntroOpened && (
          <IntroMask onClose={this.handleIntroClose} />
        )}

        <Header
          title="Create New Payment Page"
          actionBtns={actionBtns}
          handleClose={this.handleClose}
          isPageReady={isPageReady}
        />
        {isPageReady && (
          <Svelte
            isTestMode={this.props.mode.toLowerCase() === 'test'}
            merchantData={merchantData}
            onMount={this.initSubApps}
          />
        )}
      </div>
    );
  }
}

const Header = ({ title, actionBtns, handleClose, isPageReady }) => {
  return (
    <div class="page-nav">
      <div class="page-size">
        <div class="page-title">{title}</div>

        {actionBtns &&
          isPageReady && <div class="page-action">{actionBtns}</div>}

        {handleClose &&
          isPageReady && (
            <span class="close-btn" onClick={handleClose}>
              ×
            </span>
          )}
      </div>
    </div>
  );
};

const IntroMask = ({ onClose }) => {
  return (
    <ModalMask
      maskClosable={false}
      class="payment-pages-v2-intro"
      isBlur={true}
    >
      <Modal showCloseBtn={false}>
        <ModalContent>
          <div class="heading">Create New Payment Page</div>
          <p>
            This is how the page will appear to your customers.
            <br />
            You can preview and edit the page at the same time!
          </p>
          <Button.Primary onClick={onClose}>Let's Go!</Button.Primary>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};
