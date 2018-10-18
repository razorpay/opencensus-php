import { connect } from 'react-redux';
import { render } from 'react-dom';

import Button from 'component/Button';
import { ModalMask, Modal, ModalContent } from 'component/Modal';
import Svelte from './Svelte';
import DetailsView from './views/Details/index';
import FormView from './views/Form/index';

import PPShareView from '../Modals/Share';
import { createPaymentPage, sendLink } from '../model';

import { fetchPaymentPage } from 'merchant/modules/wysiwyg';
import { closeModal, openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

@connect(
  state => ({
    user: state.session.user,
    mode: state.session.mode,
    config: state.config.config,
    ...state.wysiwyg,
  }),
  {
    fetchPaymentPage,
    showNotification,
    closeModal,
    openModal,
  }
)
export default class PaymentPagesWysiwyg extends React.PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = { isPageReady: false, isIntroOpened: !this.props.id }; // isIntroOpened = false if editing existing Payment page

  componentWillMount() {
    if (this.props.id) {
      this.props.fetchPaymentPage(this.props.id);
    }
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchPaymentPage(nextProps.id);
    }
  }

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

  initSubApps = () => {
    render(
      <DetailsView payment_page_id={this.props.id} />,
      document.getElementById('details-section')
    );
    render(
      <FormView payment_page_id={this.props.id} />,
      document.getElementById('form-section')
    );
  };

  openPPShareView = (id, shortUrl, title, description) => {
    this.props.openModal({
      size: 'small',
      component: (
        <PPShareView
          handleClose={this.props.closeModal}
          handleAction={sendLink.bind(null, id)}
          isNew={true}
          showNotification={this.props.showNotification}
          url={shortUrl}
          title={title}
          description={description}
          trackerFn={function() {}}
        />
      ),
    });
  };

  handleCreate = () => {
    console.log('Handle Create..', this.props.paymentPageEntity);

    const {
      amount,
      title,
      description,
      stock,
      allow_multiple_units,
      allow_social_share,
    } = this.props.paymentPageEntity;
    const reqPayload = {
      amount,
      title,
      description: description || undefined,
      times_payable: stock || undefined,
      settings: {
        allow_multiple_units: allow_multiple_units | 0,
        allow_social_share: allow_social_share | 0,
      },
    };

    return createPaymentPage(reqPayload)
      .then(resp => {
        if (resp.data) {
          const entityId = resp.data.id;

          this.openPPShareView(
            entityId,
            resp.data.short_url,
            resp.data.title,
            resp.data.description
          );
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
          err = `Some network error has occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
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
    const { paymentPageEntity, id } = this.props;

    const merchantData = {
      name: this.props.user.name,
      brand_color: this.props.config.brand_color,
      image:
        'https://cdn.razorpay.com/logos/AjkWrnqhycTNfR_medium.png' ||
        this.props.user.logo_url,
    };

    const isAllowedToSubmit =
      paymentPageEntity &&
      paymentPageEntity.hasOwnProperty('amount') &&
      paymentPageEntity.title;
    const actionBtns = (
      <React.Fragment>
        <Button.Primary
          onClick={this.handleCreate}
          disabled={!isAllowedToSubmit}
        >
          Create and Publish Page
        </Button.Primary>
      </React.Fragment>
    );

    const pageNavTitle = id ? (
      <>
        Edit Payment Page{' '}
        <span style={{ opacity: 0.35, fontWeight: 400 }}> - {id}</span>
      </>
    ) : (
      'Create New Payment Page'
    );

    return (
      <div class="payment-pages-v2">
        {this.state.isIntroOpened && (
          <IntroMask onClose={this.handleIntroClose} />
        )}

        <Header
          title={pageNavTitle}
          actionBtns={actionBtns}
          handleClose={this.handleClose}
          isPageReady={isPageReady}
        />
        {isPageReady && (
          <Svelte
            payment_page_id={id}
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
          <Button.Primary onClick={onClose} autoFocus>
            Let's Go!
          </Button.Primary>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};
