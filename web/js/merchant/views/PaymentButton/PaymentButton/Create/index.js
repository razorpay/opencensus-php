import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { Link } from 'react-router-dom';
import Button from 'common/new-ui/Button';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import Spinner from 'common/ui/Spinner';

import TopBar from './components/TopBar';
import PaymentReceipt from 'merchant/views/PaymentPages/PaymentPages/components/Modals/PaymentReceipt';
import TemplatesMask from './components/Templates';
import SideBar from './components/SideBar';
import Form from './components/Form';
import Preview from './components/Preview';
import SettingsModal from 'merchant/views/PaymentButton/PaymentButton/components/SettingsModal';
import SuccessView from 'merchant/views/PaymentButton/PaymentButton/components/SuccessView';

import {
  fetchPaymentButtonDetails,
  updateReceiptDetails,
  updatePaymentButtonData,
  updateTemplateType,
  resetPageData,
  updateHighlightButtonSettings,
} from 'merchant/reducers/paymentbuttons/create';
import { validateUISchema } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';

import {
  createPaymentPage as createPaymentButton,
  editPaymentPage as editPaymentButton,
  setReceiptDetails,
} from 'merchant/views/PaymentPages/PaymentPages/model';

import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { getURLQueryParams, rupeesToPaise } from 'common/utils/rzp-utils';

import track from './track';
import track_details from 'merchant/views/PaymentButton/PaymentButton/Details/track';

