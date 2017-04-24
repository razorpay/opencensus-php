import { Component } from 'react';
import { connect } from 'react-redux';
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
import WizardItem from './WizardItem';

@connect(state => state.activation)
export default class ActivationWizard extends Component {
  state = {
    selectedTabIndex: 0,
  };

  gotoTab = index => {
    this.setState({
      selectedTabIndex: index - 1,
    });
  };

  renderNavAnchor(stepNumber, title) {
    let icon = {
      success: <i class="fa fa-check" />,
      error: <i class="fa fa-times" />,
    };
    let iconType = this.props.steps[stepNumber];

    return (
      <a
        class={
          iconType === 'success'
            ? 'text-success'
            : iconType === 'error' ? 'text-danger' : ''
        }
      >
        {icon[iconType]}
        <span>{title}</span>
      </a>
    );
  }

  render() {
    let { data } = this.props;
    let info;

    if (data.submitted) {
      info = data.activated
        ? 'Your account is already activated'
        : 'Form has been submitted for activation and is pending admin response';
    }

    return (
      <div>
        {info && <div class="alert alert-info text-center">{info}</div>}

        <Tabs
          class="activation-wizard"
          selectedIndex={this.state.selectedTabIndex}
          onSelect={index => this.gotoTab(index + 1)}
        >
          <TabList
            class="nav nav-tabs"
            activeTabClassName="active"
            disabledTabClassName="disabled"
          >
            <Tab>{this.renderNavAnchor(1, 'Contact Details')}</Tab>
            <Tab>{this.renderNavAnchor(2, 'Business Details')}</Tab>
            <Tab>{this.renderNavAnchor(3, 'Website Details')}</Tab>
            <Tab>{this.renderNavAnchor(4, 'Bank Account Details')}</Tab>
            <Tab>{this.renderNavAnchor(5, 'Documents Upload')}</Tab>
            <Tab>{this.renderNavAnchor(6, 'Submit Form')}</Tab>
          </TabList>

          <TabPanel>
            <WizardItem
              form="activationContactDetails"
              step={1}
              pageTitle="Contact Details"
              gotoTab={this.gotoTab}
            />
          </TabPanel>

          <TabPanel>
            <WizardItem
              form="activationBusinessDetails"
              step={2}
              pageTitle="Business Details"
              gotoTab={this.gotoTab}
            />
          </TabPanel>

          <TabPanel>
            <WizardItem
              form="activationWebsiteDetails"
              step={3}
              pageTitle="Website Details"
              gotoTab={this.gotoTab}
            />
          </TabPanel>

          <TabPanel>
            <WizardItem
              form="activationBankAccounts"
              step={4}
              pageTitle="Bank Account Details"
              gotoTab={this.gotoTab}
            />
          </TabPanel>

          <TabPanel>
            <WizardItem
              form="activationDocumentUpload"
              step={5}
              pageTitle="Document Upload"
              gotoTab={this.gotoTab}
            />
          </TabPanel>

          <TabPanel>
            <WizardItem
              form="activationSubmitForm"
              step={6}
              pageTitle="Submit for Activation"
              gotoTab={this.gotoTab}
            />
          </TabPanel>
        </Tabs>
      </div>
    );
  }
}
