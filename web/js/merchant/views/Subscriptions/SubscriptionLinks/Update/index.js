import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import Form from 'common/new-ui/Form';
import Spinner from 'common/ui/Spinner';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { Modal, ModalContent } from 'common/new-ui/Modal';

import { findBy, stringToObj, classList } from 'common/utils/rzp-utils';
import DocsLink from 'merchant/components/DocsLink';

import Plan from 'merchant/models/Plan';
import { fetchPlan, fetchPlans, updatePlans } from 'merchant/reducers/plans';
import { fetchItems } from 'merchant/reducers/items';
import {
  fetchSettings,
  fetchSubscription,
  updateSubscription,
  fetchScheduledChanges,
  fetchSubscriptionOfferAPI,
} from 'merchant/reducers/subscriptions';

import { UPI_AVL_LIMIT } from 'merchant/helpers/data';
import UPIBanner from '../components/UPIBanner';

import { showNotification } from 'merchant_common/reducers/notifications';

import Review from './Review';
import PlanDetails from './PlanDetails';
import moment from 'moment';
import analytics from '../../analytics';

const tabsMeta = {
  'Subscription Details': {
    title: 'SUBSCRIPTION DETAILS',
  },
  Review: {
    title: 'REVIEW CHANGES',
    desc: 'Please review the changes applied.',
  },
};

const tabs = Object.keys(tabsMeta);

