import { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { AsyncBtn } from 'common/new-ui/Button';
import { classList } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateFeatures } from 'merchant/reducers/config';
import { saveOnboarding, handleProductQuickGuide } from 'merchant/reducers/onboarding';
import { fetchUser } from 'merchant/reducers/session';
import { setOnBoardingDataInLocalState } from './utils';
import track from './track';

@connect(
  (state) => {
    return {
      user: state.session.user,
      isTestMode: state.session.mode === 'test',
      onboarding: state.onboarding,
    };
  },
  {
    fetchUser: () => fetchUser(), // TODO: import fetchUser is not working
    saveOnboarding,
    updateFeatures,
    showNotification,
    handleProductQuickGuide,
  },
)
@RTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_feature_enable_button`))
class FeatureEnableButton extends Component {
  state = {
    isSuccess: false,
  };

  handleEnableFeature = () => {
    if (this.props.isLocalEnabler) {
      setOnBoardingDataInLocalState({
        feature: this.props.feature,
        data: {
          isEnabled: true,
          lastVisitedTime: Date.now(),
        },
      });

      if (this.props.onClick) this.props.onClick();
      if (this.props.tracking) {
        this.props.tracking.trackEvent(
          window.rzpQ.productOnboarding().success(`${this.props.feature}.onboarding.get_started`, {
            isTour: true,
          }),
        );
      }

      return;
    }

    let saveOnboardingPromise = null;

    if (this.props.isTestMode) {
      saveOnboardingPromise = this.props.updateFeatures(
        {
          features: {
            [this.props.feature]: 1,
          },
        },
        this.props.user.current,
      );
    } else {
      saveOnboardingPromise = this.props.saveOnboarding(this.props.feature, {
        business_model: 'null-value',
      });
    }

    saveOnboardingPromise
      .then(() => {
        if (this.props.tracking) {
          track.onBoardingGetSuccess(this.props.feature);
        }
        return this.props.fetchUser();
      })
      .then((res) => {
        this.setState({
          isSuccess: true,
        });
        return this.props.onClick && this.props.onClick(res);
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        track.onBoardingGetFailed(this.props.feature);
      });
  };

  render() {
    return (
      <AsyncBtn
        class={this.props.className}
        onClick={this.handleEnableFeature}
        disabled={this.state.isSuccess}
      >
        {this.props.children}
      </AsyncBtn>
    );
  }
}

const primaryColor = (className) => classList(className, 'Button--primary');
const transparentColor = (className) => classList(className, 'Button--transparent');

FeatureEnableButton.Primary = (props) => (
  <FeatureEnableButton {...props} class={primaryColor(props.className)} />
);

FeatureEnableButton.Transparent = (props) => (
  <FeatureEnableButton {...props} class={transparentColor(props.className)} />
);

export default FeatureEnableButton;
