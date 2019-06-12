import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import Form from 'component/Form';
import Spinner from 'rzp/ui/Spinner';
import { ModalAsideNav } from 'component/Wizard';
import Button, { AsyncBtn } from 'component/Button';
import { Modal, ModalContent } from 'component/Modal';

import { fetchPlans } from 'merchant/modules/plans';
import { fetchItems } from 'merchant/modules/items';
import {
  updateSubscription,
  fetchSubscription,
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
      loading: true,
      currentTab: 0,
      validTabs: [false, false],
      fields: {
        charge_at: null,
        current_end: null,
        current_start: null,
        expire_by: null,
        plan_id: null,
        quantity: null,
        total_count: null,
        type: null,
        schedule_change_at: null,
      },
      previousSubscription: {},
      internals: {},
    };
  }

  componentWillMount() {
    this.props.fetchPlans({ count: 100 });
    this.props.fetchItems({ count: 100, type: 'invoice' });
    this.fetchSubscription(this.props.id);
  }

  fetchSubscription = (id = this.props.id) => {
    this.props
      .fetchSubscription(id)
      .then(resp => {
        this.setState({
          fields: {
            id,
            type: resp.type,
            plan_id: resp.plan_id,
            quantity: resp.quantity,
            start_at: resp.start_at,
            charge_at: resp.charge_at,
            expire_by: resp.expire_by,
            total_count: resp.total_count,
            current_end: resp.current_end,
            current_start: resp.current_start,
          },
          previousSubscription: {
            ...resp,
          },
          internals: {
            _startsImmediately: !resp.start_at,
          },
          loading: false,
        });
      })
      .catch(({ errors }) => {
        this.setState({
          loading: false,
        });

        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  changeTab = step => _ => {
    const currentTab = this.state.currentTab + step;

    const validTabs = [...this.state.validTabs];
    validTabs[this.state.currentTab] = true;

    this.setState({ currentTab, validTabs });
  };

  isFormChanged() {
    const {
      fields,
      previousSubscription,
      internals: { _startsImmediately },
    } = this.state;

    return (
      previousSubscription.plan_id !== fields.plan_id ||
      previousSubscription.quantity !== fields.quantity ||
      (previousSubscription.start_at && _startsImmediately) ||
      previousSubscription.start_at !== fields.start_at ||
      previousSubscription.total_count !== fields.total_count
    );
  }

  isFormValid = () => {
    const { fields, internals } = this.state;
    const validateTotalCount = (this.planDetailsForm || {}).validateTotalCount;

    return (
      this.isFormChanged() &&
      fields.schedule_change_at &&
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
    const { fields, internals, previousSubscription } = this.state;

    const data = {
      id: previousSubscription.id,
    };

    if (previousSubscription.plan_id !== fields.plan_id) {
      data.plan_id = fields.plan_id;
    }

    if (previousSubscription.quantity !== fields.quantity) {
      data.quantity = fields.quantity;
    }

    if (previousSubscription.total_count !== fields.total_count) {
      data.total_count = fields.total_count;
    }

    if (
      previousSubscription.start_at &&
      previousSubscription.start_at !== fields.start_at
    ) {
      data.start_at = fields.start_at;
    }

    if (
      previousSubscription.start_at &&
      internals._startsImmediately &&
      previousSubscription.status === 'created'
    ) {
      data.start_at = null;
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
    if (this.state.loading) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    switch (this.state.currentTab) {
      case 0: {
        return (
          <PlanDetails
            plans={this.props.plans}
            onChangeInPlan={this.handleChangeInPlan}
            onDateChange={this.handleDateChange}
            onTimeChange={this.handleTimeChange}
            onRadioChange={this.handleRadioChange}
            fields={this.state.fields}
            internals={this.state.internals}
            ref={form => (this.planDetailsForm = form)}
          />
        );
      }
      case 1: {
        return (
          <Review
            fields={this.state.fields}
            internals={this.state.internals}
            plans={this.props.plans.items}
            previousSubscription={this.state.previousSubscription}
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