@withRouter
@connect(
  (state) => ({
    plans: state.plans,
    items: state.items,
    subscription: state.subscription,
    user: state.session.user,
  }),
  {
    fetchPlan,
    updatePlans,
    fetchPlans,
    fetchItems,
    updateSubscription,
    showNotification,
    fetchSubscription,
    fetchSettings,
  },
)
export default class UpdateSubscription extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      fields: {},
      internals: {},
      currentTab: 0,
      currency: null,
      isLoading: true,
      prevSubscription: {},
      validTabs: [false, false],
      subscriptionOffers: {
        items: [],
        loading: this.props.user.isSubscriptionOffersEnabled,
      },
    };
    this.cloneOptions = {
      clone: '0',
    };
  }

  componentWillMount = async () => {
    const plans = await this.props.fetchPlans({ count: 100 });
    await this.props.fetchItems({ count: 100, type: 'addon' });

    const { entity: subscription } = this.props.subscription;

    let isPlanExists = false;
    plans.data.items.forEach((plan) => {
      if (plan.id === subscription.plan_id) {
        isPlanExists = true;
      }
    });

    if (!isPlanExists) {
      try {
        const resp = await this.props.fetchPlan(subscription.plan_id);
        const plan = new Plan(resp);
        const updatedPlans = {
          ...this.props.plans,
          items: [...this.props.plans.items, plan],
        };

        this.props.updatePlans(updatedPlans);
      } catch (e) {
        // TODO
      }
    }

    if (this.props.user.isSubscriptionOffersEnabled) {
      fetchSubscriptionOfferAPI([subscription.payment_method]).then((resp) => {
        this.setState({
          subscriptionOffers: resp.data,
        });
      });
    }

    if (subscription.id !== this.props.id) {
      await this.fetchSubscription(this.props.id);

      return null;
    }

    if (subscription.has_scheduled_changes) {
      return this.fetchScheduledChanges(this.props.id);
    }

    this.initUpdateSubscription(subscription);
    return null;
  };

  initUpdateSubscription = (subscription) => {
    const { plans } = this.props;

    const fields = {
      id: subscription.id,
      plan_id: subscription.plan_id,
      quantity: subscription.quantity,
      start_at: subscription.start_at,
      customer_notify: subscription.customer_notify,
      offer_id: subscription.offer_id,
    };

    if (['active'].includes(subscription.status)) {
      fields.schedule_change_at = 'now';
    }

    if (['authenticated'].includes(subscription.status)) {
      fields.remaining_count = subscription.total_count;
      subscription.remaining_count = subscription.total_count;
    } else {
      fields.remaining_count = subscription.remaining_count;
    }

    const selectedPlan = findBy(plans.items, 'id', subscription.plan_id);

    this.setState({
      fields,
      isLoading: false,
      prevSubscription: {
        ...subscription,
      },
      currency: selectedPlan.item.currency,
      internals: {
        _startsImmediately: !subscription.start_at,
      },
      _selectedPlanAmount: selectedPlan.item.amount,
    });
  };

  fetchScheduledChanges = (id = this.props.id) => {
    return fetchScheduledChanges(id).then(this.initUpdateSubscription);
  };

  fetchSubscription = (id = this.props.id) => {
    this.props
      .fetchSubscription(id)
      .then((resp) => {
        if (resp.has_scheduled_changes) {
          return fetchScheduledChanges(id);
        }

        return resp;
      })
      .then(this.initUpdateSubscription)
      .catch(({ errors }) => {
        this.setState({
          isLoading: false,
        });

        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  changeTab = (step) => {
    this.setState((prevState) => {
      const currentTab = prevState.currentTab + step;
      const validTabs = [...prevState.validTabs];
      validTabs[prevState.currentTab] = true;
      return {
        currentTab,
        validTabs,
      };
    });
  };

  handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);
    this.setState({ currentTab });
  };

  isFormChanged() {
    const {
      fields,
      prevSubscription,
      internals: { _startsImmediately },
    } = this.state;

    return (
      (fields.plan_id && prevSubscription.plan_id !== fields.plan_id) ||
      (fields.quantity && prevSubscription.quantity !== fields.quantity) ||
      (prevSubscription.start_at && _startsImmediately) ||
      prevSubscription.start_at !== fields.start_at ||
      (fields.remaining_count && prevSubscription.remaining_count !== fields.remaining_count) ||
      prevSubscription.customer_notify !== fields.customer_notify ||
      prevSubscription.offer_id !== fields.offer_id
    );
  }

  isFormValid = () => {
    const { fields, internals } = this.state;
    const validateTotalCount = (this.planDetailsForm || {}).validateTotalCount;

    return (
      this.isFormChanged() &&
      !!fields.plan_id &&
      (internals._startsImmediately || !!fields.start_at) &&
      (validateTotalCount ? !validateTotalCount(fields.remaining_count) : true)
    );
  };

  handleChangeIn = ({ target }) => {
    let value = target.value;
    const name = target.name || target.dataset.name;
    const stateKey = target.name ? 'fields' : 'internals';
    this.setState((prevState) => {
      let values = { ...prevState[stateKey] };

      if (!name || name.match(/_time/)) {
        return prevState;
      } else if (target.type === 'number') {
        value = Number(value);
      } else if (target.type === 'checkbox') {
        value = target.checked;
      }

      values = stringToObj(name, value, values);
      return {
        [stateKey]: values,
      };
    });
  };

  handleChangeInPlan = ({ option }) => {
    const currSelectedPlan = findBy(this.props.plans.items, 'id', option.id);

    this.setState((prevState) => ({
      fields: {
        ...prevState.fields,
        plan_id: option.id,
      },
      _selectedPlanAmount: currSelectedPlan.item.amount,
    }));
  };

  handleDateChange = (fieldName) => (selectedDate) => {
    selectedDate.startOf('day');

    const current = this.state.fields[fieldName] ? moment(this.state.fields[fieldName], 'X') : 0;
    const time = current
      ? Number(current.format('X')) - Number(current.startOf('day').format('X'))
      : 0;

    const target = {
      name: fieldName,
      value: Number(selectedDate.format('X')) + time,
    };
    this.handleChangeIn({ target });
  };

  handleTimeChange = (fieldName) => (selectedDate) => {
    const time = Number(selectedDate.format('X')) - Number(selectedDate.startOf('day').format('X'));
    fieldName = fieldName.replace('_time', '');

    let current = this.state.fields[fieldName];
    // adding time to current day
    current = Number(moment(current, 'X').startOf('day').format('X'));

    const target = {
      name: fieldName,
      value: current + time,
    };
    this.handleChangeIn({ target });
  };

  handleRadioChange = (e) => {
    this.setState((prevState) => ({
      fields: {
        ...prevState.fields,
        [e.target.name]: e.target.value,
      },
    }));
  };

  prepareForSave = () => {
    const { fields, prevSubscription } = this.state;

    const data = {
      id: prevSubscription.id,
      remaining_count: fields.remaining_count,
    };

    if (prevSubscription.plan_id !== fields.plan_id) {
      data.plan_id = fields.plan_id;
    }

    if (prevSubscription.quantity !== fields.quantity) {
      data.quantity = fields.quantity;
    }

    if (prevSubscription.remaining_count !== fields.remaining_count) {
      data.remaining_count = fields.remaining_count;
    }

    if (prevSubscription.start_at !== fields.start_at) {
      data.start_at = fields.start_at;
    }

    if (fields.schedule_change_at) {
      data.schedule_change_at = fields.schedule_change_at;
    }

    if (prevSubscription.customer_notify != fields.customer_notify) {
      data.customer_notify = fields.customer_notify ? '1' : '0';
    }

    if (prevSubscription.offer_id != fields.offer_id) {
      data.offer_id = fields.offer_id;
    }

    return data;
  };

  handleCreate = () => {
    analytics.track('subscription.update.issue', this.cloneOptions);
    const data = this.prepareForSave();

    return this.props
      .updateSubscription(data)
      .then((subscription) => {
        if (subscription) {
          this.props.showNotification({
            type: 'success',
            message: 'Subscription Updates Successfully',
          });
          analytics.track('subscription.update.success', this.cloneOptions);

          if (this.props.onClose) return this.props.onClose();

          const redirectUrl = `/subscriptions/${subscription.id}`;

          this.props.history.push(redirectUrl);
        }
        return null;
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
        analytics.track('subscription.update.fail', this.cloneOptions);
      });
  };

  handleChangeInOffer = ({ option = {} } = {}) => {
    this.setState((prevState) => ({
      fields: {
        ...prevState.fields,
        offer_id: option.id || null,
      },
    }));
  };

  renderForm = () => {
    const { fields, currency, internals, isLoading, currentTab, prevSubscription } = this.state;

    if (isLoading) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    switch (currentTab) {
      case 0: {
        const filteredPlans = { ...this.props.plans };

        filteredPlans.items = filteredPlans.items.filter((plan) => plan.item.currency === currency);

        return (
          <PlanDetails
            fields={fields}
            plans={filteredPlans}
            internals={internals}
            status={prevSubscription.status}
            onDateChange={this.handleDateChange}
            onTimeChange={this.handleTimeChange}
            onRadioChange={this.handleRadioChange}
            onChangeInPlan={this.handleChangeInPlan}
            ref={(form) => (this.planDetailsForm = form)}
            offers={this.state.subscriptionOffers}
            showOffers={this.props.user.isSubscriptionOffersEnabled}
            onChangeInOffer={this.handleChangeInOffer}
            cloneOptions={this.cloneOptions}
          />
        );
      }
      case 1: {
        let totalCount = prevSubscription.total_count;

        if (
          prevSubscription.remaining_count !== fields.remaining_count &&
          prevSubscription.status !== 'authenticated'
        ) {
          if (prevSubscription.remaining_count < fields.remaining_count) {
            totalCount = totalCount + (fields.remaining_count - prevSubscription.remaining_count);
          } else if (prevSubscription.remaining_count > fields.remaining_count) {
            totalCount = totalCount - (prevSubscription.remaining_count - fields.remaining_count);
          }
        }

        return (
          <Review
            fields={{
              ...fields,
              total_count: totalCount,
            }}
            internals={internals}
            plans={this.props.plans.items}
            prevSubscription={prevSubscription}
          />
        );
      }
      default: {
        return (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        );
      }
    }
  };

  disableTabCondition = (tabIndex) => {
    return tabIndex !== 0 && !this.state.validTabs[tabIndex - 1];
  };

  renderWizard() {
    const { currentTab, _selectedPlanAmount } = this.state;
    const isLastTab = currentTab === tabs.length - 1;
    const currentTabMeta = tabsMeta[tabs[currentTab]];

    const showUPIUnAvlBanner = _selectedPlanAmount > UPI_AVL_LIMIT;
    return (
      // need to improve this css styling
      <div
        class={classList(
          'PaymentLinks--Create SubscriptionLinks--update Wizard',
          'upi-banner-visible',
        )}
      >
        <ModalAsideNav
          activeTab={currentTab}
          disableTabCondition={this.disableTabCondition}
          description={<p>Make changes to your existing subscriptions</p>}
          tabs={tabs}
          title="Update Subscription"
          tabsValidity={this.state.validTabs}
          tabClickHandler={this.handleTabChange}
        />
        <main class="form-container">
          <div class="title">
            <strong>{currentTabMeta.title}</strong>
          </div>
          <div class="description large">{currentTabMeta.desc}</div>
          <Form class="PaymentLinks--Create--Form" layout="tabular" onChange={this.handleChangeIn}>
            {this.renderForm()}
          </Form>
        </main>
        <footer>
          {this.props.user.isCardRecurringPaymentsBlocked && (
            <div
              class={classList('card-blocked-banner', showUPIUnAvlBanner && 'upi-banner-visible')}
            >
              <i class="i i-info-circle" /> Cards issued by Indian banks are temporarily disabled
              for new subscriptions.{' '}
              <DocsLink
                url="https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/recurring-payments"
                title="Learn more"
              />
            </div>
          )}
          {showUPIUnAvlBanner && <UPIBanner />}
          {currentTab > 0 && (
            <Button
              onClick={() => {
                analytics.track('subscription.update.previous', this.cloneOptions);
                this.changeTab(-1);
              }}
              type="button"
            >
              Previous
            </Button>
          )}
          {!isLastTab ? (
            <Button.Primary
              onClick={() => {
                analytics.track('subscription.update.next', this.cloneOptions);
                this.changeTab(1);
              }}
              type="button"
              disabled={!this.isFormValid()}
            >
              Next
            </Button.Primary>
          ) : (
            <AsyncBtn.Primary pendingState="Updating..." type="submit" onClick={this.handleCreate}>
              Update Subscription
            </AsyncBtn.Primary>
          )}
        </footer>
      </div>
    );
  }

  render() {
    const isModalView = this.props.onClose;

    if (isModalView) {
      return (
        <Modal class="UpdateSubscriptionLink animate-down" onClose={this.props.onClose}>
          <ModalContent>{this.renderWizard({ isModalView })}</ModalContent>
        </Modal>
      );
    }

    return <div class="StandAloneContainer">{this.renderWizard({ isModalView })}</div>;
  }
}
