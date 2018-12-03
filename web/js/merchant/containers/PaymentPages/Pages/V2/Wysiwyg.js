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

import {
  fetchPaymentPage,
  updateData,
  markDataSaved,
} from 'merchant/modules/wysiwyg';
import { closeModal, openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import { validateUISchema } from 'merchant/containers/PaymentPages/Pages/V2/views/Form/Fields/helpers';

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
    markDataSaved,
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
        isSettingsOpened: false,
      });

      if (!nextProps.id) {
        this.setState({ isIntroOpened: true });
      }
    }
  }

  componentWillUpdate(nextProps) {
    const nextTheme =
      nextProps.paymentPageEntity.settings &&
      nextProps.paymentPageEntity.settings.theme;
    const curTheme =
      this.props.paymentPageEntity.settings &&
      this.props.paymentPageEntity.settings.theme;

    if (!nextTheme || nextTheme !== curTheme) {
      this.changeFETheme(nextTheme);
    }
  }

  changeFETheme(theme) {
    const parentEl = document.getElementById('payment-pages-v2');

    if (theme === 'dark') {
      parentEl.classList.add('dark');
      parentEl.classList.remove('light');
    } else if (theme === 'light') {
      parentEl.classList.add('light');
      parentEl.classList.remove('dark');
    }
  }

  fetchEntity = id => {
    const promise = this.props.fetchPaymentPage(id); // Auto reinitialise store if id doesn't exist.

    if (promise instanceof Promise) {
      promise
        .then(({ data }) => {
          if (data) {
            if (data.settings) {
              this.changeFETheme(data.settings.theme || 'light');
            }
          }
        })
        .catch(err => {
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

    script.src = 'https://cdn.razorpay.com/static/hosted/wysiwyg.js';

    document.head.appendChild(script);

    document.getElementById('payment-pages-v2').classList.add('theme-desktop');

    if (!this.props.id) {
      this.changeFETheme('light');
    }
  }

  componentWillUnmount() {
    document.title = 'Razorpay Dashboard'; // Revert title of dashboard
    this.props.closeModal();
  }

  handleClose = () => {
    this.context.confirm({
      header: this.props.isPageDirty
        ? 'Discard Changes?'
        : 'Go back to Dashboard',
      message: () => (
        <div class="text-semi-muted">
          <p>
            {this.props.isPageDirty
              ? 'Unsaved changes will be lost. Do you want to continue?'
              : ''}
          </p>
        </div>
      ),
      affirmativeLabel: 'Yes',
      abortLabel: 'Cancel',
      action: () => {
        this.props.history.push(`/paymentpages/`);
      },
    });
  };

  initSubApps = () => {
    render(<DetailsView />, document.getElementById('details-section'));
    render(<FormView />, document.getElementById('form-section'));
  };

  openPPShareView = (id, shortUrl, title, description, isEditExistingId) => {
    this.props.openModal({
      size: 'small',
      component: (
        <PPShareView
          handleClose={this.props.closeModal}
          handleAction={sendLink.bind(null, id)}
          isNew={true}
          isPaymentPagesV2={true}
          showNotification={this.props.showNotification}
          url={shortUrl}
          title={title}
          description={description}
          trackerFn={function() {}}
          closeModal={this.props.closeModal}
          isEditExistingId={isEditExistingId}
          AddonAction={
            <div class="label--faded m-t">
              You can customize this url from{' '}
              <Button.Transparent
                type="submit"
                class="Button--Link"
                onClick={() => {
                  this.props.closeModal();
                  this.setState({ isSettingsOpened: true });
                }}
              >
                Page Settings
              </Button.Transparent>
            </div>
          }
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

    if (typeof formData.theme !== 'undefined') {
      if (formData.theme === '0') {
        payload.settings.theme = 'dark';
      } else {
        payload.settings.theme = 'light';
      }
    }

    const isEditExistingId = this.props.id;

    // In edit mode
    if (isEditExistingId) {
      editPaymentPage(this.props.id, payload)
        .then(resp => {
          if (resp.data) {
            this.props.updateData(payload);
            this.props.markDataSaved();

            this.setState({
              isSettingsOpened: false,
            });

            const entityId = resp.data.id;

            this.openPPShareView(
              entityId,
              resp.data.short_url,
              resp.data.title,
              resp.data.description,
              true
            );
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
    // console.log('Handle Create..', paymentPageEntity);

    const {
      amount,
      title,
      description,
      quantity,
      terms,
      support_email,
      support_contact,
      settings,
    } = paymentPageEntity;

    // Remove Email and Phone in all cases before sending to API.
    const udf_schema = [...FORM_SCHEMA];

    const isValidSchema = validateUISchema(udf_schema);

    if (!isValidSchema) {
      throw 'UI Schema is not valid';
      return;
    }

    const reqPayload = {
      amount: amount || null,
      title,
      description: description || null,
      times_payable: quantity || null,
      terms: terms || null,
      support_email: support_email || null,
      support_contact: support_contact || null,
      settings: {
        theme: settings.theme,
        allow_multiple_units: settings.allow_multiple_units ? '1' : '0',
        allow_social_share: settings.allow_social_share ? '1' : '0',
        udf_schema: JSON.stringify(udf_schema.splice(2)),
      },
    };
    // console.log('REQ PAYLOAD...', reqPayload);

    const isEditExistingId = !!this.props.id;
    const requestAPIPromise = isEditExistingId
      ? editPaymentPage(this.props.id, reqPayload)
      : createPaymentPage(reqPayload);

    return requestAPIPromise
      .then(resp => {
        if (resp.data) {
          this.props.markDataSaved();

          const entityId = resp.data.id;

          this.props.history.push(`/paymentpages/${entityId}/edit`);
          this.openPPShareView(
            entityId,
            resp.data.short_url,
            resp.data.title,
            resp.data.description,
            isEditExistingId
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
        '#description-details .Input-el[name="title"]'
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
          disabled={
            paymentPageEntity.id &&
            typeof paymentPageEntity.title === 'undefined'
          }
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

    let themeColor;
    if (paymentPageEntity.settings) {
      themeColor =
        paymentPageEntity.settings.theme === 'dark' ? '#383838' : '#efefef';
    }

    return (
      <div id="payment-pages-v2" style={{ backgroundColor: themeColor }}>
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
          handleClose={this.handleClose}
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
      <Link class="back-btn" to="/paymentpages/">
        <i class="i i-chevron-left" />
        Back to Dashboard
      </Link>
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
