import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router';
import { connect } from 'react-redux';
import ReactDOM from 'react-dom';

import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Svelte from './Svelte';
import DetailsSection from './DetailsSection';
import FormSection from './FormSection';
import SubscriptionButtonLaunchFullPageBanner from 'merchant/components/Announcements/SubscriptionButtonLaunch/FullPageBanner';
import TemplatesMask from './Templates';
import PPSettingsView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Settings';
import PaymentReceipt from 'merchant/views/PaymentPages/PaymentPages/components/Modals/PaymentReceipt';
import ShiprocketConfirmation from 'merchant/views/PaymentPages/PaymentPages/components/Modals/ShiprocketConfirmation';
import Success from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Success';
import PPShareView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Share';
import MerchantLogoTooltip from 'merchant/views/PaymentPages/PaymentPages/components/MerchantLogoTooltip';
import MobileActionButtons from './components/MobileActionButtons';

import { createPaymentPage, editPaymentPage, sendLink, setReceiptDetails } from '../model';
import track from './track';
import { isMobileDevice } from 'merchant/components/Home/data';
import debounce from 'common/utils/debounce';

import {
  autoPrefixUrls,
  getURLQueryParams,
  rupeesToPaise,
  classList,
} from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';

import {
  initDefaultFormItems,
  fetchPaymentPage,
  updateData,
  refreshPageData,
  markDataSaved,
  updateTemplateType,
  isFormItemOfTypeAmount,
  updateReceiptDetails,
  setSettingsModal,
  replaceInFormItems,
  setShiprocketModal,
} from 'merchant/reducers/wysiwyg';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

