import { Component } from 'react';
import { connect } from 'react-redux';
import Button from 'component/Button';
import * as ModalActions from 'rzp/modules/modals';
import { Field, reduxForm } from 'redux-form';
import RadioButton from 'rzp/ui/Forms/RadioButton';
import trackESAnnouncements from './ga';
import ajax from 'merchant/utils/ajax';

@connect(
  state => ({ user: state.session.user }),
  { ...ModalActions }
)
@reduxForm({
  form: 'es-access',
  initialValues: {
    interested_in: 'automatic',
  },
})
export default class RequestEarlyAccessForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
      saving: false,
      fetching: false,
      activeScreenIndex: 0,
      pricing: 0.2,
      errors: [],
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
      '/merchant/api'
    )
      .then(response => {
        if (response) {
          let price, title;
          if (pricingType === 'on-demand') {
            price =
              response.data[`${this.props.user.current}_on_demand_es_pricing`];
            title = 'On-demand Settlements';
          } else {
            price =
              response.data[`${this.props.user.current}_scheduled_es_pricing`];
            title = 'Automatic Early Settlements';
          }
          this.setState({
            fetching: false,
            activeScreenIndex: 1,
            pricing: price,
            modalTitle: title,
          });
        }
      })
      .catch(response => {
        this.setState({
          fetching: false,
          errors: response.errors,
        });
      });
  }

  handleAcceptPricing = () => {
    trackESAnnouncements.trackESPricingAccept(this.props.from);
    this.postESRequest(
      'https://hooks.zapier.com/hooks/catch/1088429/lbq8rx/',
      2
    );
  };

  handleCancelPricing = () => {
    trackESAnnouncements.trackESPricingCancel(this.props.from);
    this.postESRequest(
      'https://hooks.zapier.com/hooks/catch/1088429/qljsgo',
      0
    );
  };

  postESRequest(webhookUrl, nextScreen) {
    let { formData, pricing } = this.state;

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
        pricing: pricing,
      },
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
    })
      .then(response => {
        if (response.status == 200) {
          if (this.props.closeBanner && nextScreen == 2) {
            this.props.closeBanner();
          }
          this.setState({
            saving: false,
            activeScreenIndex: nextScreen,
          });
        }
      })
      .catch(response => {
        this.setState({
          saving: false,
        });
      });
  }

  closeForm() {
    trackESAnnouncements.trackESModalClose(this.props.from);
    this.props.closeModal();
  }

  closePricing() {
    this.handleCancelPricing();
    trackESAnnouncements.trackESPricingModalClose(this.props.from);
    this.props.closeModal();
  }

  handleBack = () => {
    this.handleCancelPricing();
    this.setState({
      activeScreenIndex: Math.max(this.state.activeScreenIndex - 1, 0),
    });
  };

  handleNext = () => {
    this.setState({
      showFeatures: false,
      showOptions: true,
    });
  };

  componentDidMount() {
    let container = document.getElementById('es-modal-cnt');
    container.style.height = container.clientHeight + 'px';
  }

  render() {
    let { handleSubmit } = this.props;
    let screens = [],
      mainScreen;

    screens.push(
      <React.Fragment>
        <button class="close" onClick={this.closeForm}>
          <i class="i i-close" />
        </button>
        <div class="modal-header">
          <h3 class="modal-title">Get Started!</h3>
        </div>
        <div class="help-block">
          You can choose to get early settlements in either of the following
          ways:
        </div>
        <form onSubmit={handleSubmit(this.onSubmit)}>
          <div class="form-group">
            <Field
              name="interested_in"
              component={RadioButton}
              htmlValue="automatic"
              label={() => (
                <span class="radio-label">
                  <label class="title">Automatic Early Settlements</label>
                  <div class="description">
                    Razorpay will automatically settle all your payments at
                    specific hours during the day, ensuring a consistent working
                    capital.
                  </div>
                </span>
              )}
            />
            <Field
              name="interested_in"
              component={RadioButton}
              htmlValue="on-demand"
              label={() => (
                <span class="radio-label">
                  <label class="title">On-demand Early Settlements</label>
                  <div class="description">
                    Choose when you want your settlements early. All your other
                    settlements follow your existing settlement schedule.
                  </div>
                </span>
              )}
            />
          </div>
          <div class="form-action">
            <Button.Primary class="submit-btn" disabled={this.state.fetching}>
              {this.state.fetching ? 'Fetching details ' : 'Request'}
            </Button.Primary>
          </div>
        </form>
      </React.Fragment>
    );

    screens.push(
      <React.Fragment>
        <button class="close" onClick={this.closePricing}>
          <i class="i i-close" />
        </button>
        <div class="modal-header">
          <h3 class="modal-title">{this.state.modalTitle}</h3>
        </div>
        <div class="help-block">
          {this.state.formData &&
          this.state.formData.interested_in == 'on-demand'
            ? 'Choose when you want your settlements early. All your other settlements follow your existing settlement schedule.'
            : 'Razorpay will automatically settle all your payments at specific hours during the day, ensuring a consistent working capital.'}
        </div>
        <span class="modal-subtitle">Pricing</span>
        <p>
          Based on your risk profile which includes refunds, chargebacks,
          vintage with Razorpay, etc. you will be charged{' '}
          <strong>{this.state.pricing}%</strong> for every settlement that is
          being done early.
        </p>
        <div class="form-action">
          <Button onClick={this.handleBack} disabled={this.state.saving}>
            Back
          </Button>
          <Button.Primary
            onClick={this.handleAcceptPricing}
            disabled={this.state.saving}
          >
            {this.state.saving ? 'Requesting' : 'Confirm Request'}
          </Button.Primary>
        </div>
      </React.Fragment>
    );

    if (this.state.activeScreenIndex == 2) {
      mainScreen = (
        <div
          id="es-modal-cnt"
          class="modal-body rzp-early-stl-modal success-modal"
        >
          <button class="close" onClick={this.props.closeModal}>
            <i class="i i-close" />
          </button>
          <div class="success-banner-cnt">
            <img src="img/early_settlements/es-banner-2.png" />
            <h3 class="modal-title">Early Settlements Requested</h3>
            <div class="help-block">
              You shall be activated soon for Early Settlements. A confirmation
              email will be sent regarding the same on your registered Email ID.
            </div>
            <Button.Primary class="close-btn" onClick={this.props.closeModal}>
              Got it
            </Button.Primary>
          </div>
        </div>
      );
    } else {
      mainScreen = (
        <div id="es-modal-cnt" class="modal-body rzp-early-stl-modal">
          <div class={`content-left ${!this.state.showFeatures && 'hide'}`}>
            <button class="close" onClick={this.props.closeModal}>
              <i class="i i-close" />
            </button>
            <div class="modal-header">
              <h3 class="modal-title">Early Settlements</h3>
            </div>
            <div class="help-block">
              Get your payments settled within a few working hours and never
              have a shortfall of working capital for your business.
            </div>
            <div class="features-list">
              <div class="feature-item">
                <div class="feature-icon">
                  <img src="img/early_settlements/es-icon-1.png" />
                </div>
                <div class="feature-content">
                  <span class="feature-title">Better Budgeting</span>
                  <div class="feature-text">
                    Predict monthly budget, expenses and investment
                  </div>
                </div>
              </div>
              <div class="feature-item">
                <div class="feature-icon">
                  <img src="img/early_settlements/es-icon-2.png" />
                </div>
                <div class="feature-content">
                  <span class="feature-title">Zero Backlogs</span>
                  <div class="feature-text">
                    Avoid backlog in your payment reconciliation
                  </div>
                </div>
              </div>
              <div class="feature-item">
                <div class="feature-icon">
                  <img src="img/early_settlements/es-icon-3.png" />
                </div>
                <div class="feature-content">
                  <span class="feature-title">Easy Financing</span>
                  <div class="feature-text">
                    Avoid costly short-term financing
                  </div>
                </div>
              </div>
              <div class="feature-item">
                <div class="feature-icon">
                  <img src="img/early_settlements/es-icon-4.png" />
                </div>
                <div class="feature-content">
                  <span class="feature-title">Manage Settlements</span>
                  <div class="feature-text">
                    Efficiently manage your vendor settlements
                  </div>
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
    }

    return mainScreen;
  }
}
