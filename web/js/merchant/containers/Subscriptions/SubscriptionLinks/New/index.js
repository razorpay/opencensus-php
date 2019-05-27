import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { fetchPlans } from 'merchant/modules/plans';
import { fetchItems } from 'merchant/modules/items';
import { saveSubscription } from 'merchant/modules/subscriptions';
import { showNotification } from 'rzp/modules/notifications';

import { ModalAsideNav } from 'component/Wizard';
import { Modal, ModalContent } from 'component/Modal';
import Form from 'component/Form';
import Button, { AsyncBtn } from 'component/Button';

import { stringToObj } from 'common/util';
import { isPresent, findBy } from 'rzp/utils/rzp-utils';

import AddOnDetails from './AddOnDetails';
import LinkDetails from './LinkDetails';
import PlanDetails from './PlanDetails';
import Review from './Review';

@withRouter
@connect(
  state => ({
    plans: state.plans,
    items: state.items,
    user: state.session.user,
  }),
  { fetchPlans, fetchItems, saveSubscription, showNotification }
)
export default class NewSubscriptionLink extends Component {
  state = {
    currentTab: 0,
    validTabs: [false, false, false, false],
    fields: {
      quantity: 1,
      addons: [],
    },
    internals: {},
  };

  componentWillMount() {
    this.props.fetchPlans({ count: 100 });
    this.props.fetchItems({ count: 100, type: 'invoice' });
  }

  handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);
    this.setState({ currentTab });
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

    this.setState({ [stateKey]: values }, () => {
      if (name === '_addOnPresent') {
        this.setState({
          fields: {
            ...this.state.fields,
            addons: target.checked ? [{}] : [],
          },
        });
      }
    });
  };

  handleChangeInPlan = ({ option }) => {
    const { currencyOfSelectedPlan, fields, internals } = this.state;

    const currSelectedPlan = findBy(this.props.plans.items, 'id', option.id);

    if (
      currSelectedPlan &&
      currSelectedPlan.item.currency !== currencyOfSelectedPlan
    ) {
      if (internals._addOnPresent) {
        this.props.showNotification({
          type: 'neutral',
          message:
            'Currency of Plan is changed. Please select the Add Ons again',
          closeTimeout: 8000,
        });

        this.setState({
          currencyOfSelectedPlan: option.currency,
          fields: {
            ...fields,
            plan_id: option.id,
            addons: [{}],
          },
        });

        return;
      }

      this.setState({
        currencyOfSelectedPlan: option.currency,
        fields: {
          ...fields,
          plan_id: option.id,
        },
      });

      return;
    }

    this.setState({
      currencyOfSelectedPlan: option.currency,
      fields: {
        ...fields,
        plan_id: option.id,
      },
    });
  };

  handleSelectItem = addonIndex => ({ option }) => {
    const fields = { ...this.state.fields };
    fields.addons[addonIndex] = {
      item: {
        name: option.name,
        description: option.description,
        amount: option.amount,
        currency: option.currency,
        type: 'addon',
      },
      quantity: 1,
    };
    this.setState({
      fields,
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

  handleAddaddon = () => {
    const fields = { ...this.state.fields };
    fields.addons.push({});
    this.setState({ fields });
  };

  handleCreate = () => {
    let { fields: data, internals } = this.state;
    data = { ...data };

    if (internals._startsImmediately) {
      delete data.start_at;
    }

    if (internals._isNonExpiringLink) {
      delete data.expire_by;
    }

    if (!data.customer_notify) {
      delete data.customer_notify;
    }

    // formatting notes, from [key: key1, value: value1] => {key1: value1}
    data.notes = (data.notes || []).reduce(
      (otherNotes, { key, value }) => ({ ...otherNotes, [key]: value }),
      {}
    );

    return this.props
      .saveSubscription(data)
      .then(data => {
        if (data) {
          this.props.showNotification({
            type: 'success',
            message: 'Subscription Created Successfully',
          });

          if (this.props.onClose) {
            this.props.onClose();
          } else {
            const entityId = data.id;
            const redirectUrl = '/subscriptions/' + entityId;
            this.props.history.push(redirectUrl);
          }
        }
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  handleRemoveBtn = addonIndex => () => {
    const addons = this.state.fields.addons.filter(
      (_, index) => addonIndex !== index
    );

    const fields = {
      ...this.state.fields,
      addons,
    };

    const internals = {
      ...this.state.internals,
      _addOnPresent: isPresent(addons),
    };

    this.setState({ fields, internals });
  };

  changeTab = step => () => {
    const currentTab = this.state.currentTab + step;

    const validTabs = [...this.state.validTabs];
    validTabs[this.state.currentTab] = true;

    this.setState({ currentTab, validTabs });
  };

  isFormValid = () => {
    const { currentTab, fields, internals } = this.state;
    const validateTotalCount = (this.planDetailsForm || {}).validateTotalCount;
    return isFormValid(currentTab, fields, internals, validateTotalCount);
  };

  renderForm() {
    switch (this.state.currentTab) {
      case 0:
        return (
          <PlanDetails
            plans={this.props.plans}
            onChangeInPlan={this.handleChangeInPlan}
            onDateChange={this.handleDateChange}
            onTimeChange={this.handleTimeChange}
            fields={this.state.fields}
            internals={this.state.internals}
            ref={form => (this.planDetailsForm = form)}
          />
        );
      case 1:
        return (
          <AddOnDetails
            items={this.props.items}
            onSelectItem={this.handleSelectItem}
            onAddAddon={this.handleAddaddon}
            fields={this.state.fields}
            internals={this.state.internals}
            removeAddOn={this.handleRemoveBtn}
            currency={this.state.currencyOfSelectedPlan}
          />
        );
      case 2:
        return (
          <LinkDetails
            onDateChange={this.handleDateChange}
            onTimeChange={this.handleTimeChange}
            fields={this.state.fields}
            internals={this.state.internals}
          />
        );
      case 3:
        return (
          <Review
            fields={this.state.fields}
            internals={this.state.internals}
            plans={this.props.plans.items}
            getCurrencyList={this.props.user.getCurrencyList}
          />
        );
    }
  }

  renderWizard() {
    const { currentTab } = this.state;
    const isLastTab = currentTab === tabs.length - 1;
    return (
      // need to improve this css styling
      <div class="PaymentLinks--Create SubscriptionLinks--new Wizard">
        {/* create subscription link tabs */}
        <ModalAsideNav
          title="Create Subscription"
          description={<p>Provide details to create a subscription link</p>}
          tabs={tabs}
          tabClickHandler={this.handleTabChange}
          activeTab={currentTab}
          tabsValidity={this.state.validTabs}
          disableTabCondition={tabIndex =>
            tabIndex !== 0 && !this.state.validTabs[tabIndex - 1]
          }
        />
        <main class="form-container">
          <main-title>{tabs[currentTab]}</main-title>
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
              pendingState="Creating..."
              type="submit"
              onClick={this.handleCreate}
            >
              Create Subscription Link
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
        class="NewSubscriptionLink animate-down"
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

function isFormValid(
  formIndex,
  fields,
  internals,
  validateTotalCount = () => {}
) {
  switch (formIndex) {
    case 0: {
      return (
        !!fields.plan_id &&
        (internals._startsImmediately || !!fields.start_at) &&
        !validateTotalCount(fields.total_count)
      );
    }

    case 1: {
      return !internals._addOnPresent || fields.addons.every(isPresent);
    }

    case 2: {
      const notify_info = fields.notify_info || {};
      return (
        (!fields.customer_notify ||
          (!!notify_info.notify_email || !!notify_info.notify_phone)) &&
        (internals._isNonExpiringLink || !!fields.expire_by)
      );
    }
  }
}

const tabs = ['Plan Details', 'Add Ons', 'Link Details', 'Review'];
