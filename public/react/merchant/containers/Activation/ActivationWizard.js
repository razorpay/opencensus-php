import { Component } from 'react';
import { connect } from 'react-redux';
import { destroy } from 'redux-form';
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
import WizardItem from './WizardItem';
import NewProductsBanner from 'merchant/containers/Banners/NewProductsBanner';

@connect(state => state.activation, { destroy })
export default class ActivationWizard extends Component {
  activationForms = [
    {
      name: 'activationContactDetails',
      title: 'Contact Details',
    },
    {
      name: 'activationBusinessDetails',
      title: 'Business Details',
    },
    {
      name: 'activationWebsiteDetails',
      title: 'Website Details',
    },
    {
      name: 'activationBankAccounts',
      title: 'Bank Account Details',
    },
    {
      name: 'activationDocumentUpload',
      title: 'Documents Upload',
    },
    {
      name: 'activationSubmitForm',
      title: 'Submit Form',
      pageTitle: 'Submit for Activation',
    },
  ];

  state = {
    selectedTabIndex: 0,
    linkedAccountKyc: 0,
  };

  componentWillMount() {
    this.setState({
      linkedAccountKyc: this.props.data['need_kyc'] || 0,
    });

    if (this.props.accountId) {
      this.activationForms = this.activationForms.filter(
        activationForm =>
          activationForm.name !== 'activationContactDetails' &&
          activationForm.name !== 'activationWebsiteDetails' &&
          (this.state.linkedAccountKyc === 0
            ? activationForm.name !== 'activationDocumentUpload'
            : true)
      );
    }
  }

  componentWillUnmount() {
    this.activationForms.map(activationForm => {
      this.props.destroy(activationForm.name);
    });
  }

  gotoTab = index => {
    this.setState({
      selectedTabIndex: index - 1,
    });
  };

  renderNavAnchor(stepNumber, title) {
    let icon = {
      success: <i class="icon icon-done" />,
      error: <i class="icon icon-close" />,
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
        <span>
          {title}
        </span>
      </a>
    );
  }

  render() {
    let { data, accountId } = this.props;
    let info;

    if (data.submitted) {
      info = data.activated
        ? <div class="alert alert-info text-center">
            Your account is already activated
          </div>
        : <div class="alert alert-info">
            Your activation form is submitted and is under review. The process
            can take upto <b>2 working days</b>. If any clarification is needed,
            we will contact you on your registered email address -{' '}
            {data.contact_email}
          </div>;
    }

    return (
      <div>
        {!!data.submitted && <NewProductsBanner />}

        {info}

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
            {this.activationForms.map((form, index) =>
              <Tab key={form.name}>
                {this.renderNavAnchor(index + 1, form.title)}
              </Tab>
            )}
          </TabList>

          {this.activationForms.map((form, index) =>
            <TabPanel key={form.name}>
              <WizardItem
                form={form.name}
                step={index + 1}
                pageTitle={form.pageTitle || form.title}
                gotoTab={this.gotoTab}
                accountId={accountId}
                linkedAccountKyc={this.state.linkedAccountKyc}
              />
            </TabPanel>
          )}
        </Tabs>
      </div>
    );
  }
}