// TODO: Change validation logic as per V2 / V3. (Ensure that "settings" is not considered in comparison of keys)
import {
  validateUISchema,
  SHIPROCKET_FORM_ITEMS,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';

import {
  trackWYSIWYGCloseIntent,
  trackConfirmWYSIWYGCloseIntent,
  trackPageSettingsClick,
  trackClickOnCreateEmbedButton,
} from '../ga';

const ERROR = {
  SCRIPT: 1,
  INVALID_ENTITY: 2,
};

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    org: state.session.org,
    config: state.config.config,
    ...state.wysiwyg,
  }),
  {
    refreshPageData,
    updateData,
    initDefaultFormItems,
    fetchPaymentPage,
    markDataSaved,
    showNotification,
    closeModal,
    openModal,
    updateTemplateType,
    updateReceiptDetails,
    replaceInFormItems,
    setSettingsModal,
    setShiprocketModal,
  },
)
@RTracking(() => window.rzpQ.component('PaymentPagesWysiwyg'))
export default class PaymentPagesWysiwyg extends React.PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  isIntentDuplicate = false;
  supportPhoneRef = React.createRef();
  supportEmailRef = React.createRef();

  state = {
    isPageReady: false,
    isTemplatesViewOpened: !this.props.id, // isTemplatesViewOpened = false if editing existing Payment page
    onSvelteAppMount: false,
    merchant_tnc: null,
    isMerchantDataLoaded: false,
    formItemsBackup: [],
  };

  componentWillMount() {
    this.fetchEntity(this.props.id, true);

    // Preload Social media image
    const socialMediaIcons = new Image();
    socialMediaIcons.src = 'https://cdn.razorpay.com/static/assets/social-share/icons.png';

    this.fetchIfIntentDuplicate();
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchEntity(nextProps.id);
      this.props.closeModal();

      this.setState({
        isPageLoadError: null,
        isTemplatesViewOpened: false,
        isPageReceiptModalOpened: false,
      });
      this.props.setSettingsModal(false);

      if (!nextProps.id) {
        this.setState({ isTemplatesViewOpened: true });
      }

      this.fetchIfIntentDuplicate();
    } else if (!nextProps.id && this.props.id != nextProps.id) {
      // Handle only when both are not /new
      const searchQuery = getURLQueryParams(this.props.location.search);
      const searchQueryNext = getURLQueryParams(nextProps.location.search);

      // Handle moving to '/new'
      if (!nextProps.id && !searchQueryNext.duplicate_id) {
        this.props.refreshPageData();
      } else if (
        // Handle moving to different '?duplicate_id'
        !nextProps.id &&
        searchQueryNext.duplicate_id &&
        searchQuery.duplicate_id != searchQueryNext.duplicate_id
      ) {
        this.fetchIfIntentDuplicate(searchQueryNext.duplicate_id);
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

  fetchIfIntentDuplicate(entityId) {
    const searchQuery = getURLQueryParams(this.props.location.search);
    const entityIdToDuplicate = entityId || searchQuery.duplicate_id;

    if (entityIdToDuplicate) {
      // TODO: Use for tracking on saving
      this.isIntentDuplicate = true;

      // Don't show templates screen if intent is to duplicate
      this.setState({
        isTemplatesViewOpened: false,
      });

      this.fetchEntity(entityIdToDuplicate);
    }
  }

  fetchEntity = (id, isInitialLoad) => {
    const promise = this.props.fetchPaymentPage(id, this.isIntentDuplicate); // Auto reinitialise store if id doesn't exist.

    if (promise instanceof Promise) {
      promise
        .then(({ data }) => {
          // on page load -> open modal if present in query params
          if (isInitialLoad) {
            const { location } = this.props;

            const searchParams = getURLQueryParams(location.search);

            if (searchParams.modal === 'receipt') {
              this.togglePageReceiptModal();
            } else if (searchParams.modal === 'page') {
              this.togglePageSettings();
            } else if (searchParams.modal === 'disableShiprocket') {
              this.handleShiprocket();
            }
          }
          if (data) {
            if (data.settings) {
              this.changeFETheme(data.settings.theme || 'light');
            }
          }
        })
        .catch(() => {
          this.setState({
            isPageLoadError: ERROR.INVALID_ENTITY,
          });
        });
    }
  };

  componentDidMount() {
    this.fetchMerchantDetails();

    this.props.initDefaultFormItems();

    track.init(this.props.tracking.trackEvent, {
      payment_page_id: this.props.id,
    });

    // Load color.js
    let script = document.createElement('script');
    script.src = 'https://cdn.razorpay.com/static/assets/color.js';
    document.head.appendChild(script);

    // Insert wysiwyg script in dashboard to reuse shell and styles as much possible
    script = document.createElement('script');
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

    script.src = `${window.cdnBaseUrl}/static/hosted/wysiwyg.js`;
    document.head.appendChild(script);

    document.getElementById('paymentpage-container').classList.add('theme-desktop');
  }

  componentWillUnmount() {
    // unmount nodes added during initSubApps
    const detailsSection = document.getElementById('details-section');
    const formSection = document.getElementById('form-section');
    // if component unmounts before these sections are loaded, we check before unmounting
    if (detailsSection) {
      ReactDOM.unmountComponentAtNode(detailsSection);
    }
    if (formSection) {
      ReactDOM.unmountComponentAtNode(formSection);
    }

    document.title = 'Razorpay Dashboard'; // Revert title of dashboard
    this.props.closeModal();

    window.removeEventListener('resize', this.debouncedHandleModalPosition);
  }

  fetchMerchantDetails = () => {
    const { mode, user } = this.props;
    merchantFetch({ url: `merchant/${user.id}/tnc`, mode })
      .then((res) => {
        this.setState({ merchant_tnc: !res.error ? res.data : null });
      })
      .finally(() => {
        this.setState({ isMerchantDataLoaded: true });
      });
  };

  handleClose = () => {
    trackWYSIWYGCloseIntent();

    this.context.confirm({
      header: this.props.isPageDirty ? 'Discard Changes?' : 'Go back to Dashboard',
      message: () => (
        <div class="text-semi-muted">
          <p>
            {this.props.isPageDirty ? 'Unsaved changes will be lost. Do you want to continue?' : ''}
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
    ReactDOM.render(
      <DetailsSection
        supportEmailRef={this.supportEmailRef}
        supportPhoneRef={this.supportPhoneRef}
      />,
      document.getElementById('details-section'),
    );
    ReactDOM.render(<FormSection />, document.getElementById('form-section'));

    this.setState({
      onSvelteAppMount: true,
    });
  };

  openSuccessView = (id, shortUrl, title, description, isEditExistingId) => {
    const isNewPPSuccessModalEnabled = this.props.user.isNewPPSuccessModalEnabled;
    let modalContent;

    if (isNewPPSuccessModalEnabled) {
      modalContent = (
        <Success
          id={id}
          handleClose={this.props.closeModal}
          openModal={this.props.openModal}
          handleSendLink={sendLink.bind(null, id)}
          showNotification={this.props.showNotification}
          title={title}
          url={shortUrl}
          trackerFn={noop}
          trackClickOnCreateEmbedButton={(_) => trackClickOnCreateEmbedButton('new')}
          closeModal={this.props.closeModal}
          isEditExistingId={isEditExistingId}
          user={this.props.user}
          openSettingsModal={(_) => {
            trackPageSettingsClick();
            this.props.closeModal();
            this.props.setSettingsModal(true);
          }}
        />
      );
    } else {
      modalContent = (
        <PPShareView
          id={id}
          handleClose={this.props.closeModal}
          openModal={this.props.openModal}
          handleAction={sendLink.bind(null, id)}
          isNew={true}
          isPaymentPagesV2={true}
          showNotification={this.props.showNotification}
          title={title}
          url={shortUrl}
          description={description}
          trackerFn={noop}
          trackClickOnCreateEmbedButton={trackClickOnCreateEmbedButton}
          closeModal={this.props.closeModal}
          isEditExistingId={isEditExistingId}
          openSettingsModal={(_) => {
            trackPageSettingsClick();
            this.props.closeModal();
            this.props.setSettingsModal(true);
          }}
        />
      );
    }

    this.props.openModal({
      size: 'medium',
      component: modalContent,
    });
  };

  // Update settings in store
  handleSaveSettings = (formData) => {
    const data = {};

    data.expire_by = formData.expire_by;

    if (formData.slug) {
      data.slug = formData.slug.trim();
    }

    data.settings = {};

    if (typeof formData.theme !== 'undefined') {
      if (formData.theme === '0') {
        data.settings.theme = 'dark';
      } else {
        data.settings.theme = 'light';
      }
    }

    data.settings.payment_success_message = formData.payment_success_message || '';

    data.settings.payment_success_redirect_url = formData.payment_success_redirect_url
      ? autoPrefixUrls(formData.payment_success_redirect_url)
      : '';

    // Update in store
    this.props.updateData(data);

    this.props.setSettingsModal(false);
  };

  handleSavePaymentReceipt = (data) => {
    this.props.updateReceiptDetails(data);
  };

  // Handles both Create and Edit payment page.
  @RTracking(() =>
    window.rzpQ.onbr().success('dash.pp_action', {
      action: 'Initiate_PP_Launch',
    }),
  )
  handleSavePublish = (label) => {
    const isEditExistingId = !!this.props.id;
    const { paymentPageEntity, FORM_ITEMS } = this.props;
    // console.log('Handle Create..', paymentPageEntity);

    const {
      currency,
      title,
      description,
      template_type,
      terms,
      support_email,
      support_contact,
      settings,
      expire_by,
      slug,
      receipt,
    } = paymentPageEntity;

    const udf_schema = [];
    const paymentPageItems = [];

    // Separate UDF and amount fields from FORM ITEMS.
    FORM_ITEMS.forEach((fi, ix) => {
      fi.settings = fi.settings || {};
      fi.settings.position = ix; // Updating the position of each item (both udf and amount fields)

      if (isFormItemOfTypeAmount(fi)) {
        // Prepare payload for amount field (as extra fields aren't required to be sent)
        const {
          id,
          image_url,
          mandatory,
          min_purchase,
          max_purchase,
          min_amount,
          max_amount,
          item,
          // eslint-disable-next-line no-shadow
          settings,
          stock,
        } = fi;
        // eslint-disable-next-line no-shadow
        const { name, description, amount } = item;

        const prunedFi = {
          item: {
            name,
            description,
            amount: amount ? rupeesToPaise(amount) : null, // Convert in paisa (smaller unit)
          },
          settings, // Contains position
          image_url,
          mandatory,
          min_purchase,
          max_purchase,
          min_amount: min_amount ? rupeesToPaise(min_amount) : null,
          max_amount: max_amount ? rupeesToPaise(max_amount) : null,
          stock: stock ? stock : null, // stock cannot be 0 or ""
        };

        if (isEditExistingId) {
          if (id) {
            prunedFi.id = id; // Editing existing item
          } else {
            prunedFi.item.currency = currency; // currency to be added only for newly added items
          }
        } else {
          prunedFi.item.currency = currency; // Currency cannot be edited from UI once Payment page is created
        }

        /*
         * NOTE: Since payment_page_items are not shareable items with other payment pages, therefore, currency of payment_page entity is used as single source of truth .
         * Currency of each payment_page_item is ignored in general, and is being added here only for the reason that blueprint of line_items of invoices is reused for PP in BE.
         * */

        paymentPageItems.push(prunedFi);
      } else {
        udf_schema.push(fi);
      }
    });

    if (!paymentPageItems.length) {
      this.props.showNotification({
        type: 'error',
        message: 'Add at least 1 Price field',
      });

      return;
    }

    if (this.props.user.isPaymentPageDescriptionRequired) {
      // {"value":[{"insert":"\n"}],"metaText":". "} is the value in the state when description(quill instance) is empty
      const isQuillEmptyRegex = /{"value":\[{"insert":"\s*\\n"}],"metaText":"\s*. "}/;

      if (!description || isQuillEmptyRegex.test(description)) {
        this.props.showNotification({
          type: 'error',
          message: 'Please fill out the description',
        });

        const descriptionElement = document.querySelector('#description-quill .ql-editor');
        if (descriptionElement) {
          descriptionElement.focus();
        }

        return;
      }
    }

    const isValidSchema = validateUISchema(udf_schema);

    // console.log('udf_schema......', udf_schema);

    if (!isValidSchema) {
      throw new Error('UI Schema is not valid');
    }

    //check if contact details are filled
    if (!support_contact || !support_email) {
      this.props.showNotification({
        type: 'error',
        message: `Please add your support details in the 'Contact Us' section`,
      });
      if (!support_email) {
        this.supportEmailRef?.current?.el.focus();
      }
      if (support_email && !support_contact) {
        this.supportPhoneRef?.current?.el.focus();
      }

      return;
    }

    const reqPayload = {
      currency,
      expire_by: expire_by || null,
      title,
      description: description || null,
      terms: terms || null,
      support_email: support_email || null,
      support_contact: support_contact || null,
      settings: {
        theme: settings.theme,
        allow_social_share: settings.allow_social_share ? '1' : '0',
        payment_success_message: settings.payment_success_message,
        payment_success_redirect_url: settings.payment_success_redirect_url,
        udf_schema: JSON.stringify(udf_schema),
        pp_fb_pixel_tracking_id: settings.pp_fb_pixel_tracking_id,
        pp_ga_pixel_tracking_id: settings.pp_ga_pixel_tracking_id,
        pp_fb_event_add_to_cart_enabled: settings.pp_fb_event_add_to_cart_enabled,
        pp_fb_event_initiate_payment_enabled: settings.pp_fb_event_initiate_payment_enabled,
        pp_fb_event_payment_complete_enabled: settings.pp_fb_event_payment_complete_enabled,
        goal_tracker: settings.goal_tracker ? pruneGoalTracker(settings.goal_tracker) : undefined,
        partner_webhook_settings: settings.partner_webhook_settings,
      },
      slug,
    };

    // Send template type in while creation
    if (!isEditExistingId) {
      reqPayload.template_type = template_type;
    }

    reqPayload.settings.checkout_options = {
      ...settings.checkout_options,
    };

    reqPayload.settings.payment_button_label = settings.payment_button_label;
    reqPayload.payment_page_items = paymentPageItems;

    // console.log('REQ PAYLOAD...', reqPayload);

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
      trackData.push(`form_fields: ${udf_schema.length}`); // count of total form fields
    }

    const searchQueryNext = getURLQueryParams(this.props.location.search);

    track.publishPaymentPage(label, !isEditExistingId, !!searchQueryNext.duplicate_id);

    // Note: Don't make this call and save receipt call in parallel bcoz they modify the same DB table which gets locked.

    // eslint-disable-next-line consistent-return
    return requestAPIPromise
      .then((resp) => {
        if (resp.data) {
          const entityId = resp.data.id;

          track.publishPaymentPageSuccess(entityId, !isEditExistingId);

          this.saveReceiptSettings(entityId, receipt)
            .then(() => {
              window.rzpQ.paymentPages().interaction('pp.receipt.configured', {
                page_id: this.props.id,
              });

              this.onSaveSuccessActions(resp, isEditExistingId);
            })
            .catch(() => {
              this.onSaveSuccessActions(resp, isEditExistingId);
            });
        } else {
          throw new Error(resp.errors);
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          if (errors.length) {
            errors.forEach((e) => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });
          }

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some network error has occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });

        track.publishPaymentPageFail(!isEditExistingId);
      });
  };

  onSaveSuccessActions = (resp, isEditExistingId) => {
    const { isPPSuccessPage } = this.props.user;

    this.props.markDataSaved();
    this.isIntentDuplicate = false;

    const entityId = resp.data.id;

    if (isPPSuccessPage) {
      this.props.history.push(`/paymentpages/${entityId}/success`);
    } else {
      this.openSuccessView(
        entityId,
        resp.data.short_url,
        resp.data.title,
        resp.data.description,
        isEditExistingId,
      );
    }
  };

  saveReceiptSettings = (entityId, receipt) => {
    const requestAPIPromiseForReceipt = setReceiptDetails(entityId, receipt);

    return requestAPIPromiseForReceipt
      .then((res) => {
        if (!res || !res.success) {
          throw new Error(res.errors);
        }

        return res;
      })
      .catch(() => {
        this.props.showNotification({
          type: 'error',
          message: 'Receipt settings could not be saved. Please try again.',
        });
      });
  };

  handleIntroClose = () => {
    this.setState({ isTemplatesViewOpened: false });

    setTimeout(() => {
      const titleEle = document.querySelector('#description-details .Input-el[name="title"]');
      if (titleEle) {
        titleEle.focus();
      }
    }, 100);
  };

  togglePageSettings = () => {
    if (!this.props.isSettingsOpened) {
      track.settings.open();
    }
    this.props.setSettingsModal(!this.props.isSettingsOpened);
  };

  togglePageReceiptModal = () => {
    this.setState((prevState) => ({
      isPageReceiptModalOpened: !prevState.isPageReceiptModalOpened,
    }));
  };

  openShiprocketModal = (cb) => {
    // on open, filter any form items that are same as the shiprocket fields
    let MODIFIED_FORM_ITEMS = [...this.props.FORM_ITEMS];
    const shiprocketFieldKeys = SHIPROCKET_FORM_ITEMS.map((item) => item.name);

    MODIFIED_FORM_ITEMS = MODIFIED_FORM_ITEMS.filter((item) => {
      return shiprocketFieldKeys.indexOf(item.name) === -1;
    });

    this.setState({ formItemsBackup: this.props.FORM_ITEMS });
    this.props.replaceInFormItems(MODIFIED_FORM_ITEMS);

    this.props.setShiprocketModal(true).then(cb);
  };

  closeShiprocketModal = (isReplace) => {
    // if modal is closed, revert the form items back to the original one, remove enable modal's resize event listener
    window.removeEventListener('resize', this.debouncedHandleModalPosition);

    if (isReplace) {
      this.props.replaceInFormItems(this.state.formItemsBackup);
    }
    this.setState({ formItemsBackup: [] });
    this.props.setShiprocketModal(false);
  };

  handleShiprocketEnable = () => {
    // close modal & add Shiprocket fields to the filtered FORM_ITEMS [update store] & update SR field in redux
    window.removeEventListener('resize', this.debouncedHandleModalPosition);

    this.setState({ formItemsBackup: [] });
    this.props.setShiprocketModal(false);

    const MODIFIED_FORM_ITEMS = [...this.props.FORM_ITEMS, ...SHIPROCKET_FORM_ITEMS];
    this.props.updateData({
      settings: {
        partner_webhook_settings: {
          partner_shiprocket: '1',
        },
      },
    });
    this.props.replaceInFormItems(MODIFIED_FORM_ITEMS);
  };

  removeShiprocket = () => {
    // remove shiprocket fields from form items & update SR field in redux
    let MODIFIED_FORM_ITEMS = [...this.props.FORM_ITEMS];
    const shiprocketFieldKeys = SHIPROCKET_FORM_ITEMS.map((item) => item.name);

    MODIFIED_FORM_ITEMS = MODIFIED_FORM_ITEMS.filter(
      (item) => shiprocketFieldKeys.indexOf(item.name) === -1,
    );
    this.props.updateData({
      settings: {
        partner_webhook_settings: {
          partner_shiprocket: '0',
        },
      },
    });
    this.props.replaceInFormItems(MODIFIED_FORM_ITEMS);
    this.closeShiprocketModal();
  };

  debouncedHandleModalPosition = debounce(() => this.handleModalPosition(), 100);

  handleModalPosition = () => {
    const shiprocketFormPreviewElement = document.querySelector(
      '.Modal-container--Paymentpage-shiprocket-fields-preview',
    );
    const addNewFieldsElement = document.querySelector('.Field-add-new');

    if (addNewFieldsElement) {
      const rect = addNewFieldsElement.getBoundingClientRect();

      if (shiprocketFormPreviewElement) {
        shiprocketFormPreviewElement.style.top = `${rect.top - 360 - 32}px`; // 360px of blank preview + 32px for top padding
        shiprocketFormPreviewElement.style.left = `${rect.left - 32}px`; // 32px padding (left & right)
        shiprocketFormPreviewElement.style.width = `${rect.width + 64}px`;
      }
    }
  };

  handleShiprocket = (enablePPClose) => {
    // if PP Settings modal is going to remain closed after SR modal is open, the SR modal code needs to be in Wysiwyg file
    const { paymentPageEntity } = this.props;

    const isShiprocket =
      paymentPageEntity.settings?.partner_webhook_settings?.partner_shiprocket === '1';

    enablePPClose && this.togglePageSettings();

    if (isShiprocket) {
      // turning SR off
      this.context.confirm({
        header: 'Address fields will be removed from this page',
        className: 'shiprocket-confirm-modal',
        message:
          'If you proceed, a few fields that were previously added to collect customer’s shipping address will be removed.',
        affirmativeLabel: 'Continue',
        action: () => {
          this.removeShiprocket();
        },
        abort: () => {
          this.closeShiprocketModal();
        },
      });
    } else {
      // turning SR on

      // open modal & modify layout to show SR form fields
      // TODO: If form fields are out of the screen, then the preview modal will not be visible (out of screen)
      // window.scrollTo({ top: 0, behavior: 'smooth' });
      this.openShiprocketModal(() => {
        this.handleModalPosition();

        window.addEventListener('resize', this.debouncedHandleModalPosition);
      });
    }
  };

  render() {
    const {
      isPageReady,
      isPageLoadError,
      onSvelteAppMount,
      merchant_tnc,
      isMerchantDataLoaded,
    } = this.state;
    const { paymentPageEntity, id: payment_page_id, user, FORM_ITEMS } = this.props;

    const isShiprocket =
      paymentPageEntity.settings?.partner_webhook_settings?.partner_shiprocket === '1';

    let isAllowedToSubmit, actionBtns, themeColor, content;

    const merchantData = {
      name: this.props.user.billing_label || this.props.user.name,
      brand_color:
        this.props.config.brand_color || this.props.org.merchant_styles?.checkout_theme_color,
      image: this.props.user.logo_url,
      tnc_link: merchant_tnc ? merchant_tnc.link : null,
    };

    if (paymentPageEntity) {
      isAllowedToSubmit = paymentPageEntity.title;

      actionBtns = (
        <React.Fragment>
          {user.isPaymentPageReceiptsEnabled && (
            <Button.Transparent
              type="button"
              style={{ color: '#fff' }}
              onClick={this.togglePageReceiptModal}
              className="Button--header"
            >
              <i className="i i-receipt" />
              <span>Payment Receipts</span>
            </Button.Transparent>
          )}

          <Button.Transparent
            type="button"
            style={{ color: '#fff' }}
            onClick={this.togglePageSettings}
            className="Button--header"
          >
            <i className="i i-settings-outline" />
            <span>Page Settings</span>
          </Button.Transparent>
          <AsyncBtn.Primary
            onClick={() => {
              this.handleSavePublish(
                payment_page_id ? 'Save and Update Page' : 'Create and Publish Page',
              );
            }}
            disabled={!isAllowedToSubmit}
            pendingState="Publishing"
            class="hidden-xs"
          >
            {payment_page_id ? 'Save and Update Page' : 'Create and Publish Page'}
          </AsyncBtn.Primary>
          {/* floating container for actions in mobile view */}
          <MobileActionButtons
            handlePublishPage={() => this.handleSavePublish('Publish Page')}
            title={paymentPageEntity.title}
            supportEmail={paymentPageEntity.support_email}
            supportContact={paymentPageEntity.support_contact}
          />
        </React.Fragment>
      );

      if (paymentPageEntity.settings) {
        themeColor = paymentPageEntity.settings.theme === 'dark' ? '#383838' : '#efefef';
      }
    }

    const pageNavTitle = payment_page_id ? (
      <React.Fragment>
        Edit Payment Page <span> - {payment_page_id}</span>
      </React.Fragment>
    ) : (
      'Create New Payment Page'
    );

    if (isPageLoadError) {
      if (isPageLoadError === ERROR.SCRIPT) {
        content = (
          <div class="page-center">Some network error has occurred. Please reload the page.</div>
        );
      } else if (isPageLoadError === ERROR.INVALID_ENTITY) {
        content = (
          <div class="page-center">
            Payment page with id <b>{payment_page_id}</b> doesn&apos;t exist.
            <br />
            Go to <Link to="/paymentpages/">Payment Pages list</Link>{' '}
          </div>
        );
      }
    } else if (isPageReady && isMerchantDataLoaded) {
      content = (
        <React.Fragment>
          {onSvelteAppMount && !user.logo_url && <MerchantLogoTooltip />}

          <Svelte
            payment_page_id={payment_page_id}
            isTestMode={this.props.mode.toLowerCase() === 'test'}
            merchantData={merchantData}
            onMount={this.initSubApps}
          />
        </React.Fragment>
      );
    }

    return (
      <div
        id="paymentpage-container"
        class={classList(
          'payment-pages-v2',
          'payment-pages-v3',
          user.isPPDonationGoalTracker ? 'paymentpage-container-goal-tracker' : '',
          isMobileDevice() ? '' : 'desktop-view',
        )}
        style={{ backgroundColor: themeColor }}
      >
        {this.state.isTemplatesViewOpened && (
          <TemplatesMask
            onClose={this.handleIntroClose}
            selectTemplate={this.props.updateTemplateType}
          />
        )}

        {this.props.isSettingsOpened && (
          <PPSettingsView
            handleClose={this.togglePageSettings}
            openModal={this.props.openModal}
            paymentPageEntity={paymentPageEntity}
            handleAction={this.handleSaveSettings}
            isNew={this.props.id}
            isTestMode={this.props.mode.toLowerCase() === 'test'}
            handleShiprocket={this.handleShiprocket}
            isShiprocket={isShiprocket}
            isPPShiprocket={user.isPPShiprocket}
          />
        )}

        {this.state.isPageReceiptModalOpened && (
          <PaymentReceipt
            paymentPageEntity={paymentPageEntity}
            formItems={FORM_ITEMS}
            handleClose={this.togglePageReceiptModal}
            handleSave={this.handleSavePaymentReceipt}
            trackingDetails={{
              isPaymentPage: true,
            }}
          />
        )}

        {this.props.isShiprocketOpened && (
          <ShiprocketConfirmation
            handleClose={this.closeShiprocketModal.bind(null, true)}
            handleConfirm={this.handleShiprocketEnable}
          />
        )}

        <Header
          title={pageNavTitle}
          actionBtns={actionBtns}
          isPageReady={isPageReady}
          handleClose={this.handleClose}
        >
          <SubscriptionButtonLaunchFullPageBanner productName="PaymentPages-Create" />
        </Header>
        {content}
      </div>
    );
  }
}

const Header = ({ title, actionBtns, handleClose, isPageReady, children }) => {
  return (
    <div class="page-nav-container">
      {children}
      <div class="page-nav">
        <div class="page-size">
          <div class="page-title">{title}</div>

          {isPageReady && !!actionBtns && <div class="page-action">{actionBtns}</div>}

          {isPageReady && !!handleClose && (
            <span class="close-btn" onClick={handleClose}>
              ×
            </span>
          )}
        </div>
      </div>
    </div>
  );
};

function noop() {}

function pruneGoalTracker(goal_tracker) {
  const newGoalTracker = { ...goal_tracker };
  if (newGoalTracker.meta_data) {
    if (newGoalTracker.meta_data.hasOwnProperty('sold_units')) {
      delete newGoalTracker.meta_data.sold_units;
    }
    if (newGoalTracker.meta_data.hasOwnProperty('supporter_count')) {
      delete newGoalTracker.meta_data.supporter_count;
    }
    if (newGoalTracker.meta_data.hasOwnProperty('collected_amount')) {
      delete newGoalTracker.meta_data.collected_amount;
    }
    if (newGoalTracker.meta_data.hasOwnProperty('goal_end_timestamp_formatted')) {
      delete newGoalTracker.meta_data.goal_end_timestamp_formatted;
    }
  }
  return newGoalTracker;
}
