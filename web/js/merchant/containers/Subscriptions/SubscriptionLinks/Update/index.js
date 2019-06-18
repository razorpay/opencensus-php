import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import Form from 'component/Form';
import Spinner from 'rzp/ui/Spinner';
import { ModalAsideNav } from 'component/Wizard';
import Button, { AsyncBtn } from 'component/Button';
import { Modal, ModalContent } from 'component/Modal';

import { findBy } from 'rzp/utils/rzp-utils';

import { fetchPlans } from 'merchant/modules/plans';
import { fetchItems } from 'merchant/modules/items';
import {
  fetchSubscription,
  updateSubscription,
  fetchScheduledChanges,
} from 'merchant/modules/subscriptions';

import { showNotification } from 'rzp/modules/notifications';

import { stringToObj } from 'common/util';

import Review from './Review';
import PlanDetails from './PlanDetails';

@withRouter
@connect(
  state => ({
    plans: state.plans,
    items: state.items,
  }),
  {
    fetchPlans,
    fetchItems,
    updateSubscription,
    showNotification,
    fetchSubscription,
  }
)
export default class UpdateSubscription extends Component {
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
    };
  }

  componentWillMount = async () => {
    await this.props.fetchPlans({ count: 100 });
    await this.props.fetchItems({ count: 100, type: 'invoice' });
    await this.fetchSubscription(this.props.id);
  };

  fetchSubscription = (id = this.props.id) => {
    this.props
      .fetchSubscription(id)
      .then(resp => {
        if (resp.has_scheduled_changes) {
          return fetchScheduledChanges(id);
        }

        return resp;
      })
      .then(resp => {
        const fields = {
          id,
          plan_id: resp.plan_id,
          quantity: resp.quantity,
          start_at: resp.start_at,
          total_count: resp.total_count,
        };

        if (['authenticated', 'active', 'created'].includes(resp.status)) {
          fields.schedule_change_at = 'now';
        }

        const selectedPlan = findBy(this.props.plans.items, 'id', resp.plan_id);

        this.setState({
          fields,
          isLoading: false,
          prevSubscription: {
            ...resp,
          },
          currency: selectedPlan.item.currency,
          internals: {
            _startsImmediately: !resp.start_at,
          },
        });
      })
      .catch(({ errors }) => {
        this.setState({
          isLoading: false,
        });

        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  changeTab = step => () => {
    const currentTab = this.state.currentTab + step;

    const validTabs = [...this.state.validTabs];
    validTabs[this.state.currentTab] = true;

    this.setState({ currentTab, validTabs });
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
      prevSubscription.plan_id !== fields.plan_id ||
      prevSubscription.quantity !== fields.quantity ||
      (prevSubscription.start_at && _startsImmediately) ||
      prevSubscription.start_at !== fields.start_at ||
      prevSubscription.total_count !== fields.total_count
    );
  }

  isFormValid = () => {
    const { fields, internals } = this.state;
    const validateTotalCount = (this.planDetailsForm || {}).validateTotalCount;

    return (
      this.isFormChanged() &&
      (!!fields.plan_id &&
        (internals._startsImmediately || !!fields.start_at) &&
        (validateTotalCount ? !validateTotalCount(fields.total_count) : true))
    );
  };

  handleChangeIn = ({ target }) => {
    let value = target.value;
    const name = target.name || target.dataset.name;
    const stateKey = target.name ? 'fields' : 'internals';
    let values = { ...this.state[stateKey] };

    if (name.match(/_time/)) {
      return;
    } else if (target.type === 'number') {
      value = Number(value);
    } else if (target.type === 'checkbox') {
      value = target.checked;
    }

    values = stringToObj(name, value, values);

    this.setState({ [stateKey]: values });
  };

  handleChangeInPlan = ({ option }) => {
    this.setState({
      fields: {
        ...this.state.fields,
        plan_id: option.id,
      },
    });
  };

  handleDateChange = fieldName => selectedDate => {
    selectedDate.startOf('day');

    const current = this.state.fields[fieldName]
      ? moment(this.state.fields[fieldName], 'X')
      : 0;
    const time = current
      ? Number(current.format('X')) - Number(current.startOf('day').format('X'))
      : 0;

    const target = {
      name: fieldName,
      value: Number(selectedDate.format('X')) + time,
    };
    this.handleChangeIn({ target });
  };

  handleTimeChange = fieldName => selectedDate => {
    const time =
      Number(selectedDate.format('X')) -
      Number(selectedDate.startOf('day').format('X'));
    fieldName = fieldName.replace('_time', '');

    let current = this.state.fields[fieldName];
    // adding time to current day
    current = Number(
      moment(current, 'X')
        .startOf('day')
        .format('X')
    );
    const target = {
      name: fieldName,
      value: current + time,
    };
    this.handleChangeIn({ target });
  };

  handleRadioChange = e => {
    this.setState({
      fields: {
        ...this.state.fields,
        [e.target.name]: e.target.value,
      },
    });
  };

  prepareForSave = () => {
    const { fields, prevSubscription } = this.state;

    const data = {
      id: prevSubscription.id,
    };

    if (prevSubscription.plan_id !== fields.plan_id) {
      data.plan_id = fields.plan_id;
    }

    if (prevSubscription.quantity !== fields.quantity) {
      data.quantity = fields.quantity;
    }

    if (prevSubscription.total_count !== fields.total_count) {
      data.total_count = fields.total_count;
    }

    if (prevSubscription.start_at !== fields.start_at) {
      data.start_at = fields.start_at;
    }

    if (fields.schedule_change_at) {
      data.schedule_change_at = fields.schedule_change_at;
    }

    return data;
  };

  handleCreate = () => {
    const data = this.prepareForSave();

    return this.props
      .updateSubscription(data)
      .then(data => {
        if (data) {
          this.props.showNotification({
            type: 'success',
            message: 'Subscription Updates Successfully',
          });

          if (this.props.onClose) return this.props.onClose();

          const entityId = data.id;
          const redirectUrl = '/subscriptions/' + entityId;

          this.props.history.push(redirectUrl);
        }
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  renderForm = () => {
    if (this.state.isLoading) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    switch (this.state.currentTab) {
      case 0: {
        const filteredPlans = this.props.plans;

        filteredPlans.items = filteredPlans.items.filter(
          plan => plan.item.currency === this.state.currency
        );

        return (
          <PlanDetails
            plans={filteredPlans}
            fields={this.state.fields}
            internals={this.state.internals}
            onDateChange={this.handleDateChange}
            onTimeChange={this.handleTimeChange}
            onRadioChange={this.handleRadioChange}
            onChangeInPlan={this.handleChangeInPlan}
            ref={form => (this.planDetailsForm = form)}
            status={this.state.prevSubscription.status}
          />
        );
      }
      case 1: {
        return (
          <Review
            fields={this.state.fields}
            internals={this.state.internals}
            plans={this.props.plans.items}
            prevSubscription={this.state.prevSubscription}
          />
        );
      }
    }
  };

  renderWizard() {
    const { currentTab } = this.state;
    const isLastTab = currentTab === tabs.length - 1;
    const currentTabMeta = tabsMeta[tabs[currentTab]];

    return (
      // need to improve this css styling
      <div class="PaymentLinks--Create SubscriptionLinks--update Wizard">
        {/* updates subscription link tabs */}
        <ModalAsideNav
          title="Updates Subscription"
          description={<p>Make changes to your existing subscriptions</p>}
          tabs={tabs}
          tabClickHandler={this.handleTabChange}
          activeTab={currentTab}
          tabsValidity={this.state.validTabs}
          disableTabCondition={tabIndex =>
            tabIndex !== 0 && !this.state.validTabs[tabIndex - 1]
          }
        />
        <main class="form-container">
          <div class="title">
            <strong>{currentTabMeta.title}</strong>
          </div>
          <div class="description large">{currentTabMeta.desc}</div>
          <Form
            class="PaymentLinks--Create--Form"
            layout="tabular"
            onChange={this.handleChangeIn}
          >
            {this.renderForm()}
          </Form>
        </main>
        <footer>
          {currentTab > 0 && (
            <Button onClick={this.changeTab(-1)} type="button">
              Previous
            </Button>
          )}
          {!isLastTab ? (
            <Button.Primary
              onClick={this.changeTab(1)}
              type="button"
              disabled={!this.isFormValid()}
            >
              Next
            </Button.Primary>
          ) : (
            <AsyncBtn.Primary
              pendingState="Updating..."
              type="submit"
              onClick={this.handleCreate}
            >
              Update Subscription Link
            </AsyncBtn.Primary>
          )}
        </footer>
      </div>
    );
  }

  render() {
    const isModalView = this.props.onClose;

    return isModalView ? (
      <Modal
        class="UpdateSubscriptionLink animate-down"
        onClose={this.props.onClose}
      >
        <ModalContent>{this.renderWizard({ isModalView })}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">
        {this.renderWizard({ isModalView })}
      </div>
    );
  }
}

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
