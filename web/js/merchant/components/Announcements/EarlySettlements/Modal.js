/* eslint-disable no-undef */
import { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { Field, reduxForm } from 'redux-form';

import { withRouter } from 'common/deprecated/withRouter';
import Button from 'common/new-ui/Button';
import RadioButton from 'common/ui/Forms/RadioButton';
import { setItem } from 'common/utils/localStorage';
import trackESAnnouncements from 'merchant/components/Announcements/ga';
import ShowWhen from 'merchant/components/ShowWhen';
import ajax from 'merchant/utils/ajax';
import * as ModalActions from 'merchant_common/reducers/modals';

const SuccessScreen = (closeScreen) => (
  <div class="modal-body rzp-early-stl-modal success-modal">
    <div class="success-banner-cnt">
      <img class="banner-header" src="/img/early_settlements/es-banner-1-header.png" />
      <button class="close" onClick={() => closeScreen('Close Buuton')}>
        <i class="i i-close" />
      </button>

      <img class="banner" src="/img/early_settlements/es-banner-2.png" />
      <h3 class="modal-title">Instant Settlements Requested</h3>
      <div class="help-block">
        You shall be activated soon for Instant Settlements. A confirmation email will be sent to
        your registered Email ID.
      </div>

      <div>
        <Button.Primary class="close-btn m-t" onClick={() => closeScreen('Got it')}>
          Got it
        </Button.Primary>
      </div>

      <img class="banner-footer" src="/img/early_settlements/es-banner-1-footer.png" />
    </div>
  </div>
);

class RequestEarlyAccessForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
      saving: false,
      fetching: false,
      activeScreenIndex: 0,
      formData: '',
      pricing: 0.2,
      modalTitle: '',
      showFeatures: true,
      showOptions: true,
    };

    if (window.innerWidth < 764) {
      this.state.showOptions = false;
    }
    this.onSubmit = this.onSubmit.bind(this);
    this.closeForm = this.closeForm.bind(this);
    this.closePricing = this.closePricing.bind(this);
    this.requestKey = `early-settlement-requested-${props.user.current}`;
  }

  onSubmit(body) {
    this.setState({
      fetching: true,
      formData: body,
    });
    trackESAnnouncements.trackESModalSubmit(this.props.from);
    this.fetchPricing(body.interested_in);
  }

  fetchPricing(pricingType) {
    ajax(
      {
        url: '/cache/es_pricing',
        method: 'GET',
      },
      {},
      '/merchant/api',
    )
      .then((response) => {
        if (response) {
          let price, title;
          if (pricingType === 'on-demand') {
            price = response.data[`${this.props.user.current}_on_demand_es_pricing`];
            title = 'On-demand Settlements';
          } else {
            price = response.data[`${this.props.user.current}_scheduled_es_pricing`];
            title = 'Automatic Instant Settlements';
          }
          this.setState({
            fetching: false,
            activeScreenIndex: 1,
            pricing: price,
            modalTitle: title,
          });
        }
      })
      .catch(() => {
        this.setState({
          fetching: false,
        });
      });
  }

  handleAcceptPricing = () => {
    trackESAnnouncements.trackESPricingAccept(this.props.from, this.state.formData.interested_in);
    this.postESRequest('https://hooks.zapier.com/hooks/catch/1088429/lbq8rx/', 2);
    this.createFreshdeskTicket();
  };

  handleCancelPricing = () => {
    this.postESRequest('https://hooks.zapier.com/hooks/catch/1088429/qljsgo', 0);
  };

  createFreshdeskTicket() {
    const TSYS_AUTH_TOKEN = '4d482bcf908b56771a86db388bae8ee7639b0f81';
    const apiUrl = 'https://support-tsa.razorpay.com/api/fd/ticket/create';
    // Sandbox API URL - Bussiness operations group id = 42000097437
    // const apiUrl = 'http://localhost:4000/api/fd/ticket/create';

    const { formData, pricing } = this.state;
    const item = formData.interested_in === 'automatic' ? 'Automatic' : 'On demand';

    axios({
      method: 'post',
      baseURL: apiUrl,
      headers: {
        sendImmediately: true,
        Authorization: `Basic ${TSYS_AUTH_TOKEN}`,
        'Content-Type': 'application/json',
      },
      data: {
        priority: 3,
        email: this.props.user.user.email,
        phone: this.props.user.contact_mobile,
        subject: `Razorpay | Early Settlement Request [${this.props.user.current}]`,
        type: 'Service request',
        group_id: 1000097912,
        ticketType: 'early_settlement',
        description: `<div dir="ltr"><div>Hey,<br><br>We have received a request for ${formData.interested_in} Early Settlement for <strong>${this.props.user.name}</strong>. The pricing agreed to is <strong>${pricing}%.</strong> For international payments, it is <strong>1%</strong> more.<br><br>We will update you once the changes have been approved.<br><br>Cheers,<br>Team Razorpay</div></div>`,
        custom_fields: {
          cf_requester_category: 'Merchant',
          cf_requestor_subcategory: 'Account configuration/changes',
          cf_subcategory: 'Early settlement',
          cf_item: item,
          cf_ticket_queue: 'Merchant',
          cf_merchant_id: this.props.user.current,
          cf_category: 'Account configuration/changes',
          cf_product: 'Early settlement',
        },
      },
    });
  }

  postESRequest(webhookUrl, nextScreen) {
    const { formData, pricing } = this.state;

    this.setState({
      saving: true,
    });

    axios({
      method: 'post',
      url: webhookUrl,
      data: {
        user_email: this.props.user.user.email,
        user_phone: this.props.user.contact_mobile,
        merchant_id: this.props.user.current,
        merchant_name: this.props.user.name,
        role: this.props.user.role,
        activation_status: '',
        interested_in: formData.interested_in,
        pricing,
      },
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
    })
      .then((response) => {
        if (response.status == 200) {
          this.setState({
            saving: false,
            activeScreenIndex: nextScreen,
          });
          if (nextScreen == 2) {
            const bannerEvent = new window.CustomEvent('remove-es-announcement', {
              bubbles: false,
            });
            const buttonEvent = new window.CustomEvent('remove-req-es-button', {
              bubbles: false,
            });

            window.dispatchEvent(bannerEvent);
            window.dispatchEvent(buttonEvent);

            setItem(this.requestKey, 1);
            this.props.closeModal();
            this.props.openModal({
              component: <SuccessScreen closeScreen={this.closeSuccessScreen} />,
            });
          }
        }
      })
      .catch(() => {
        this.setState({
          saving: false,
        });
      });
  }

  closeForm() {
    trackESAnnouncements.trackESModalClose(this.props.from);

    //remove hash from URL when modal is closed
    if (location.hash.indexOf('#requestearlyaccess') > -1) {
      this.props.history.replace(this.props.location.pathname);
    }

    this.props.closeModal();
  }

  closePricing() {
    this.handleCancelPricing();
    trackESAnnouncements.trackESPricingModalClose(
      this.props.from,
      this.state.formData.interested_in,
    );
    this.props.closeModal();
  }

  handleBack = () => {
    this.handleCancelPricing();
    trackESAnnouncements.trackESPricingBack(this.props.from, this.state.formData.interested_in);
    this.setState((prevState) => {
      return {
        activeScreenIndex: Math.max(prevState.activeScreenIndex - 1, 0),
      };
    });
  };

  closeSuccessScreen = (buttonText) => {
    trackESAnnouncements.trackESSuccessModalClose(this.props.from, buttonText);
    this.props.closeModal();
  };

  handleNext = () => {
    this.setState({
      showFeatures: false,
      showOptions: true,
      activeScreenIndex: 0,
    });
  };

  handleOptionsBack = () => {
    this.setState({
      showFeatures: true,
      showOptions: false,
      activeScreenIndex: 0,
    });
  };

  handleChange = (e) => {
    this.setState({
      formData: {
        interested_in: e.target.name,
      },
    });
  };

  componentDidMount() {
    const container = document.getElementById('es-modal-cnt');
    container.style.height = `${container.clientHeight}px`;
  }

  render() {
    const { handleSubmit } = this.props;
    const screens = [];
    let mainScreen = null;

    screens.push(
      <>
        <button class="close" onClick={this.closeForm}>
          <i class="i i-close" />
        </button>
        <div class="modal-header">
          <h3 class="modal-title">Get Started!</h3>
        </div>
        <div class="help-block">
          You can choose to get Instant settlements in either of the following ways:
        </div>
        <form onSubmit={handleSubmit(this.onSubmit)}>
          <div class="form-group">
            <Field
              name="interested_in"
              component={RadioButton}
              htmlValue="automatic"
              onChange={this.handleChange}
              label={() => (
                <span class="radio-label">
                  <label class="title">Automatic Instant Settlements</label>
                  <div class="description">
                    Razorpay will automatically settle all your payments at specific hours during
                    the day, ensuring a consistent working capital.
                  </div>
                </span>
              )}
            />
            <Field
              name="interested_in"
              component={RadioButton}
              htmlValue="on-demand"
              onChange={this.handleChange}
              label={() => (
                <span class="radio-label">
                  <label class="title">On-demand Instant Settlements</label>
                  <div class="description">
                    Choose when you want your settlements early. All your other settlements follow
                    your existing settlement schedule.
                  </div>
                </span>
              )}
            />
          </div>
          <div class="form-action">
            <Button class="options-back-btn" onClick={this.handleOptionsBack}>
              Back
            </Button>
            <Button.Primary
              class="submit-btn"
              disabled={this.state.fetching || !this.state.formData}
            >
              {this.state.fetching ? 'Fetching details ' : 'Request'}
            </Button.Primary>
          </div>
        </form>
      </>,
    );

    screens.push(
      <>
        <button class="close" onClick={this.closePricing}>
          <i class="i i-close" />
        </button>
        <div class="modal-header">
          <h3 class="modal-title">{this.state.modalTitle}</h3>
        </div>
        <div class="help-block">
          {this.state.formData && this.state.formData.interested_in == 'on-demand'
            ? 'Choose when you want your settlements early. All your other settlements follow your existing settlement schedule.'
            : 'Razorpay will automatically settle all your payments at specific hours during the day, ensuring a consistent working capital.'}
        </div>
        <span class="modal-subtitle">Your pricing is {this.state.pricing}%</span>
        <p>
          Based on your risk profile which includes refunds, chargebacks, vintage with Razorpay,
          etc. you will be charged <strong>{this.state.pricing}%</strong> more for domestic payments
          settling early. For international payments, it will be <strong>1%</strong> more.
        </p>
        <div class="form-action">
          <Button onClick={this.handleBack} disabled={this.state.saving}>
            Back
          </Button>
          <Button.Primary onClick={this.handleAcceptPricing} disabled={this.state.saving}>
            {this.state.saving ? 'Requesting' : 'Confirm Request'}
          </Button.Primary>
        </div>
      </>,
    );

    mainScreen = (
      <div id="es-modal-cnt" class="modal-body rzp-early-stl-modal">
        <div class={`content-left ${!this.state.showFeatures && 'hide'}`}>
          <button class="close" onClick={this.props.closeModal}>
            <i class="i i-close" />
          </button>
          <div class="modal-header">
            <h3 class="modal-title">Instant Settlements</h3>
          </div>
          <div class="help-block">
            Razorpay is working with <strong>top financing institutions</strong> to help you realise
            your settlements within a few working hours. No more shortfalls in working capital.
            <ShowWhen
              additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
            >
              <p class="m-t">
                <a
                  target="_blank"
                  rel="noopener noreferrer"
                  href="https://razorpay.com/knowledgebase/"
                >
                  Know more about Instant Settlements <i class="i i-external-link" />
                </a>
              </p>
            </ShowWhen>
          </div>
          <div class="features-list">
            <div class="feature-item">
              <div class="feature-icon">
                <img src="/img/early_settlements/es-icon-1.png" />
              </div>
              <div class="feature-content">
                <span class="feature-title">Better Budgeting</span>
                <div class="feature-text">Predict monthly budget, expenses and investment</div>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon">
                <img src="/img/early_settlements/es-icon-2.png" />
              </div>
              <div class="feature-content">
                <span class="feature-title">Zero Backlogs</span>
                <div class="feature-text">Avoid backlog in your payment reconciliation</div>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon">
                <img src="/img/early_settlements/es-icon-3.png" />
              </div>
              <div class="feature-content">
                <span class="feature-title">Easy Financing</span>
                <div class="feature-text">Avoid costly short-term financing</div>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon">
                <img src="/img/early_settlements/es-icon-4.png" />
              </div>
              <div class="feature-content">
                <span class="feature-title">Manage Settlements</span>
                <div class="feature-text">Efficiently manage your vendor settlements</div>
              </div>
            </div>
          </div>
          <div class="form-action">
            <Button.Primary onClick={this.handleNext}>Next</Button.Primary>
          </div>
        </div>
        <div class={`content-right ${!this.state.showOptions && 'hide'}`}>
          {screens[this.state.activeScreenIndex]}
        </div>
      </div>
    );

    return mainScreen;
  }
}

const mapStateToProps = (state) => {
  return { user: state.session.user };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ ...ModalActions }, dispatch);
};

const enhancedComponent = compose(
  withRouter,
  reduxForm({
    form: 'es-access',
    initialValues: {
      interested_in: '',
    },
  }),
  connect(mapStateToProps, mapDispatchToProps),
);

export default enhancedComponent(RequestEarlyAccessForm);
