import { Component } from 'react'
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs'

import ContactDetailsForm from './ContactDetailsForm'
import BusinessDetailsForm from './BusinessDetailsForm'
import WebsiteDetailsForm from './WebsiteDetailsForm'
import BankAccountDetailsForm from './BankAccountDetailsForm'
import DocumentUploadForm from './DocumentUploadForm'
import SubmitForm from './SubmitForm'

export default class ActivationWizard extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      selectedTabIndex: 0
    }
  }

  renderNavAnchor(stepNumber, title) {
    let isStepCompleted = (this.props.steps_finished || []).indexOf(stepNumber) !== -1
    let icon = isStepCompleted ? <i className='fa fa-check'></i> : ''
    return (
      <a class={ isStepCompleted ? 'text-success' : '' }>
        {icon}
        {title}
      </a>
    );
  }

  render() {
    return (
      <div class='react-root'>
        <div class='content-wrapper'>
          <Tabs selectedIndex={this.state.selectedTabIndex}>
            <TabList class='nav nav-tabs' activeTabClassName='active' disabledTabClassName='disabled'>
              <Tab>{this.renderNavAnchor(1, 'Contact Details')}</Tab>
              <Tab>{this.renderNavAnchor(2, 'Business Details')}</Tab>
              <Tab>{this.renderNavAnchor(3, 'Website Details')}</Tab>
              <Tab>{this.renderNavAnchor(4, 'Bank Account Details')}</Tab>
              <Tab>{this.renderNavAnchor(5, 'Documents Upload')}</Tab>
              <Tab>{this.renderNavAnchor(6, 'Submit Form')}</Tab>
            </TabList>

            <TabPanel>
              <ContactDetailsForm />
            </TabPanel>

            <TabPanel>
              <BusinessDetailsForm />
            </TabPanel>

            <TabPanel>
              <WebsiteDetailsForm />
            </TabPanel>

            <TabPanel>
              <BankAccountDetailsForm />
            </TabPanel>

            <TabPanel>
              <DocumentUploadForm />
            </TabPanel>

            <TabPanel>
              <SubmitForm />
            </TabPanel>
          </Tabs>
        </div>
      </div>
    )
  }
}
