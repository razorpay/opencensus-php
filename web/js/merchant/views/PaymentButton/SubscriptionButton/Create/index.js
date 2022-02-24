import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { Link } from 'react-router-dom';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import Spinner from 'common/ui/Spinner';

import TopBar from './components/TopBar';
import PaymentReceipt from 'merchant/views/PaymentPages/PaymentPages/components/Modals/PaymentReceipt';
import SideBar from './components/SideBar';
import Form from './components/Form';
import Preview from './components/Preview';
import SuccessModal from '../components/SuccessModal';

import {
  fetchSubscriptionButtonDetails,
  updateReceiptDetails,
  resetPageData,
  updateHighlightButtonSettings,
} from 'merchant/reducers/subscriptionButtons/create';
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

const docTitles = {
  DEFAULT: 'Razorpay Dashboard',
  CREATE: 'Create New Subscription Button',
  EDIT: 'Edit Subscription Button',
};

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    subscription_button: state.subscription_button_create,
  }),
  {
    resetPageData,
    fetchSubscriptionButtonDetails,
    updateReceiptDetails,
    closeModal,
    openModal,
    showNotification,
    updateHighlightButtonSettings,
  },
)
@RTracking(() => window.rzpQ.component('SubscriptionButtonCreate'))
export default class SubscriptionButtonCreate extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  isIntentDuplicate = false;

  state = {
    isPageReceiptModalOpened: false,
    activeTabIndex: 0,
  };

  UNSAFE_componentWillMount() {
    // Always reset the data initially
    if (!this.props.id) {
      this.resetPageData();
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
        isPageReceiptModalOpened: false,
      });

      if (!nextProps.id) {
        this.resetPageData();

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

    const config = {
      is_new: isNew,
      subscription_button_id: id || searchQuery.duplicate_id,
      is_intent_edit: !!id,
      is_intent_duplicate: !!searchQuery.duplicate_id,
    };

    track.lj.init(tracking.trackEvent, config);
  };

  /*
   *
   * Api methods
   *
   * */

  fetchDetails = (id) => {
    const promise = this.props.fetchSubscriptionButtonDetails(id, this.isIntentDuplicate); // Auto reinitialise store if id doesn't exist.

    if (promise instanceof Promise) {
      promise
        .then(({ data }) => {
          if (data) {
            setWindowTitle(`${docTitles.EDIT} - ${this.subscriptionButtonId}`);
          }
        })
        .catch(() => {});
    }
  };

  fetchIfIntentDuplicate(entityId) {
    const searchQuery = getURLQueryParams(this.props.location.search);
    const entityIdToDuplicate = entityId || searchQuery.duplicate_id;

    if (entityIdToDuplicate) {
      this.isIntentDuplicate = true;

      this.fetchDetails(entityIdToDuplicate);
    } else {
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
        this.props.history.push(`/subscription_buttons/`);
      },
    });
  };

  // eslint-disable-next-line consistent-return
  handleSavePaymentReceipt = (data) => {
    const isEditExistingId = !!this.subscriptionButtonId;

    this.props.updateReceiptDetails(data); // Updating in the store

    if (isEditExistingId) {
      return this.saveReceiptSettings(this.subscriptionButtonId, data).then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Receipt settings are updated.',
        });
      });
    }
  };

  handleSavePaymentButton = () => {
    const isEditExistingId = !!this.subscriptionButtonId;
    const { subscriptionButtonEntity, paymentFields, udfFields } = this.props.subscription_button;
    const currency = subscriptionButtonEntity.currency;

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

    if (!paymentFields.length) {
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
    paymentFields.forEach((field, index) => {
      field.settings = field.settings || {};
      field.settings.position = index; // Updating the position of each item (both udf and amount fields)

      // Prepare payload for amount field (as extra unnecessary fields which api sends aren't required to be sent back)
      const { id, plan_id, settings } = field;

      const commonKeysInField = {
        mandatory: false,
        settings, // Contains position
        image_url: null,
      };

      let specificKeysInField;

      if (plan_id) {
        const { product_config } = field;

        specificKeysInField = {
          plan_id,
          product_config: {
            subscription_details: {
              total_count: product_config.subscription_details.total_count,
            },
          },
        };
      } else {
        const { item } = field;

        specificKeysInField = {
          item: {
            name: item.name,
            description: item.description,
            amount: item.amount ? rupeesToPaise(item.amount) : null, // Convert in paisa (smaller unit)
          },
        };

        if (isEditExistingId) {
          if (id) {
            specificKeysInField.id = id; // IMPORTANT NOTE: Existing item must keep its id bcoz payments against items are stored against id.
          } else {
            specificKeysInField.item.currency = currency; // currency to be added only for newly added amount items (in existing subscription button)
          }
        } else {
          specificKeysInField.item.currency = currency; // Currency cannot be edited from UI once Payment page is created
        }
      }

      const prunedField = { ...commonKeysInField, ...specificKeysInField };

      paymentPageItems.push(prunedField);
    });

    // 5. Prepare request payload
    const reqPayload = {
      currency,
      expire_by: null,
      title: subscriptionButtonEntity.title,
      description: subscriptionButtonEntity.description || null,
      terms: null,
      support_email: null,
      support_contact: null,
      settings: {
        payment_button_label: '', // Used only in hosted page
        theme: 'light', // Should be empty, but api not supporting
        allow_social_share: '0',
        payment_success_message: subscriptionButtonEntity.settings.payment_success_message,
        payment_success_redirect_url: '', // settings.payment_success_redirect_url,
        udf_schema: JSON.stringify(udfSchema),
        checkout_options: {
          ...subscriptionButtonEntity.settings.checkout_options,
        },
        payment_button_text: subscriptionButtonEntity.settings.payment_button_text,
        payment_button_theme: subscriptionButtonEntity.settings.payment_button_theme,
        payment_button_template_type: '',
      },
      payment_page_items: paymentPageItems,
      slug: subscriptionButtonEntity.slug, // Will be undefined if so
    };

    const requestAPIPromise = isEditExistingId
      ? editPaymentButton(this.subscriptionButtonId, reqPayload)
      : createPaymentButton(reqPayload, { view_type: 'subscription_button' });

    // Note: Receipt call is made after main api call, bcoz they modify same entity in DB table which gets locked, so parallel calls might fail.

    // eslint-disable-next-line consistent-return
    return requestAPIPromise
      .then((resp) => {
        if (resp.data) {
          const entityId = resp.data.id;

          // In case of edit, receipt will be directly updated from modal's Save button
          if (!isEditExistingId) {
            const receipt = subscriptionButtonEntity.receipt;

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

          track.lj.trackCreateOrEditSuccess();
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

        track.lj.trackCreateOrEditFail(err);

        this.props.showNotification({
          type: 'error',
          message: err,
        });
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

    this.props.history.push(`/subscription_buttons/${entity.id}/edit`);

    this.setState({
      activeTabIndex: 0,
    });

    this.openSuccessView(isEditExistingId, entity);
  };

  openSuccessView = (isEditExistingId, subscriptionButtonEntity) => {
    track.lj.trackShowCode(subscriptionButtonEntity.id);

    const modalContent = (
      <SuccessModal
        isEditExistingId={isEditExistingId}
        paymentButton={subscriptionButtonEntity}
        updateHighlightButtonSettings={this.props.updateHighlightButtonSettings}
        onCodeCopy={() => track.lj.trackCodeCopy(subscriptionButtonEntity.id)}
        onClickSeeDocumentation={() => track.lj.trackOpenDocs(subscriptionButtonEntity.id)}
        onClickButtonSettings={() => {
          track.lj.trackOnClickButtonSettings();
        }}
      />
    );

    this.props.openModal({
      size: 'medium',
      className: 'GetCodeModal',
      component: modalContent,
    });
  };

  onChangeActiveTabIndex = (newIndex) => {
    this.setState({ activeTabIndex: newIndex });
  };

  get subscriptionButtonId() {
    const { subscription_button } = this.props;

    return subscription_button.subscriptionButtonId || this.props.id; // This subscription button id can be valid / invalid.
  }

  /*
   *
   * Render helpers
   *
   * */

  get TopBar() {
    let title;

    if (this.subscriptionButtonId) {
      title = (
        <React.Fragment>
          Edit Subscription Button <span> - {this.subscriptionButtonId}</span>
        </React.Fragment>
      );
    } else {
      title = 'Create New Subscription Button';
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
    const actionButtons = (
      <React.Fragment>
        {/*
        {user.isPaymentPageReceiptsEnabled && (
          <Button.Transparent
            type="button"
            style={{ color: '#fff' }}
            class="payment-receipt-btn"
            onClick={this.handleTogglePageReceiptModal}
          >
            <span>
              <i class="i i-document" /> Payment Receipts
            </span>
          </Button.Transparent>
        )}
      */}
      </React.Fragment>
    );

    return actionButtons;
  }

  get ReceiptModal() {
    const { subscription_button } = this.props;

    return (
      <PaymentReceipt
        paymentPageEntity={subscription_button.subscriptionButtonEntity}
        formItems={subscription_button.udfFields}
        handleClose={this.handleTogglePageReceiptModal}
        handleSave={this.handleSavePaymentReceipt}
        saveBtnLabel={this.subscriptionButtonId ? 'Save & Update' : 'Save'}
        trackingDetails={{
          isPaymentPage: false,
          via: 'create',
        }}
      />
    );
  }

  get ErrorView() {
    return (
      <div class="page-center">
        Subscription Button with id <b>{this.subscriptionButtonId}</b> doesn't exist.
        <br />
        Go to <Link to="/subscription_buttons/">Subscription Buttons list</Link>{' '}
      </div>
    );
  }

  get ContentView() {
    const { subscription_button } = this.props;
    const { activeTabIndex } = this.state;

    const isPageLoading =
      this.subscriptionButtonId && !subscription_button.subscriptionButtonEntity.title;

    return (
      <div class="PaymentButton-Create-Content">
        <div class="PaymentButton-Create-Content-container SubscriptionButton-Create-Content-container">
          {isPageLoading ? (
            <div class="page-center">
              <Spinner />
            </div>
          ) : (
            <React.Fragment key={this.subscriptionButtonId || 'new'}>
              <SideBar {...subscription_button} activeTabIndex={activeTabIndex} />
              <Form
                {...subscription_button}
                activeTabIndex={activeTabIndex}
                onChangeActiveTabIndex={this.onChangeActiveTabIndex}
                submitPaymentButtonForm={this.handleSavePaymentButton}
                isEditExistingId={!!this.subscriptionButtonId}
              />
              <Preview {...subscription_button} activeTabIndex={activeTabIndex} />
            </React.Fragment>
          )}
        </div>
      </div>
    );
  }

  get PageContent() {
    const { subscription_button } = this.props;

    if (subscription_button.subscriptionButtonEntity === null) {
      return this.ErrorView;
    }

    return this.ContentView;
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

        {this.TopBar}

        <ErrorBoundary key={this.subscriptionButtonId || 'new'}>{this.PageContent}</ErrorBoundary>
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
