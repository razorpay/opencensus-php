import { Component } from 'react';
import { connect } from 'react-redux';
import { destroy } from 'redux-form';
import WizardItem from './WizardItem';

import Tabs, { Tab, TabPane } from 'rzp/ui/ReactTabs';

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
    const needKyc = this.props.data['need_kyc'] || 0;

    this.setState({
      linkedAccountKyc: needKyc,
    });

    if (this.props.accountId) {
      this.activationForms = this.activationForms.filter(
        activationForm =>
          activationForm.name !==
          'activationContactDetails'(
            needKyc === 0
              ? activationForm.name !== 'activationDocumentUpload'
              : true
          )
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
      success: <i class="i i-done" />,
      error: <i class="i i-close" />,
    };
    let iconType = this.props.steps[stepNumber];

    return (
      <span
        class={
          iconType === 'success'
            ? 'text-success'
            : iconType === 'error' ? 'text-danger' : ''
        }
      >
        {icon[iconType]}
        <span>{title}</span>
      </span>
    );
  }

  render() {
    let { data, accountId } = this.props;
    let info;

    if (data.submitted) {
      info = data.activated ? (
        <div class="alert alert-info text-center">
          {accountId
            ? 'The account has been activated'
            : 'Your account is already activated'}
        </div>
      ) : !accountId ? (
        (info = ActivationStatusInfo(data))
      ) : null;
    } else {
      //- Not to be shown when used in marketplace when accessing this form via 'Route'
      if (!accountId) {
        info = (
          <div class="alert alert-info text-center">
            Complete and submit your activation form to start live transactions
            on Razorpay.
          </div>
        );
      }
    }

    return (
      <div>
        {info}
        <Tabs
          class="activation-wizard"
          selectedTabIndex={this.state.selectedTabIndex}
          onSelect={index => this.gotoTab(index + 1)}
        >
          {this.activationForms.map((form, index) => (
            <Tab key={form.name}>
              {this.renderNavAnchor(index + 1, form.title)}
            </Tab>
          ))}

          {this.activationForms.map((form, index) => (
            <TabPane key={form.name}>
              <WizardItem
                form={form.name}
                step={index + 1}
                pageTitle={form.pageTitle || form.title}
                gotoTab={this.gotoTab}
                accountId={accountId}
                callback={this.props.callback}
                linkedAccountKyc={this.state.linkedAccountKyc}
              />
            </TabPane>
          ))}
        </Tabs>
      </div>
    );
  }
}

const ActivationStatusInfo = data => {
  if (data.activation_status === 'under_review') {
    return (
      <div class="alert alert-info text-center">
        Your activation form is submitted and is under review. The process may
        take upto <b>2 working days</b>. If any clarification is needed, we will
        contact you on your registered email address - {data.contact_email}
      </div>
    );
  } else if (data.activation_status === 'needs_clarification') {
    return (
      <div class="alert alert-info text-center">
        We have sent you an email seeking clarification about your activation
        form. Please check your registered email - {data.contact_email}.
      </div>
    );
  } else {
    return null;
  }
};