const docTitles = {
  DEFAULT: 'Razorpay Dashboard',
  CREATE: 'Create New Payment Button',
  EDIT: 'Edit Payment Button',
};

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    org: state.session.org,
    payment_button: state.payment_button_create,
  }),
  {
    resetPageData,
    fetchPaymentButtonDetails,
    updateTemplateType,
    updateReceiptDetails,
    updatePaymentButtonData,
    closeModal,
    openModal,
    showNotification,
    updateHighlightButtonSettings,
  },
)
@RTracking(() => window.rzpQ.component('PaymentButtonCreate'))
export default class PaymentButtonCreate extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  isIntentDuplicate = false;

  state = {
    isTemplatesSelectionOpened: !this.paymentButtonId,
    isPageReceiptModalOpened: false,
    isSuccessViewOpened: false,
    isSuccessViewOpenedForExistingId: false,
    activeTabIndex: 0,
    isEntityLoaded: false,
  };

  UNSAFE_componentWillMount() {
    // Always reset the data initially
    if (!this.props.id) {
      this.resetPageData();

      this.setState({
        isTemplatesSelectionOpened: true, // It'll automatically become false if it's fetchIfIntentDuplicate is true
      });
    }

    if (this.props.id) {
      this.fetchDetails(this.props.id);
    } else {
      this.fetchIfIntentDuplicate();
    }

    this.initTracker();
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.closeModal();

      this.setState({
        isTemplatesSelectionOpened: false,
        isPageReceiptModalOpened: false,
      });

      if (!nextProps.id) {
        this.resetPageData();
        this.setState({ isTemplatesSelectionOpened: true });

        this.fetchIfIntentDuplicate();
      } else {
        this.resetPageData();
        this.fetchDetails(nextProps.id);
      }

      this.initTracker(nextProps);
    } else if (!nextProps.id && this.props.id != nextProps.id) {
      // Handle only when both are not /new
      const searchQuery = getURLQueryParams(this.props.location.search);
      const searchQueryNext = getURLQueryParams(nextProps.location.search);

      // Handle moving to '/new'
      if (!nextProps.id && !searchQueryNext.duplicate_id) {
        this.resetPageData();
      } else if (
        // Handle moving to different '?duplicate_id'
        !nextProps.id &&
        searchQueryNext.duplicate_id &&
        searchQuery.duplicate_id != searchQueryNext.duplicate_id
      ) {
        this.fetchIfIntentDuplicate(searchQueryNext.duplicate_id);
      }

      this.initTracker(nextProps);
    }
  }

  componentWillUnmount() {
    setWindowTitle(docTitles.DEFAULT); // Revert title of dashboard
    this.props.closeModal();
  }

  // Tracker initialization

  initTracker = (props = this.props) => {
    const { tracking, location, id } = props;

    const searchQuery = getURLQueryParams(location.search);

    const isNew = !(id || searchQuery.duplicate_id);

    const paymentButtonId = id || searchQuery.duplicate_id;

    const config = {
      is_new: isNew,
      payment_button_id: paymentButtonId,
      is_intent_edit: !!id,
      is_intent_duplicate: !!searchQuery.duplicate_id,
    };

    track.init(tracking.trackEvent, config);
    track_details.init(tracking.trackEvent, paymentButtonId);
  };

  /*
   *
   * Api methods
   *
   * */

  fetchDetails = (id) => {
    this.setState({ isEntityLoaded: false });
    const promise = this.props.fetchPaymentButtonDetails(id, this.isIntentDuplicate); // Auto reinitialise store if id doesn't exist.

    if (promise instanceof Promise) {
      // edit or duplicate case
      promise
        .then(({ data }) => {
          if (data) {
            setWindowTitle(`${docTitles.EDIT} - ${this.paymentButtonId}`);
          }
        })
        .finally(() => this.setState({ isEntityLoaded: true }));
    } else {
      // create case
      this.setState({ isEntityLoaded: true });
    }
  };

  fetchIfIntentDuplicate(entityId) {
    const searchQuery = getURLQueryParams(this.props.location.search);
    const entityIdToDuplicate = entityId || searchQuery.duplicate_id;

    if (entityIdToDuplicate) {
      this.isIntentDuplicate = true;

      // Don't show templates screen if intent is to duplicate
      this.setState({
        isTemplatesSelectionOpened: false,
      });

      this.fetchDetails(entityIdToDuplicate);
    } else {
      // create flow (during any life cycle)
      this.setState({
        isEntityLoaded: true,
      });
      setWindowTitle(docTitles.CREATE);
    }
  }

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

  // TODO: Split handleSavePaymentButton() into 2 parts, one as handler and other to just make api call

  /*
   *
   * Action handler methods
   *
   * */

  handleTogglePageReceiptModal = () => {
    if (!this.state.isPageReceiptModalOpened) {
      track_details.paymentReceiptsOpen();
    }
    this.setState((prevState) => ({
      isPageReceiptModalOpened: !prevState.isPageReceiptModalOpened,
    }));
  };

  handleClose = () => {
    this.context.confirm({
      header: 'Go back to Dashboard',
      message: () => (
        <div class="text-semi-muted">
          <p>Unsaved changes will be lost. Do you want to continue?</p>
        </div>
      ),
      affirmativeLabel: 'Yes',
      abortLabel: 'Cancel',
      action: () => {
        this.props.history.push(`/paymentbuttons/`);
      },
    });
  };

  // Update settings in store
  handleSaveSettings = (formData) => {
    const requestAPIPromise = editPaymentButton(this.paymentButtonId, formData);

    return requestAPIPromise
      .then((resp) => {
        if (resp.data) {
          this.props.showNotification({
            type: 'success',
            message: 'Button settings are updated successfully',
          });

          this.props.updatePaymentButtonData(formData);
        } else {
          throw new Error(resp.errors);
        }

        return resp;
      })
      .catch(({ errors }) => {
        let err = errors;

        if (!err) {
          err = 'Some network error has occured';
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  };

  // eslint-disable-next-line consistent-return
  handleSavePaymentReceipt = (data) => {
    const isEditExistingId = !!this.paymentButtonId;

    this.props.updateReceiptDetails(data); // Updating in the store

    if (isEditExistingId) {
      return this.saveReceiptSettings(this.paymentButtonId, data).then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Receipt settings are updated successfully',
        });
      });
    }
  };

  handleSavePaymentButton = () => {
    const isEditExistingId = !!this.paymentButtonId;
    const { paymentButtonEntity, amountFields, udfFields } = this.props.payment_button;
    const currency = paymentButtonEntity.currency;

    const udfSchema = [];
    const paymentPageItems = [];

    // 1. Validate UDF schema
    const isValidSchema = validateUISchema(udfFields);

    if (!isValidSchema) {
      this.props.showNotification({
        type: 'error',
        message: 'Customer details fields are of invalid format',
      });

      throw new Error('UI Schema is not valid'); // This scenario implies, there is some frontend issue / not user action error
    }

    // 2. Check if atleast 1 amount item is present

    if (!amountFields.length) {
      this.props.showNotification({
        type: 'error',
        message: 'Add at least 1 Amount field',
      });

      return;
    }

    // 3. Update position for each item in udf field
    udfFields.forEach((field, index) => {
      field.settings = field.settings || {};
      field.settings.position = index;

      udfSchema.push(field);
    });

    // 4. Prune amount fields
    amountFields.forEach((field, index) => {
      field.settings = field.settings || {};
      field.settings.position = index; // Updating the position of each item (both udf and amount fields)

      // Prepare payload for amount field (as extra unnecessary fields which api sends aren't required to be sent back)
      const {
        id,
        item,
        settings,
        mandatory,
        min_purchase,
        max_purchase,
        min_amount,
        max_amount,
        stock,
      } = field;

      const prunedField = {
        item: {
          name: item.name,
          description: item.description,
          amount: item.amount ? rupeesToPaise(item.amount) : null, // Convert in paisa (smaller unit)
        },
        settings, // Contains position
        mandatory,
        min_purchase,
        max_purchase,
        min_amount: min_amount ? rupeesToPaise(min_amount) : null,
        max_amount: max_amount ? rupeesToPaise(max_amount) : null,
        stock: stock ? stock : null, // stock cannot be 0 or empty string
        image_url: null,
      };

      if (isEditExistingId) {
        if (id) {
          prunedField.id = id; // IMPORTANT NOTE: Existing item must keep its id bcoz payments against items are stored against id.
        } else {
          prunedField.item.currency = currency; // currency to be added only for newly added amount items (in existing payment button)
        }
      } else {
        prunedField.item.currency = currency; // Currency cannot be edited from UI once Payment page is created
      }

      /*
      * NOTE: Since payment_page_items are not shareable items with other payment pages, therefore, currency of payment_page entity is used as single source of truth .

      * Currency of each payment_page_item is ignored in general, and is being added here only for the reason that blueprint of line_items of invoices is reused for PP in BE.
      * */

      paymentPageItems.push(prunedField);
    });

    // 5. Prepare request payload
    const reqPayload = {
      currency,
      expire_by: null,
      title: paymentButtonEntity.title,
      description: paymentButtonEntity.description || null,
      terms: null,
      support_email: null,
      support_contact: null,
      settings: {
        payment_button_label: '',
        theme: 'light', // Should be empty, but api not supporting
        allow_social_share: '0',
        payment_success_message: paymentButtonEntity.settings.payment_success_message,
        payment_success_redirect_url: '', // settings.payment_success_redirect_url,
        udf_schema: JSON.stringify(udfSchema),
        checkout_options: {
          ...paymentButtonEntity.settings.checkout_options,
        },
        payment_button_text: paymentButtonEntity.settings.payment_button_text,
        payment_button_theme: paymentButtonEntity.settings.payment_button_theme,
      },
      payment_page_items: paymentPageItems,
      slug: paymentButtonEntity.slug, // Will be undefined if so
    };

    // Template can be sent only while creation
    if (!isEditExistingId) {
      reqPayload.settings.payment_button_template_type =
        paymentButtonEntity.settings.payment_button_template_type;
    }

    const requestAPIPromise = isEditExistingId
      ? editPaymentButton(this.paymentButtonId, reqPayload)
      : createPaymentButton(reqPayload, { view_type: 'button' });

    // Note: Receipt call is made after main api call, bcoz they modify same entity in DB table which gets locked, so parallel calls might fail.

    // eslint-disable-next-line consistent-return
    return requestAPIPromise
      .then((resp) => {
        if (resp.data) {
          const entityId = resp.data.id;

          // In case of edit, receipt will be directly updated from modal's Save button
          if (!isEditExistingId) {
            const receipt = paymentButtonEntity.receipt;

            this.saveReceiptSettings(entityId, receipt)
              .then(() => {
                this.onSaveSuccessActions(resp, isEditExistingId);
              })
              .catch(() => {
                this.onSaveSuccessActions(resp, isEditExistingId);
              });
          } else {
            this.onSaveSuccessActions(resp, isEditExistingId);
          }

          track.createOrEditSuccess(entityId);
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

        track.createOrEditFail(err);

        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  };

  handleCloseTemplateSelection = () => {
    this.setState({
      isTemplatesSelectionOpened: false,
    });
  };

  /*
   *
   * Others
   *
   * */

  resetPageData = () => {
    this.setState({
      activeTabIndex: 0,
    });

    this.props.resetPageData();
  };

  onSaveSuccessActions = (resp, isEditExistingId) => {
    this.isIntentDuplicate = false;

    const entity = resp.data;

    this.props.history.push(`/paymentbuttons/${entity.id}/edit`);

    this.setState({
      activeTabIndex: 0,
    });

    this.setState({
      isSuccessViewOpened: true,
      isSuccessViewOpenedForExistingId: isEditExistingId,
    });
  };

  openSettingsModal = () => {
    track_details.optionsOpenSettings('create');

    const paymentButtonEntity = this.props.payment_button.paymentButtonEntity;

    this.props.openModal({
      size: 'medium',
      component: (
        <SettingsModal
          paymentSuccessMessage={paymentButtonEntity.settings.payment_success_message}
          paymentSuccessRedirectUrl={paymentButtonEntity.settings.payment_success_redirect_url}
          editPaymentButton={this.handleSaveSettings}
          track={{
            customMessage: track_details.settingsCustomMessage.bind(null, 'create'),
            closeModal: track_details.settingsCancel,
            save: track_details.settingsSave,
            saveFail: track_details.settingsSaveFail,
            customMessageCheckbox: track_details.customMessageCheckbox.bind(null, 'create'),
            redirectURLCheckbox: track_details.redirectURLCheckbox.bind(null, 'create'),
          }}
        />
      ),
    });
  };

  onChangeButtonTemplate = (templateType) => {
    this.resetPageData();
    this.props.updateTemplateType(null, templateType);
  };

  onChangeActiveTabIndex = (newIndex) => {
    this.setState({ activeTabIndex: newIndex });
  };

  get paymentButtonId() {
    const { payment_button } = this.props;

    return payment_button.paymentButtonId || this.props.id; // This payment button id can be valid / invalid.
  }

  /*
   *
   * Render helpers
   *
   * */

  get TopBar() {
    let title;

    if (this.paymentButtonId) {
      title = (
        <React.Fragment>
          Edit Payment Button <span> - {this.paymentButtonId}</span>
        </React.Fragment>
      );
    } else {
      title = 'Create New Payment Button';
    }

    return (
      <TopBar
        title={title}
        actionButtons={this.actionButtons}
        isActionsActive={true}
        handleClose={this.handleClose}
      />
    );
  }

  get actionButtons() {
    const { user, payment_button } = this.props;
    const { isEntityLoaded } = this.state;

    if (isEntityLoaded && payment_button.paymentButtonEntity === null) return '';

    const actionButtons = user.isPaymentPageReceiptsEnabled ? (
      <Button.Transparent
        type="button"
        style={{ color: '#fff' }}
        class="payment-receipt-btn"
        onClick={this.handleTogglePageReceiptModal}
        disabled={!isEntityLoaded}
      >
        <span>
          <i class="i i-document" /> Payment Receipts
        </span>
      </Button.Transparent>
    ) : null;

    return actionButtons;
  }

  get ReceiptModal() {
    const { payment_button } = this.props;

    return (
      <PaymentReceipt
        paymentPageEntity={payment_button.paymentButtonEntity}
        formItems={payment_button.udfFields}
        handleClose={this.handleTogglePageReceiptModal}
        handleSave={this.handleSavePaymentReceipt}
        saveBtnLabel={this.paymentButtonId ? 'Save & Update' : 'Save'}
        trackingDetails={{
          isPaymentPage: false,
          via: 'create',
        }}
      />
    );
  }

  get TemplateSelectionModal() {
    return (
      <TemplatesMask
        onClose={this.handleCloseTemplateSelection}
        selectTemplate={(templateKey) => {
          this.props.updateTemplateType(null, templateKey);
          track.templateSelect(templateKey);
        }}
        history={this.props.history}
      />
    );
  }

  get ErrorView() {
    return (
      <div class="page-center">
        Payment Button with id <b>{this.paymentButtonId}</b> doesn&apos;t exist.
        <br />
        Go to <Link to="/paymentbuttons/">Payment Buttons list</Link>{' '}
      </div>
    );
  }

  get NoTemplateSelectedView() {
    return <div class="page-center">No template was selected! Please reload the page.</div>;
  }

  get ContentView() {
    const { payment_button, org } = this.props;
    const {
      activeTabIndex,
      isSuccessViewOpened,
      isSuccessViewOpenedForExistingId,
      isEntityLoaded,
    } = this.state;
    const orgCustomCode = org.custom_code;

    const isEditExistingId = !!this.paymentButtonId;

    return (
      <div class="PaymentButton-Create-Content">
        <div class="PaymentButton-Create-Content-container">
          {!isEntityLoaded ? (
            <div class="page-center">
              <Spinner />
            </div>
          ) : (
            <React.Fragment
              key={`${payment_button.paymentButtonEntity.settings.payment_button_template_type}-${
                this.paymentButtonId || 'new'
              }`}
            >
              <SideBar
                {...payment_button}
                activeTabIndex={activeTabIndex}
                isSuccessViewOpened={isSuccessViewOpened}
                isSuccessViewOpenedForExistingId={isSuccessViewOpenedForExistingId}
                orgCustomCode={orgCustomCode}
              />
              {isSuccessViewOpened ? (
                <SuccessView
                  paymentButton={payment_button.paymentButtonEntity}
                  openSettingsModal={this.openSettingsModal}
                  openPageReceiptModal={this.handleTogglePageReceiptModal}
                  updateHighlightButtonSettings={this.props.updateHighlightButtonSettings}
                  onClickButtonSettings={() => {
                    track.onClickButtonSettings();
                  }}
                />
              ) : (
                <React.Fragment>
                  <Form
                    {...payment_button}
                    activeTabIndex={activeTabIndex}
                    onChangeActiveTabIndex={this.onChangeActiveTabIndex}
                    submitPaymentButtonForm={this.handleSavePaymentButton}
                    onChangeButtonTemplate={this.onChangeButtonTemplate}
                    isEditExistingId={isEditExistingId}
                  />
                  <Preview {...payment_button} activeTabIndex={activeTabIndex} />
                </React.Fragment>
              )}
            </React.Fragment>
          )}
        </div>
      </div>
    );
  }

  get PageContent() {
    const { payment_button } = this.props;

    if (payment_button.paymentButtonEntity === null) {
      return this.ErrorView;
    }

    if (
      payment_button.paymentButtonEntity.settings &&
      !payment_button.paymentButtonEntity.settings.payment_button_template_type
    ) {
      // This condition is helpful for 2 things. 1st, if somehow the template screen is skipped.
      // 2nd, Content won't be loaded until template is selected, so this way the defaultValues would have latest values as per template, rather than making them as controlled components

      if (!this.state.isTemplatesSelectionOpened) {
        return this.NoTemplateSelectedView;
      }
    } else {
      return this.ContentView;
    }
    return '';
  }

  /*
   *
   * Main render
   *
   * */

  render() {
    return (
      <div id="payment-buttons-create-container" class="desktop-view">
        {this.state.isPageReceiptModalOpened && this.ReceiptModal}
        {this.state.isTemplatesSelectionOpened && this.TemplateSelectionModal}

        {this.TopBar}

        <ErrorBoundary key={this.paymentButtonId || 'new'}>{this.PageContent}</ErrorBoundary>
      </div>
    );
  }
}

function setWindowTitle(title) {
  if (!title) {
    return;
  }

  document.title = 'Razorpay Dashboard';
}
