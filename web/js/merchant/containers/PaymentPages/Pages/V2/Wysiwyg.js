import { withRouter } from 'react-router';
import { connect } from 'react-redux';
import { render } from 'react-dom';

import { Link } from 'react-router-dom';
import Button, { AsyncBtn } from 'component/Button';
import { ModalMask, Modal, ModalContent } from 'component/Modal';
import Svelte from './Svelte';
import DetailsView from './views/Details/index';
import FormView from './views/Form/index';

import PPSettingsView from '../Modals/Settings';
import PPShareView from '../Modals/Share';
import { createPaymentPage, editPaymentPage, sendLink } from '../model';

import { fetchPaymentPage, updateData } from 'merchant/modules/wysiwyg';
import { closeModal, openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

const ERROR = {
  SCRIPT: 1,
  INVALID_ENTITY: 2,
};

@withRouter
@connect(
  state => ({
    user: state.session.user,
    mode: state.session.mode,
    config: state.config.config,
    ...state.wysiwyg,
  }),
  {
    updateData,
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
    this.fetchEntity(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchEntity(nextProps.id);
      this.props.closeModal();

      this.setState({
        isPageLoadError: null,
        isIntroOpened: false,
      });

      if (!nextProps.id) {
        this.setState({ isIntroOpened: true });
      }
    }
  }

  fetchEntity = id => {
    const promise = this.props.fetchPaymentPage(id); // Auto reinitialise store if id doesn't exist.

    if (promise instanceof Promise) {
      promise.catch(err => {
        this.setState({
          isPageLoadError: ERROR.INVALID_ENTITY,
        });
      });
    }
  };

  componentDidMount() {
    // Insert script in local

    const script = document.createElement('script');

    script.onload = () => {
      // Init the Svelte App in wysiwyg-root;
      this.setState({
        isPageReady: true,
      });
    };

    script.onerror = () => {
      this.setState({
        isPageLoadError: ERROR.SCRIPT,
      });
    };

    // TODO: Change to prod CDN url
    script.src = 'http://127.0.0.1:7999/static/hosted/wysiwyg.js';

    document.head.appendChild(script);
  }

  handleClose = () => {
    // TODO: Handle close button
  };

  initSubApps = () => {
    render(<DetailsView />, document.getElementById('details-section'));
    render(<FormView />, document.getElementById('form-section'));
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

  // Handles both in save and edit mode.
  handleSaveSettings = formData => {
    const payload = {};

    if (formData.expire_by) {
      payload.expire_by = formData.expire_by;
    }

    if (formData.slug) {
      payload.slug = formData.slug.trim();
    }

    payload.settings = {};

    if (formData.theme) {
      if (formData.theme === '0') {
        payload.settings.theme = 'dark';
      } else {
        payload.settings.theme = 'light';
      }
    }

    // In edit mode
    if (this.props.id) {
      editPaymentPage(this.props.id, payload)
        .then(resp => {
          this.setState({
            isSettingsOpened: false,
          });

          if (resp.data) {
            this.props.updateData(payload);

            this.props.showNotification({
              type: 'success',
              message: 'Page Settings are successfully updated',
            });
          }
        })
        .catch(({ errors }) => {
          this.setState({
            isSettingsOpened: false,
          });

          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    } else {
      this.props.updateData(payload);

      this.setState({
        isSettingsOpened: false,
      });
    }
  };

  // Handles both Create and Edit payment page.
  handleSavePublish = () => {
    const { FORM_SCHEMA, paymentPageEntity } = this.props;

    console.log('Handle Create..', paymentPageEntity);

    const {
      amount,
      title,
      description,
      terms,
      stock,
      allow_multiple_units,
      allow_social_share,
    } = paymentPageEntity;

    // Remove Email and Phone in all cases before sending to API.
    const udf_schema = [...FORM_SCHEMA];

    // TODO: Add validate method FORM_SCHEMA before sending. Write test case also around this method.
    const reqPayload = {
      amount: amount || undefined,
      title,
      description: description || undefined,
      times_payable: stock || undefined,
      terms: terms || undefined,
      settings: {
        allow_multiple_units: !!allow_multiple_units ? '1' : undefined,
        allow_social_share: !!allow_social_share ? '1' : '0',
        udf_schema: JSON.stringify(udf_schema.splice(2)), // Remove Email and Phone in all cases before sending to API.
      },
    };

    const isEditExistingId = this.props.id;
    const requestAPIPromise = isEditExistingId
      ? editPaymentPage(this.props.id, reqPayload)
      : createPaymentPage(reqPayload);

    return requestAPIPromise
      .then(resp => {
        if (resp.data) {
          if (isEditExistingId) {
            this.props.showNotification({
              type: 'success',
              message: 'Paymentpage is successfully Saved and Published',
            });
          } else {
            const entityId = resp.data.id;

            this.props.history.push(`/paymentpages/${entityId}/edit`);

            this.openPPShareView(
              entityId,
              resp.data.short_url,
              resp.data.title,
              resp.data.description
            );
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

  togglePageSettings = () => {
    this.setState({
      isSettingsOpened: !this.state.isSettingsOpened,
    });
  };

  render() {
    const { isPageReady, isPageLoadError } = this.state;
    const { paymentPageEntity, id: payment_page_id } = this.props;

    const merchantData = {
      name: this.props.user.name,
      brand_color: this.props.config.brand_color,
      image: this.props.user.logo_url,
    };

    const isAllowedToSubmit =
      paymentPageEntity &&
      paymentPageEntity.hasOwnProperty('amount') &&
      paymentPageEntity.title;
    const actionBtns = (
      <React.Fragment>
        <Button.Transparent
          type="button"
          style={{ color: '#fff' }}
          onClick={this.togglePageSettings}
        >
          Page Settings
        </Button.Transparent>
        <AsyncBtn.Primary
          onClick={this.handleSavePublish}
          disabled={!isAllowedToSubmit}
          pendingState="Publishing"
        >
          {payment_page_id
            ? 'Save and Publish Page'
            : 'Create and Publish Page'}
        </AsyncBtn.Primary>
      </React.Fragment>
    );

    const pageNavTitle = payment_page_id ? (
      <>
        Edit Payment Page <span> - {payment_page_id}</span>
      </>
    ) : (
      'Create New Payment Page'
    );

    return (
      <div class="payment-pages-v2">
        {this.state.isIntroOpened && (
          <IntroMask onClose={this.handleIntroClose} />
        )}

        {this.state.isSettingsOpened && (
          <PPSettingsView
            handleClose={this.togglePageSettings}
            paymentPageEntity={paymentPageEntity}
            handleAction={this.handleSaveSettings}
            isNew={this.props.id}
          />
        )}

        <Header
          title={pageNavTitle}
          actionBtns={actionBtns}
          isPageReady={isPageReady}
        />
        {!isPageLoadError &&
          isPageReady && (
            <Svelte
              payment_page_id={payment_page_id}
              isTestMode={this.props.mode.toLowerCase() === 'test'}
              merchantData={merchantData}
              onMount={this.initSubApps}
            />
          )}
        {do {
          if (isPageLoadError) {
            if (isPageLoadError === ERROR.SCRIPT) {
              <div class="page-center">
                Some network error has occurred. Please reload the page.
              </div>;
            } else if (isPageLoadError === ERROR.INVALID_ENTITY) {
              <div class="page-center">
                Payment page with id <b>{payment_page_id}</b> doesn't exist.
                <br />
                Go to <Link to="/paymentpages/">Payment Pages list</Link>{' '}
              </div>;
            }
          }
        }}
      </div>
    );
  }
}

const Header = ({ title, actionBtns, handleClose, isPageReady }) => {
  return (
    <div class="page-nav">
      <div class="page-size">
        <div class="page-title">{title}</div>

        {isPageReady &&
          !!actionBtns && <div class="page-action">{actionBtns}</div>}

        {isPageReady &&
          !!handleClose && (
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
