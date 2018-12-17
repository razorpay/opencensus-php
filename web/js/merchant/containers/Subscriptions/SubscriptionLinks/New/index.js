import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { fetchPlans } from 'merchant/modules/plans';

import { ModalAsideNav } from 'component/Wizard';
import { Modal, ModalContent } from 'component/Modal';
import Form from 'component/Form';
import Button, { AsyncBtn } from 'component/Button';

import { dotStringToObj } from 'common/util';

import AddOnDetails from './AddOnDetails';
import LinkDetails from './LinkDetails';
import PlanDetails from './PlanDetails';
import Review from './Review';

@withRouter
@connect(state => ({ plans: state.plans }), { fetchPlans })
export default class NewSubscriptionLink extends Component {
  state = {
    currentTab: 0,
    validTabs: [false, false, false, false],
    fields: {
      quantity: 1,
    },
  };

  componentWillMount() {
    this.props.fetchPlans({ count: 100 });
  }

  handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);
    this.setState({ currentTab });
  };

  handleChangeIn = ({ target }) => {
    const value = target.value;
    const name = target.name || target.dataset.name;
    const fields = { ...this.state.fields };

    dotStringToObj(name, value, fields);

    this.setState({ fields });
  };

  handleChangeInPlan = ({ option }) => {
    this.setState({
      fields: {
        ...this.state.fields,
        plan_id: option.id,
      },
    });
  };

  changeTab = step => () => {
    const currentTab = this.state.currentTab + step;

    const validTabs = [...this.state.validTabs];
    validTabs[this.state.currentTab] = true;

    this.setState({ currentTab, validTabs });
  };

  renderForm() {
    switch (this.state.currentTab) {
      case 0:
        return (
          <PlanDetails
            plans={this.props.plans}
            selectedPlanId={this.state.fields.plan_id}
            onChangeInPlan={this.handleChangeInPlan}
            planQuantity={this.state.fields.quantity}
          />
        );
      case 1:
        return <AddOnDetails />;
      case 2:
        return <LinkDetails />;
      case 3:
        return <Review />;
    }
  }

  renderWizard() {
    const { currentTab } = this.state;
    return (
      // need to improve this css styling
      <div class="PaymentLinks--Create SubscriptionLinks--new Wizard">
        {/* create subscription link tabs */}
        <ModalAsideNav
          title="Create Subscription"
          description={<p>Provide details to create a subscription link</p>}
          tabs={tabs}
          disableTabCondition={_ => false}
          tabClickHandler={this.handleTabChange}
          activeTab={currentTab}
          tabsValidity={this.state.validTabs}
        />
        <main class="form-container">
          <main-title>{tabs[Number(currentTab)].title}</main-title>
          <Form
            class="PaymentLinks--Create--Form"
            layout="tabular"
            onChange={this.handleChangeIn}
            onSubmit={() => {}}
          >
            {this.renderForm()}
          </Form>
        </main>
        <footer>
          {this.state.currentTab > 0 && (
            <Button onClick={this.changeTab(-1)}>Previous</Button>
          )}
          {this.state.currentTab < tabs.length - 1 && (
            <Button.Primary class="btn btn-primary" onClick={this.changeTab(1)}>
              Next
            </Button.Primary>
          )}
          {this.state.currentTab === tabs.length - 1 && (
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

const tabs = ['Plan Details', 'Add Ons', 'Link Details', 'Review'];
