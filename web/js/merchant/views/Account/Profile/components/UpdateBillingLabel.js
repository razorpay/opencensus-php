import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import InputGroupField from 'common/ui/Forms/InputField/InputGroupField';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Spinner from 'common/ui/Spinner';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchBillingLabelSuggestions } from 'merchant/reducers/profile';
import { reduxForm } from 'redux-form';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@connect(
  (state) => {
    return { user: state.session.user };
  },
  { closeModal, showNotification, fetchBillingLabelSuggestions },
)
@reduxForm({
  form: 'updateMerchantConfigForm',
})
export default class UpdateBillingLabel extends PureComponent {
  constructor(props) {
    super(props);

    this.state = {
      billingLabel: '',
      loading: true,
      suggestions: [],
      isValid: false,
      selectedSuggestion: null,
      openCustomLabel: false,
    };
  }

  componentDidMount() {
    fetchBillingLabelSuggestions()
      .then(({ data }) => {
        analyticsTrack({
          objectName: 'Brand name edit popup',
          actionName: 'displayed',
          screen: 'my account',
          properties: {
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.setState({ loading: false, suggestions: data });
      })
      .catch(() => {
        console.log('ERROR: Failed to fetch billing label suggestions');
        this.setState({ loading: false });
      });
  }

  onClickedSuggestion = (suggestion, index) => {
    analyticsTrack({
      objectName: 'brand name suggestions',
      actionName: 'selected',
      screen: 'my account',
      properties: {
        originalName: this.state.billingLabel,
        selectedBrandName: suggestion,
        // website: '',
        // businessName: // props remaining to be added
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const { openCustomLabel } = this.state;
    if (openCustomLabel) {
      this.setState({ openCustomLabel: false });
    }
    this.setState({ billingLabel: suggestion, isValid: true, selectedSuggestion: index });
  };

  titleGenerator = () => {
    return (
      <div>
        <span>Brand Name</span>
        <small>
          <i class="i i-info-circle billing-label-info" />
          <Popover
            align="bottom"
            followPointer={true}
            theme="dark"
            className="billing-label-popover"
          >
            <PopoverBody>
              <div>
                <div>Brand Name changes would be reflected in the following places,</div>
                <div>- Transaction Confirmation Email</div>
                <div>- Refund Email</div>
                <div>- Payment Pages</div>
                <div>- Payment link</div>
                <div>- Checkout</div>
                <div>- Smart Collect</div>
                <div>- Route</div>
                <div>- Subscriptions</div>
              </div>
            </PopoverBody>
          </Popover>
        </small>
      </div>
    );
  };

  openCutomBillingLabelInput = () => {
    analyticsTrack({
      objectName: 'add custom brand name',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.setState({
      isValid: false,
      billingLabel: '',
      selectedSuggestion: null,
      openCustomLabel: true,
    });
  };

  handleChange = (event) => {
    const { name, value } = event.target;
    let isValid = false;
    if (value != '') {
      isValid = true;
    }
    this.setState({ [name]: value, isValid });
  };

  onSaveClicked = () => {
    const data = {
      billing_label: this.state.billingLabel,
    };
    const { user } = this.props;
    analyticsTrack({
      objectName: 'save brand name',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    window.rzpAnalytics?.({
      eventCategory: 'Brand Name',
      eventAction: 'Save brand name clicked',
      eventLabel: `${user.id}`,
    });

    this.props
      .updateMerchantConfig(data)
      .then(() => {
        analyticsTrack({
          objectName: 'save brand name',
          actionName: 'result',
          screen: 'my account',
          properties: {
            status: true,
            newBrandName: this.state.billing_label,
            // original remaining
            // originalBrandName: this.state.billingLabel,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      })
      .catch((e) => {
        analyticsTrack({
          objectName: 'save brand name',
          actionName: 'result',
          screen: 'my account',
          properties: {
            status: false,
            failureReason: e.errors[0],
            // eslint-disable-next-line no-undef
            newBrandName: billing_label,
            originalBrandName: this.state.billingLabel,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      });
  };

  render() {
    const {
      billingLabel,
      loading,
      suggestions,
      isValid,
      selectedSuggestion,
      openCustomLabel,
    } = this.state;

    return (
      <form onSubmit={this.props.handleSubmit(this.onSaveClicked)}>
        <ModalHeader title={this.titleGenerator()} onCloseClick={this.props.closeModal} />
        <div class="modal-body">
          <div class="form-group">
            <small>
              Brand Name should be similar to your <b>Business name</b> or the <b>Domain name</b> of
              your website/app. Based on that we have generated the following suggestions.
            </small>
            <div class="help-curr-billing-div">Current Brand Name</div>
            <InputGroupField
              value={this.props.value}
              readOnly={true}
              className="form-control curr-billing-label-input"
              meta={{}}
              suffix={
                <img
                  src={'/dist/css/assets/success-tick-blue.svg'}
                  alt="Tick icon"
                  className="suggested-label-selected-tick"
                />
              }
            />
            <div class="help-curr-billing-div">Other Alternatives</div>
            {loading ? (
              <div class="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              suggestions.map((item, index) => (
                <div
                  key={index}
                  className={`billing-label-suggestions ${
                    selectedSuggestion === index ? 'suggested-label-selected' : ''
                  } ${
                    index === suggestions.length - 1
                      ? 'billing-label-suggestions-border-bottom'
                      : ''
                  }`}
                  onClick={() => this.onClickedSuggestion(item, index)}
                >
                  {item}
                  <img
                    src={'/dist/css/assets/success-tick-blue.svg'}
                    alt="Tick icon"
                    className={`suggested-label-selected-tick ${
                      !(selectedSuggestion === index) ? 'hide-element' : ''
                    }`}
                  />
                </div>
              ))
            )}
            {!openCustomLabel && (
              <div className="add-cutom-label-header">
                <span onClick={this.openCutomBillingLabelInput}>+ Add a Custom Brand Name</span>
              </div>
            )}
            {openCustomLabel && (
              <div>
                <div class="help-curr-billing-div">Custom Brand Name</div>
                <input
                  name="billingLabel"
                  value={billingLabel}
                  className="form-control"
                  onChange={this.handleChange}
                />
              </div>
            )}
          </div>

          <div class="Modal__actions">
            <AsyncButton
              type="submit"
              class="btn btn-primary btn-block"
              text="Save Changes"
              pendingText="Saving..."
              disabled={!isValid}
              onClick={this.props.handleSubmit(this.onSaveClicked)}
            />
          </div>
        </div>
      </form>
    );
  }
}
