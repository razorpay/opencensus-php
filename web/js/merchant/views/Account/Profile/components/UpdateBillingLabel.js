import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import InputGroupField from 'common/ui/Forms/InputField/InputGroupField';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Spinner from 'common/ui/Spinner';
import { updateBillingLabel, fetchBillingLabelSuggestions } from 'merchant/reducers/profile';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateSession } from 'merchant/reducers/session';
import { reduxForm } from 'redux-form';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import User from 'merchant/models/User';

import SuccessTickBlue from 'assets/success-tick-blue.svg';
import { compose } from 'redux';

class UpdateBillingLabel extends PureComponent {
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
          <i className="i i-info-circle billing-label-info" />
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

  updateBillingLabel = (data) => {
    const {
      updateBillingLabel: updateBillingLabelProp,
      user,
      updateSession,
      showNotification,
      closeModal,
    } = this.props;
    return updateBillingLabelProp(data)
      .then((response) => {
        if (response.success) {
          window.rzpAnalytics?.({
            eventCategory: 'Brand Name',
            eventAction: 'Save brand name success',
            eventLabel: user.id,
          });
          selfServeTrackSuccess({
            selfServeAction: 'Brand Name Updated',
            page: 'Profile',
            screen: 'My Account',
          });
          showNotification({
            type: 'success',
            message: 'Brand name updated successfully.',
          });

          closeModal();

          const newUser = new User({
            ...user,
            billing_label: response.data.billing_label,
          });

          updateSession({ user: newUser });
        }
      })
      .catch(({ errors }) => {
        window.rzpAnalytics?.({
          eventCategory: 'Brand Name',
          eventAction: 'Save brand name failure',
          eventLabel: user.id,
        });
        showNotification({
          type: 'error',
          message: errors[0],
        });
      });
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
      eventLabel: user.id,
    });

    this.updateBillingLabel(data)
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
    const { billingLabel, loading, suggestions, isValid, selectedSuggestion, openCustomLabel } =
      this.state;
    const { user } = this.props;

    return (
      <form onSubmit={this.props.handleSubmit(this.onSaveClicked)}>
        <ModalHeader title={this.titleGenerator()} onCloseClick={this.props.closeModal} />
        <div className="modal-body">
          <div className="form-group">
            <small>
              Brand Name should be similar to your <b>Business name</b> or the <b>Domain name</b> of
              your website/app. Based on that we have generated the following suggestions.
            </small>
            <div className="help-curr-billing-div">Current Brand Name</div>
            <InputGroupField
              value={user.billing_label}
              readOnly={true}
              className="form-control curr-billing-label-input"
              meta={{}}
              suffix={
                <img
                  src={SuccessTickBlue}
                  alt="Tick icon"
                  className="suggested-label-selected-tick"
                />
              }
            />
            <div className="help-curr-billing-div">Other Alternatives</div>
            {loading ? (
              <div className="page-spinner-container">
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
                    src={SuccessTickBlue}
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
                <div className="help-curr-billing-div">Custom Brand Name</div>
                <input
                  name="billingLabel"
                  value={billingLabel}
                  className="form-control"
                  onChange={this.handleChange}
                />
              </div>
            )}
          </div>

          <div className="Modal__actions">
            <AsyncButton
              type="submit"
              className="btn btn-primary btn-block"
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

export default compose(
  connect(
    (state) => {
      return { user: state.session.user };
    },
    {
      updateBillingLabel,
      updateSession,
      closeModal,
      showNotification,
      fetchBillingLabelSuggestions,
    },
  ),
  reduxForm({
    form: 'updateMerchantConfigForm',
  }),
)(UpdateBillingLabel);
