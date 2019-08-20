import { withRouter } from 'react-router';
import { connect } from 'react-redux';
import { render } from 'react-dom';

import { Link } from 'react-router-dom';
import Button, { AsyncBtn } from 'component/Button';
import { ModalMask, Modal, ModalContent } from 'component/Modal';
import Svelte from './Svelte';
import DetailsView from './Details';
import FormView from './Form';

import TemplatesMask from './Templates';
import PPSettingsView from '../Modals/Settings';
import PPShareView from '../Modals/Share';
import { createPaymentPage, editPaymentPage, sendLink } from '../model';

import { autoPrefixUrls } from 'rzp/utils/rzp-utils';

import {
  fetchPaymentPage,
  updateData,
  markDataSaved,
  updateTemplateType,
  isFormItemOfTypeAmount,
} from 'merchant/modules/wysiwyg';
import { closeModal, openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

// TODO: Change validation logic as per V2 / V3. (Ensure that "settings" is not considered in comparison of keys)
import { validateUISchema } from 'merchant/containers/PaymentPages/Pages/Create/Form/UDF_Fields/V2';

import {
  trackWYSIWYGCloseIntent,
  trackConfirmWYSIWYGCloseIntent,
  trackPageSettingsClick,
  trackPageSave,
} from '../ga';

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
    updateTemplateType,
  }
)
export default class PaymentPagesWysiwyg extends React.PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = { isPageReady: false, isTemplatesViewOpened: !this.props.id }; // isTemplatesViewOpened = false if editing existing Payment page

  componentWillMount() {
    this.fetchEntity(this.props.id);

    // Preload Social media image
    const socialMediaIcons = new Image();
    socialMediaIcons.src =
      'https://cdn.razorpay.com/static/assets/social-share/icons.png';
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchEntity(nextProps.id);
      this.props.closeModal();

      this.setState({
        isPageLoadError: null,
        isTemplatesViewOpened: false,
        isSettingsOpened: false,
      });

      if (!nextProps.id) {
        this.setState({ isTemplatesViewOpened: true });
      }
    }
  }

  componentWillUpdate(nextProps) {
    const nextTheme =
      nextProps.paymentPageEntity &&
      nextProps.paymentPageEntity.settings &&
      nextProps.paymentPageEntity.settings.theme;
    const curTheme =
      this.props.paymentPageEntity &&
      this.props.paymentPageEntity.settings &&
      this.props.paymentPageEntity.settings.theme;

    if (!nextTheme || nextTheme !== curTheme) {
      if (nextProps.paymentPageEntity && nextProps.paymentPageEntity.id) {
        this.changeFETheme(nextTheme);
      }
    }
  }

  changeFETheme(theme) {
    const parentEl = document.getElementById('paymentpage-container');

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

    document
      .getElementById('paymentpage-container')
      .classList.add('theme-desktop');
  }

  componentWillUnmount() {
    document.title = 'Razorpay Dashboard'; // Revert title of dashboard
    this.props.closeModal();
  }

  handleClose = () => {
    trackWYSIWYGCloseIntent();

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
        trackConfirmWYSIWYGCloseIntent();
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
          openModal={this.props.openModal}
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
                  trackPageSettingsClick();
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

    payload.settings.payment_success_message =
      formData.payment_success_message || '';

    payload.settings.payment_success_redirect_url = formData.payment_success_redirect_url
      ? autoPrefixUrls(formData.payment_success_redirect_url)
      : '';

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
    const { paymentPageEntity, FORM_ITEMS } = this.props;
    // console.log('Handle Create..', paymentPageEntity);

    const {
      currency,
      amount,
      title,
      description,
      quantity,
      terms,
      support_email,
      support_contact,
      settings,
      expire_by,
    } = paymentPageEntity;

    // Remove Email and Phone in all cases before sending to API.
    const formItems = [...FORM_ITEMS]; // Separate UDF and amount fields from FORM ITEMS.

    const udf_schema = [],
      paymentPageItems = [];

    FORM_ITEMS.forEach((fi, ix) => {
      fi.position = ix; // Updating the position of each item (both udf and amount fields)

      if (isFormItemOfTypeAmount(fi)) {
        // Will exist only when this.props.user.isPPV3Enabled === true
        // TODO: Check with BE if id needs to be sent in case of edited amount item.
        paymentPageItems.push(fi);
      } else {
        udf_schema.push(fi);
      }
    });

    const isValidSchema = validateUISchema(udf_schema);

    if (!isValidSchema) {
      throw 'UI Schema is not valid';
      return;
    }

    const reqPayload = {
      currency,
      amount: amount || null,
      expire_by: expire_by || null,
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
        payment_success_message: settings.payment_success_message,
        payment_success_redirect_url: settings.payment_success_redirect_url,
        udf_schema: JSON.stringify(udf_schema),
      },
    };

    // Will exist only when props.user.isPPV3Enabled = true
    if (paymentPageItems.length) {
      reqPayload.payment_page_items = paymentPageItems;
    }

    // console.log('REQ PAYLOAD...', reqPayload);

    const isEditExistingId = !!this.props.id;
    const requestAPIPromise = isEditExistingId
      ? editPaymentPage(this.props.id, reqPayload)
      : createPaymentPage(reqPayload);

    const trackData = [];
    if (description) {
      trackData.push('description');
    }

    if (settings.allow_social_share) {
      trackData.push('social_share');
    }

    if (terms) {
      trackData.push('terms');
    }

    if (support_email) {
      trackData.push('support_email');
    }

    if (support_contact) {
      trackData.push('support_contact');
    }

    if (udf_schema) {
      trackData.push('form_fields: ' + udf_schema.length); // count of total form fields
    }

    if (isEditExistingId) {
      trackPageSave('save', trackData);
    } else {
      trackPageSave('create', trackData);
    }

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
    this.setState({ isTemplatesViewOpened: false });

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
    let isAllowedToSubmit, actionBtns, themeColor;

    const merchantData = {
      name: this.props.user.billing_label || this.props.user.name,
      brand_color: this.props.config.brand_color,
      image: this.props.user.logo_url,
    };

    if (paymentPageEntity) {
      isAllowedToSubmit =
        paymentPageEntity &&
        paymentPageEntity.hasOwnProperty('amount') &&
        paymentPageEntity.title;

      actionBtns = (
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

      if (paymentPageEntity.settings) {
        themeColor =
          paymentPageEntity.settings.theme === 'dark' ? '#383838' : '#efefef';
      }
    }

    const pageNavTitle = payment_page_id ? (
      <React.Fragment>
        Edit Payment Page <span> - {payment_page_id}</span>
      </React.Fragment>
    ) : (
      'Create New Payment Page'
    );

    let content;

    if (isPageLoadError) {
      if (isPageLoadError === ERROR.SCRIPT) {
        content = (
          <div class="page-center">
            Some network error has occurred. Please reload the page.
          </div>
        );
      } else if (isPageLoadError === ERROR.INVALID_ENTITY) {
        content = (
          <div class="page-center">
            Payment page with id <b>{payment_page_id}</b> doesn't exist.
            <br />
            Go to <Link to="/paymentpages/">Payment Pages list</Link>{' '}
          </div>
        );
      }
    } else if (isPageReady) {
      content = (
        <Svelte
          payment_page_id={payment_page_id}
          isTestMode={this.props.mode.toLowerCase() === 'test'}
          merchantData={merchantData}
          onMount={this.initSubApps}
        />
      );
    }

    return (
      <div
        id="paymentpage-container"
        class="payment-pages-v2 payment-pages-v3"
        style={{ backgroundColor: themeColor }}
      >
        {this.state.isTemplatesViewOpened && (
          <TemplatesMask
            onClose={this.handleIntroClose}
            selectTemplate={this.props.updateTemplateType}
          />
        )}

        {this.state.isSettingsOpened && (
          <PPSettingsView
            handleClose={this.togglePageSettings}
            openModal={this.props.openModal}
            paymentPageEntity={paymentPageEntity}
            handleAction={this.handleSaveSettings}
            isNew={this.props.id}
            isTestMode={this.props.mode.toLowerCase() === 'test'}
          />
        )}

        <Header
          title={pageNavTitle}
          actionBtns={actionBtns}
          isPageReady={isPageReady}
          handleClose={this.handleClose}
        />
        {content}
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

function dataURLtoFile(dataurl, filename) {
  var arr = dataurl.split(','),
    mime = arr[0].match(/:(.*?);/)[1],
    bstr = atob(arr[1]),
    n = bstr.length,
    u8arr = new Uint8Array(n);
  while (n--) {
    u8arr[n] = bstr.charCodeAt(n);
  }
  return new File([u8arr], filename, { type: mime });
}
