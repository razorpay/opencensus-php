import { Component } from 'react'
import { connect } from 'react-redux'
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs'
import Header from 'rzp/ui/Header'
import Spinner from 'rzp/ui/Spinner'
import { fetchActivationDetails } from 'merchant/modules/activation'

import ContactDetailsForm from './ContactDetailsForm'
import BusinessDetailsForm from './BusinessDetailsForm'
import WebsiteDetailsForm from './WebsiteDetailsForm'
import BankAccountDetailsForm from './BankAccountDetailsForm'
import DocumentUploadForm from './DocumentUploadForm'
import SubmitForm from './SubmitForm'


@connect(
  (state) => state.activation,
  { fetchActivationDetails }
)
export default class ActivationWizard extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      selectedTabIndex: 0
    }
  }

  componentWillMount() {
    this.props.fetchActivationDetails()
  }

  gotoTab = (index) => {
    this.setState({
      selectedTabIndex: index - 1
    })
  }

  renderNavAnchor(stepNumber, title) {
    let icon = {
      success: <i class='fa fa-check'></i>,
      error: <i class='fa fa-times'></i>,
    }
    let iconType = this.props.steps[stepNumber]

    return (
      <a class={ iconType === 'success' ? 'text-success' : iconType === 'error' ? 'text-danger' : '' }>
        {icon[iconType]}
        <span>{title}</span>
      </a>
    );
  }

  render() {
    let { loading, data } = this.props
    let info

    if (data.submitted) {
      info = data.activated ? 'Your account is already activated' : 'Form has been submitted for activation and is pending admin response'
    }

    return (
      <div class='react-root'>
        <Header title='Activation Form' showMode={false} />

        <div class='content-wrapper'>
          {
            loading ?
              <div class='page-spinner-container'>
                <Spinner />
              </div> :
              <div>
                {
                  info && <div class='alert alert-info text-center'>{info}</div>
                }

                <Tabs
                  class='activation-wizard'
                  selectedIndex={this.state.selectedTabIndex}
                  onSelect={(index) => this.gotoTab(index + 1)}
                >
                  <TabList class='nav nav-tabs' activeTabClassName='active' disabledTabClassName='disabled'>
                    <Tab>{this.renderNavAnchor(1, 'Contact Details')}</Tab>
                    <Tab>{this.renderNavAnchor(2, 'Business Details')}</Tab>
                    <Tab>{this.renderNavAnchor(3, 'Website Details')}</Tab>
                    <Tab>{this.renderNavAnchor(4, 'Bank Account Details')}</Tab>
                    <Tab>{this.renderNavAnchor(5, 'Documents Upload')}</Tab>
                    <Tab>{this.renderNavAnchor(6, 'Submit Form')}</Tab>
                  </TabList>

                  <TabPanel>
                    <ContactDetailsForm
                      form='activationContactDetails'
                      step={1}
                      pageTitle='Contact Details'
                      gotoTab={this.gotoTab}
                    />
                  </TabPanel>

                  <TabPanel>
                    <BusinessDetailsForm
                      form='activationBusinessDetails'
                      step={2}
                      pageTitle='Business Details'
                      gotoTab={this.gotoTab}
                    />
                  </TabPanel>

                  <TabPanel>
                    <WebsiteDetailsForm
                      form='activationWebsiteDetails'
                      step={3}
                      pageTitle='Website Details'
                      gotoTab={this.gotoTab}
                    />
                  </TabPanel>

                  <TabPanel>
                    <BankAccountDetailsForm
                      form='activationBankAccounts'
                      step={4}
                      pageTitle='Bank Account Details'
                      gotoTab={this.gotoTab}
                    />
                  </TabPanel>

                  <TabPanel>
                    <DocumentUploadForm
                      form='activationDocumentUpload'
                      step={5}
                      pageTitle='Document Upload'
                      gotoTab={this.gotoTab}
                    />
                  </TabPanel>

                  <TabPanel>
                    <SubmitForm
                      form='activationSubmitForm'
                      step={6}
                      pageTitle='Submit for Activation'
                      gotoTab={this.gotoTab}
                    />
                  </TabPanel>
                </Tabs>
              </div>
          }
        </div>
      </div>
    )
  }
}
