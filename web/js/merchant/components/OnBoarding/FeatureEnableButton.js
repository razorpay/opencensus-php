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
import { bindActionCreators, compose } from 'redux';

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

      this.props.tracking.trackEvent(
        window.rzpQ.productOnboarding().success(`${this.props.feature}.onboarding.get_started`, {
          isTour: true,
        }),
      );

      return;
    }

    let saveOnboarding = null;

    if (this.props.isTestMode) {
      saveOnboarding = this.props.updateFeatures(
        {
          features: {
            [this.props.feature]: 1,
          },
        },
        this.props.user.current,
      );
    } else {
      saveOnboarding = this.props.saveOnboarding(this.props.feature, {
        business_model: 'null-value',
      });
    }

    return saveOnboarding
      .then((res) => {
        this.props.tracking.trackEvent(
          window.rzpQ.productOnboarding().success(`${this.props.feature}.onboarding.get_started`),
        );

        return this.props.fetchUser();
      })
      .then((res) => {
        this.setState({
          isSuccess: true,
        });

        this.props.onClick && this.props.onClick(res);
      })
      .catch((err) => {
        window.rzpQ.productOnboarding().failed(`${this.props.feature}.onboarding.get_started`);

        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
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

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    isTestMode: state.session.mode === 'test',
    onboarding: state.onboarding,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchUser: () => fetchUser(), // TODO: import fetchUser is not working
      saveOnboarding: () => saveOnboarding(), // Somehow, only this seems to work
      updateFeatures,
      showNotification,
      handleProductQuickGuide,
    },
    dispatch,
  );

export default compose(
  connect(mapStateToProps, mapDispatchToProps),
  // eslint-disable-next-line babel/new-cap
  RTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_feature_enable_button`)),
)(FeatureEnableButton);
