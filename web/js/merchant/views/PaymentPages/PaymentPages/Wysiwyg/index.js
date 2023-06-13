/* eslint-disable react/no-unsafe */
import React, { Suspense } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router';
import { connect } from 'react-redux';
import ReactDOM from 'react-dom';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Loader from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/components/Loader';
import lazy from 'merchant/routes/LazyLoader';
import Svelte from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/Svelte';
import DetailsSection from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/DetailsSection';
import FormSection from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection';
import TemplatesMask from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/Templates';
import PPSettingsView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Settings';
import PaymentReceipt from 'merchant/views/PaymentPages/PaymentPages/components/Modals/PaymentReceipt';
import ShiprocketConfirmation from 'merchant/views/PaymentPages/PaymentPages/components/Modals/ShiprocketConfirmation';
import MerchantLogoTooltip from 'merchant/views/PaymentPages/PaymentPages/components/MerchantLogoTooltip';
import Header from 'merchant/views/PaymentPages/PaymentPages/components/Header';
import MobileActionButtons from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/components/MobileActionButtons';

import {
  createPaymentPage,
  editPaymentPage,
  setReceiptDetails,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';
import { isMobileDevice } from 'merchant/components/Home/data';
import debounce from 'common/utils/debounce';
import { dispatchWebViewEvent } from 'common/utils/reactNativeWebView';

import {
  autoPrefixUrls,
  getURLQueryParams,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
  classList,
} from 'common/utils/rzp-utils';

import {
  initDefaultFormItems,
  fetchPaymentPage,
  updateData,
  refreshPageData,
  markDataSaved,
  updateTemplateType,
  updateReceiptDetails,
  setSettingsModal,
  replaceInFormItems,
  setShiprocketModal,
  updateMagicData,
  setIsBatchPaymentPages,
} from 'merchant/reducers/wysiwyg';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

// TODO: Change validation logic as per V2 / V3. (Ensure that "settings" is not considered in comparison of keys)
import {
  validateUISchema,
  SHIPROCKET_FORM_ITEMS,
  checkIsMagicCheckoutField,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';
import {
  trackWYSIWYGCloseIntent,
  trackConfirmWYSIWYGCloseIntent,
} from 'merchant/views/PaymentPages/PaymentPages/ga';
import {
  convertSinglePriceFieldToMandatory,
  isFormItemOfTypeAmount,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import { transfeeRuleToApiFormat } from 'merchant/views/PaymentPages/PaymentPages/helpers';
import { checkBatchPaymentPages } from 'merchant/views/PaymentPages/PaymentPages/utils';

import { DEFAULT_RULE } from 'merchant/views/MagicCheckout/constants';
import { FIXED_FIELDS } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers/preAddedFields';

const MagicCheckoutEnabledModal = lazy(() =>
  import(
    /* webpackChunkName: "MagicCheckoutEnabledModal" */ 'merchant/views/PaymentPages/PaymentPages/components/Modals/MagicCheckout/MagicCheckoutEnabledModal'
  ),
);

const MagicCheckoutFormModal = lazy(() =>
  import(
    /* webpackChunkName: "MagicCheckoutFormModal" */ 'merchant/views/PaymentPages/PaymentPages/components/Modals/MagicCheckout/MagicCheckoutFormModal'
  ),
);

const MagicShiprocketModal = lazy(() =>
  import(
    /* webpackChunkName: "MagicShiprocketModal" */ 'merchant/views/PaymentPages/PaymentPages/components/Modals/MagicCheckout/MagicShiprocketModal'
  ),
);

const MagicSettingsModal = lazy(() =>
  import(
    /* webpackChunkName: "MagicSettingsModal" */ 'merchant/views/PaymentPages/PaymentPages/components/Modals/MagicCheckout/MagicSettingsModal'
  ),
);

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
    isWebView: state.app.isWebView,
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
    updateMagicData,
    setIsBatchPaymentPages,
  },
)
@RTracking(() => window.rzpQ.component('PaymentPagesWysiwyg'))
export default class PaymentPagesWysiwyg extends React.PureComponent {
  _isMounted = true;
  static contextTypes = {
    confirm: PropTypes.func,
  };

  isIntentDuplicate = false;
  supportPhoneRef = React.createRef();
  supportEmailRef = React.createRef();
  _isMounted = true;

  state = {
    isPageReady: false,
    isTemplatesViewOpened: !this.props.id, // isTemplatesViewOpened = false if editing existing Payment page
    onSvelteAppMount: false,
    formItemsBackup: [],
    isEntityLoaded: false,
    isMagicSettingsModalOpen: false,
    magicFeeRule: { ...DEFAULT_RULE },
    isMagicCheckoutEnabled: false,
  };

  UNSAFE_componentWillMount() {
    const { id, setIsBatchPaymentPages } = this.props;
    this.fetchEntity(id, true);

    // Preload Social media image
    const socialMediaIcons = new Image();
    socialMediaIcons.src = 'https://cdn.razorpay.com/static/assets/social-share/icons.png';

    this.fetchIfIntentDuplicate();
    const isBatchPaymentPages = checkBatchPaymentPages();
    // set the batch pp identifier
    isBatchPaymentPages && setIsBatchPaymentPages(true);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
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
    const { isMagicCheckoutEnabled } = this.state;
    // Update the magic checkout component state only if it is a magic payment page.
    if (
      this.props.magicCheckout?.enabled !== nextProps.magicCheckout?.enabled ||
      (nextProps.magicCheckout?.enabled && !isMagicCheckoutEnabled)
    ) {
      const { magicCheckout } = nextProps;
      const { enabled, feeRule } = magicCheckout;
      this.setState({
        isMagicCheckoutEnabled: enabled,
        magicFeeRule: { ...feeRule },
      });
    }
  }

  UNSAFE_componentWillUpdate(nextProps) {
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
    this.setState({ isEntityLoaded: false });
    const promise = this.props.fetchPaymentPage(id, this.isIntentDuplicate); // Auto reinitialise store if id doesn't exist.

    if (promise instanceof Promise) {
      // edit & duplicate case
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
            } else if (searchParams.modal === 'shiprocket') {
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
        })
        .finally(() => {
          this.setState({ isEntityLoaded: true });
        });
    } else {
      // create case
      this.setState({ isEntityLoaded: true });
    }
  };

  componentDidMount() {
    const {
      isBatchPaymentPages,
      initDefaultFormItems,
      id,
      user,
      updateTemplateType,
      tracking,
      isWebView,
    } = this.props;
    const isEditExistingId = !!id;
    // if create flow & storefront enabled, then preselect the empty template
    if (!isEditExistingId && user.isPaymentPageStorefrontEnabled) {
      updateTemplateType(null, 'custom');
    }

    // i18n: This will update the merchant currency in redux store.
    this.props.updateData(null, false, this.props.user.merchant.currency);
    this.props.initDefaultFormItems();
    initDefaultFormItems(isBatchPaymentPages);

    track.init(tracking.trackEvent, {
      payment_page_id: id,
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

    //dispatching event to tell mobile app to hide header in creation flow
    isWebView && dispatchWebViewEvent({ eventType: 'HIDE_HEADER' });
  }

  componentWillUnmount() {
    this._isMounted = false;

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

    //dispatching event to tell mobile app to show header again as exiting creation flow
    this.props.isWebView && dispatchWebViewEvent({ eventType: 'SHOW_HEADER' });

    this.props.updateMagicData({
      enabled: false,
      feeRule: DEFAULT_RULE,
    });
    this._isMounted = false;
  }

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
    const { user, isBatchPaymentPages } = this.props;
    ReactDOM.render(
      <DetailsSection
        supportEmailRef={this.supportEmailRef}
        supportPhoneRef={this.supportPhoneRef}
        isBatchPaymentPages={isBatchPaymentPages}
      />,
      document.getElementById('details-section'),
    );
    ReactDOM.render(
      <FormSection
        hideDynamicPriceField={user.hideDynamicPriceFieldPP}
        isBatchPaymentPages={isBatchPaymentPages}
      />,
      document.getElementById('form-section'),
    );

    this.setState({
      onSvelteAppMount: true,
    });
  };

  // Update settings in store
  handleSaveSettings = (formData) => {
    const { showNotification, user } = this.props;
    const data = {};
    if (!user?.isNoExpiryMandatoryPP && !formData?.expire_by) {
      showNotification({
        type: 'error',
        message: 'Expire By is mandatory!',
      });
      return;
    }
    data.expire_by = formData.expire_by;

    /*
      - While creation, if a slug has not been entered, the slug key is not sent in the payload in the normal flow
        (pages.razorpay.com). Backend automatically generates a slug in that case.
      - In the custom domain flow, the user can have an empty string as slug to use the root domain, hence
        explicitly sending an empty string in the slug in that case.
    */
    if (formData.slug || formData.domainType === 'custom') {
      data.slug = (formData.slug || '').trim();
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

    data.settings.custom_domain =
      formData.domainType === 'custom' ? this.props.customDomain.value : '';

    // Update in store
    this.props.updateData(data);

    this.props.setSettingsModal(false);
  };

  handlePluginsAndAddOnsSave = (data) => {
    this.props.updateData({ settings: data });
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
    const {
      paymentPageEntity,
      FORM_ITEMS,
      magicCheckout,
      user,
      showNotification,
      isBatchPaymentPages,
    } = this.props;
    const { isMagicCheckoutLive, isPaymentPageMagicEnabled, isNoExpiryMandatoryPP } = user;
    const { enabled: magicEnabled, feeRule: magicFeeRule } = magicCheckout;
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
    let paymentPageItems = [];

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
            amount: amount ? i18CurrencyConversionFromCommonUnitToMinorUnit(amount) : null, // Convert in paisa (smaller unit)
          },
          settings, // Contains position
          image_url,
          mandatory,
          min_purchase,
          max_purchase,
          min_amount: min_amount
            ? i18CurrencyConversionFromCommonUnitToMinorUnit(min_amount)
            : null,
          max_amount: max_amount
            ? i18CurrencyConversionFromCommonUnitToMinorUnit(max_amount)
            : null,
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
    if (isBatchPaymentPages) {
      const errorMessages = [];
      if (!paymentPageItems.length) {
        errorMessages.push(`${errorMessages.length + 1} : Add at least 1 Price field`);
      } else {
        const mandatoryPriceFeilds = paymentPageItems?.filter((item) => item?.mandatory);
        if (mandatoryPriceFeilds.length === 0) {
          errorMessages.push(
            `${
              errorMessages.length + 1
            } : Please add at least 1 Price field with ‘Make it Optional Item’ not selected.`,
          );
        }
      }

      const primaryRefIdFeilds = FORM_ITEMS?.filter(
        (item) => item?.name === 'pri__ref__id' && item?.pattern === 'alphanumeric',
      );
      if (primaryRefIdFeilds.length === 0) {
        errorMessages.push(
          `${errorMessages.length + 1} : Add at least 1 Primary reference ID field`,
        );
      }

      if (primaryRefIdFeilds.length > 1) {
        errorMessages.push(
          `${errorMessages.length + 1} : Only 1 Input Field may be added as Primary Reference ID`,
        );
      }

      if (errorMessages.length > 0) {
        showNotification({
          type: 'error',
          message: errorMessages,
        });

        return;
      }
    }
    // before saving, if there is only one price field, we are marking it as mandatory. (for UX reasons on hosted pages)
    paymentPageItems = convertSinglePriceFieldToMandatory(paymentPageItems);

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

    if (!isNoExpiryMandatoryPP && !expire_by) {
      showNotification({
        type: 'error',
        message: 'Expire By is mandatory!',
      });
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

    if (isMagicCheckoutLive && isPaymentPageMagicEnabled && (magicEnabled || isEditExistingId)) {
      reqPayload.settings.one_click_checkout = magicEnabled ? '1' : '0';
      reqPayload.settings.shipping_fee_rule = transfeeRuleToApiFormat(magicFeeRule);
    }
    // Send template type in while creation
    if (!isEditExistingId) {
      reqPayload.template_type = template_type;
    }

    reqPayload.settings.checkout_options = {
      email: settings.checkout_options?.email || FIXED_FIELDS.email.name,
      phone: settings.checkout_options?.phone || FIXED_FIELDS.phone.name,
    };

    if (this.props.user.isPaymentPageCustomDomainEnabled) {
      reqPayload.settings.custom_domain = settings.custom_domain;
    }

    reqPayload.settings.payment_button_label = settings.payment_button_label;
    reqPayload.payment_page_items = paymentPageItems;
    if (isBatchPaymentPages) {
      reqPayload.view_type = 'file_upload_page';
    }

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

              this.onSaveSuccessActions(resp);
            })
            .catch(() => {
              this.onSaveSuccessActions(resp);
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

  onSaveSuccessActions = (resp) => {
    const { isBatchPaymentPages, history, markDataSaved } = this.props;
    markDataSaved();
    this.isIntentDuplicate = false;

    const entityId = resp.data.id;
    const url = isBatchPaymentPages
      ? `/paymentpages/batchpaymentpages/${entityId}/batchuploadsubpage`
      : `/paymentpages/${entityId}/success`;
    history.push(url);
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

  removeMagicCheckoutFields = () => {
    const { FORM_ITEMS, replaceInFormItems, updateMagicData, magicCheckout } = this.props;
    const { prevAddedFields } = magicCheckout;
    const magicFields = [];
    const modifiedFormFields = [];

    FORM_ITEMS.forEach((item) => {
      if (item?.name && checkIsMagicCheckoutField(item.name)) {
        magicFields.push(item);
      } else {
        modifiedFormFields.push(item);
      }
    });

    /*
     * when magic checkout is enabled, we need to remove magic checkout(address, email, phone)
     * related fields if they are already present.
     */
    if (magicFields.length && !prevAddedFields?.length) {
      updateMagicData({ prevAddedFields: magicFields });
    }

    replaceInFormItems(modifiedFormFields);
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

  openMagicShiprocketModal = (isEnabled) => {
    const { closeModal, openModal } = this.props;
    openModal({
      size: 'small',
      component: (
        <Suspense fallback={<Loader />}>
          <MagicShiprocketModal onClose={closeModal} isEnabled={isEnabled} />
        </Suspense>
      ),
    });
  };

  handleShiprocketEnable = () => {
    // close modal & add Shiprocket fields to the filtered FORM_ITEMS [update store] & update SR field in redux
    window.removeEventListener('resize', this.debouncedHandleModalPosition);

    const { setShiprocketModal, updateData, FORM_ITEMS, replaceInFormItems, magicCheckout } =
      this.props;
    this.setState({ formItemsBackup: [] });
    setShiprocketModal(false);

    updateData({
      settings: {
        partner_webhook_settings: {
          partner_shiprocket: '1',
        },
      },
    });
    if (magicCheckout?.enabled) {
      this.openMagicShiprocketModal(true);
    } else {
      const MODIFIED_FORM_ITEMS = [...FORM_ITEMS, ...SHIPROCKET_FORM_ITEMS];
      replaceInFormItems(MODIFIED_FORM_ITEMS);
    }

    track.settings.clickShiprocketEnableConfirm();
  };

  removeShiprocket = () => {
    const { FORM_ITEMS, replaceInFormItems, updateData, updateMagicData, magicCheckout } =
      this.props;
    const { prevAddedFields: magicPrevAddedFields } = magicCheckout;
    // remove shiprocket fields from form items & update SR field in redux
    let MODIFIED_FORM_ITEMS = [...FORM_ITEMS];
    const shiprocketFieldKeys = SHIPROCKET_FORM_ITEMS.map((item) => item.name);

    /*
     * when magic checkout & shiprocket are both enabled and user tries to disable
     * shiprocket only, then we need to remove shiprocket related fields from prevAddedFields.
     */
    if (magicPrevAddedFields.length) {
      const prevAddedFields = [];
      magicPrevAddedFields.forEach((item) => {
        if (item?.name && !shiprocketFieldKeys.includes(item.name)) {
          prevAddedFields.push(item);
        }
      });
      updateMagicData({ prevAddedFields });
    }
    MODIFIED_FORM_ITEMS = MODIFIED_FORM_ITEMS.filter(
      (item) => shiprocketFieldKeys.indexOf(item.name) === -1,
    );
    updateData({
      settings: {
        partner_webhook_settings: {
          partner_shiprocket: '0',
        },
      },
    });
    replaceInFormItems(MODIFIED_FORM_ITEMS);
    this.closeShiprocketModal();

    track.settings.clickShiprocketDisableConfirm();
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
    const { paymentPageEntity, magicCheckout } = this.props;

    const isShiprocket =
      paymentPageEntity.settings?.partner_webhook_settings?.partner_shiprocket === '1';

    enablePPClose && this.togglePageSettings();

    if (isShiprocket) {
      // turning SR off

      if (magicCheckout?.enabled) {
        this.removeShiprocket();
        this.openMagicShiprocketModal();
      } else {
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
      }

      track.settings.clickShiprocketDisable();
    } else if (!magicCheckout?.enabled) {
      // turning SR on

      // open modal & modify layout to show SR form fields
      // TODO: If form fields are out of the screen, then the preview modal will not be visible (out of screen)
      // window.scrollTo({ top: 0, behavior: 'smooth' });
      this.openShiprocketModal(() => {
        this.handleModalPosition();

        window.addEventListener('resize', this.debouncedHandleModalPosition);
      });
      track.settings.clickShiprocketEnable();
    } else {
      this.handleShiprocketEnable();
    }
  };

  resetMagicCheckout = () => {
    const { updateMagicData } = this.props;
    const rule = { ...DEFAULT_RULE };
    updateMagicData({
      enabled: false,
      feeRule: rule,
    });
    this.setState({
      magicFeeRule: rule,
      isMagicCheckoutEnabled: false,
    });
  };

  getModifiedFormItems = () => {
    const { FORM_ITEMS, magicCheckout, updateMagicData } = this.props;
    const { prevAddedFields } = magicCheckout;
    const fieldNameList = prevAddedFields.map(({ name }) => name);

    // After magic checkout enabled, if user adds a field with address related field label, we will be removing that field.
    let duplicateFieldInd;
    FORM_ITEMS?.forEach(({ name }, index) => {
      if (fieldNameList.includes(name)) {
        duplicateFieldInd = index;
      }
    });
    if (typeof duplicateFieldInd !== 'undefined') {
      FORM_ITEMS.splice(duplicateFieldInd, 1);
    }
    const modifiedFormItems = [...FORM_ITEMS, ...prevAddedFields];
    updateMagicData({ prevAddedFields: [] });
    return modifiedFormItems;
  };

  addPrevMagicFormItems = () => {
    const { replaceInFormItems } = this.props;
    replaceInFormItems(this.getModifiedFormItems());
  };

  closeMagicEnabledModal = () => {
    const { closeModal } = this.props;
    this.resetMagicCheckout();
    closeModal();
  };

  onMagicEnabledModalContinue = () => {
    const { closeModal } = this.props;
    this.removeMagicCheckoutFields();
    closeModal();
  };

  onMagicFormModalContinue = () => {
    this.removeMagicCheckoutFields();
    this.props.updateMagicData({ formModalOpen: false });
  };

  toggleMagicCheckout = () => {
    this.setState((prevState) => ({
      isMagicCheckoutEnabled: !prevState.isMagicCheckoutEnabled,
    }));
  };

  toggleMagicSettingsModal = () => {
    this.setState((prevState) => ({
      isMagicSettingsModalOpen: !prevState.isMagicSettingsModalOpen,
    }));
  };

  saveMagicSettings = () => {
    const { paymentPageEntity, replaceInFormItems, updateMagicData } = this.props;
    const { magicFeeRule, isMagicCheckoutEnabled } = this.state;

    updateMagicData({
      enabled: isMagicCheckoutEnabled,
      feeRule: { ...magicFeeRule },
    });
    this.toggleMagicSettingsModal();

    if (isMagicCheckoutEnabled) {
      const { FORM_ITEMS, openModal } = this.props;

      // When we enable magic checkout for the first time, MagicCheckoutEnabledModal will be shown.
      openModal({
        size: 'small',
        component: (
          <Suspense fallback={<Loader />}>
            <MagicCheckoutEnabledModal
              formField={FORM_ITEMS}
              onContinue={this.onMagicEnabledModalContinue}
              closeModal={this.closeMagicEnabledModal}
            />
          </Suspense>
        ),
      });
    }
    if (
      paymentPageEntity?.settings?.partner_webhook_settings?.partner_shiprocket === '1' &&
      !isMagicCheckoutEnabled
    ) {
      const MODIFIED_FORM_ITEMS = this.getModifiedFormItems();
      const formFieldKeys = MODIFIED_FORM_ITEMS.map((item) => item?.name);
      SHIPROCKET_FORM_ITEMS.forEach((item) => {
        if (!formFieldKeys.includes(item.name)) {
          MODIFIED_FORM_ITEMS.push(item);
        }
      });
      replaceInFormItems(MODIFIED_FORM_ITEMS);
    } else if (!isMagicCheckoutEnabled) {
      this.addPrevMagicFormItems();
    }
  };

  cancelMagicSettings = () => {
    const { magicCheckout } = this.props;
    const { enabled, feeRule } = magicCheckout;
    this.setState({
      magicFeeRule: { ...feeRule },
      isMagicCheckoutEnabled: enabled,
    });
    this.toggleMagicSettingsModal();
  };

  updateRule = (_, value) => {
    const rule = {
      ...DEFAULT_RULE,
      ...value,
    };
    this.setState({ magicFeeRule: rule });
  };

  render() {
    const {
      isPageReady,
      isPageLoadError,
      onSvelteAppMount,
      isEntityLoaded,
      isMagicSettingsModalOpen,
      magicFeeRule,
      isMagicCheckoutEnabled,
    } = this.state;

    const {
      paymentPageEntity,
      id: payment_page_id,
      user,
      FORM_ITEMS,
      magicCheckout,
      isBatchPaymentPages,
    } = this.props;
    const createButtonText = isBatchPaymentPages
      ? 'Save and Proceed to Next Step'
      : payment_page_id
      ? 'Save and Update Page'
      : 'Create and Publish Page';
    const {
      isMagicCheckoutLive,
      isPaymentPageMagicEnabled,
      isNoExpiryMandatoryPP,
      showCustomTemplatePP,
      isPaymentPageStorefrontEnabled,
    } = user;
    const isShiprocket =
      paymentPageEntity?.settings?.partner_webhook_settings?.partner_shiprocket === '1';

    let isAllowedToSubmit, actionBtns, themeColor, content;

    const merchantData = {
      name: this.props.user.billing_label || this.props.user.name,
      brand_color:
        this.props.config.brand_color || this.props.org.merchant_styles?.checkout_theme_color,
      image: this.props.user.logo_url,
    };

    if (paymentPageEntity) {
      isAllowedToSubmit = paymentPageEntity.title;

      if (!isPageLoadError && isPageReady) {
        actionBtns = (
          <React.Fragment>
            {isMagicCheckoutLive && isPaymentPageMagicEnabled && (
              <Button.Transparent
                type="button"
                style={{ color: '#fff' }}
                onClick={this.toggleMagicSettingsModal}
                className="Button--header magic-link"
                disabled={!isEntityLoaded}
              >
                <i className="i i-magic-checkout" />
                <span>Magic Checkout Settings</span>
                <span className="new-label">New</span>
              </Button.Transparent>
            )}
            {user.isPaymentPageReceiptsEnabled && (
              <Button.Transparent
                type="button"
                style={{ color: '#fff' }}
                onClick={this.togglePageReceiptModal}
                className="Button--header"
                disabled={!isEntityLoaded}
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
              disabled={!isEntityLoaded}
            >
              <i className="i i-settings-outline" />
              <span>Page Settings</span>
            </Button.Transparent>
            <AsyncBtn.Primary
              onClick={() => {
                return this.handleSavePublish(
                  payment_page_id ? 'Save and Update Page' : 'Create and Publish Page',
                );
              }}
              disabled={!isAllowedToSubmit || !isEntityLoaded}
              pendingState="Publishing"
              class="hidden-xs"
            >
              {createButtonText}
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
      }

      if (paymentPageEntity.settings) {
        themeColor = paymentPageEntity.settings.theme === 'dark' ? '#383838' : '#efefef';
      }
    }

    const pageNavTitle = payment_page_id ? (
      <React.Fragment>
        Edit Payment Page <span> - {payment_page_id}</span>
      </React.Fragment>
    ) : (
      `Create New Payment Page${isBatchPaymentPages ? ' (Step 1/2)' : ''}`
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
    } else if (isPageReady) {
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
          'paymentpage-container-goal-tracker',
          isMobileDevice() ? '' : 'desktop-view',
        )}
        style={{ backgroundColor: themeColor }}
      >
        {!isPaymentPageStorefrontEnabled && this.state.isTemplatesViewOpened && (
          <TemplatesMask
            onClose={this.handleIntroClose}
            selectTemplate={this.props.updateTemplateType}
            showCustomTemplate={showCustomTemplatePP}
            isBatchPaymentPages={isBatchPaymentPages}
          />
        )}

        {isMagicSettingsModalOpen && (
          <Suspense fallback={<Loader />}>
            <MagicSettingsModal
              closeModal={this.cancelMagicSettings}
              magicFeeRule={magicFeeRule}
              updateRule={this.updateRule}
              isMagicCheckoutEnabled={isMagicCheckoutEnabled}
              toggleMagicCheckout={this.toggleMagicCheckout}
              handleSubmit={this.saveMagicSettings}
              isEditPaymentPage={payment_page_id}
            />
          </Suspense>
        )}

        {magicCheckout?.formModalOpen && (
          <Suspense fallback={<Loader />}>
            <MagicCheckoutFormModal onClose={this.onMagicFormModalContinue} />
          </Suspense>
        )}

        {this.props.isSettingsOpened && (
          <PPSettingsView
            handleClose={this.togglePageSettings}
            openModal={this.props.openModal}
            closeModal={this.props.closeModal}
            paymentPageEntity={paymentPageEntity}
            handleAction={this.handleSaveSettings}
            onPluginsAndAddOnsSave={this.handlePluginsAndAddOnsSave}
            isNew={this.props.id}
            isTestMode={this.props.mode.toLowerCase() === 'test'}
            handleShiprocket={this.handleShiprocket}
            isShiprocket={isShiprocket}
            customDomain={this.props.customDomain}
            isNoExpiryMandatory={isNoExpiryMandatoryPP}
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
          handleClose={this.handleClose}
          isSticky
        />
        {content}
      </div>
    );
  }
}

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
