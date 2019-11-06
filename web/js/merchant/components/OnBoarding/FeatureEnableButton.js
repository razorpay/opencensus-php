import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { AsyncBtn } from 'component/Button';

import { classList } from 'common/util';

import { showNotification } from 'rzp/modules/notifications';

import { updateFeatures } from 'merchant/modules/config';
import {
  saveOnboarding,
  handleProductQuickGuide,
} from 'merchant/modules/onboarding';
import { fetchUser } from 'merchant/modules/session';

import { setOnBoardingDataInLocalState } from './utils';

@connect(
  state => {
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
  }
)
@RTracking(props =>
  window.rzpQ.component(`${props.feature}_onboarding_feature_enable_button`)
)
export default class FeatureEnableButton extends React.Component {
  state = {
    isSuccess: false,
  };

  handleEnableFeature = () => {
    if (this.props.isLocalEnabler) {
      setOnBoardingDataInLocalState({
        feature: this.props.feature,
        data: {
          isEnabled: true,
        },
      });

      this.props.onClick && this.props.onClick();

      this.props.tracking.trackEvent(
        window.rzpQ
          .productOnboarding()
          .success(`${this.props.feature}.onboarding.get_started.success`)
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
        this.props.user.current
      );
    } else {
      saveOnboarding = this.props.saveOnboarding(this.props.feature, {
        business_model: 'null-value',
      });
    }

    return saveOnboarding
      .then(res => {
        this.props.tracking.trackEvent(
          window.rzpQ
            .productOnboarding()
            .success(`${this.props.feature}.onboarding.get_started.success`, {
              clickSource: 'GetStarted_CTA',
            })
        );

        return this.props.fetchUser();
      })
      .then(res => {
        this.setState({
          isSuccess: true,
        });

        this.props.onClick && this.props.onClick(res);
      })
      .catch(err => {
        window.rzpQ
          .onbr()
          .failed(`${this.props.feature}.onboarding.get_started.failed`, {
            clickSource: 'GetStarted_CTA',
          });

        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    return (
      <AsyncBtn
        {...this.props}
        onClick={this.handleEnableFeature}
        disabled={this.state.isSuccess}
      >
        {this.props.children}
      </AsyncBtn>
    );
  }
}

FeatureEnableButton.Primary = props => (
  <FeatureEnableButton {...props} class={PRIMARY_COLOR(props.className)} />
);

FeatureEnableButton.Transparent = props => (
  <FeatureEnableButton {...props} class={TRANSPARENT_COLOR(props.className)} />
);

const PRIMARY_COLOR = className => classList(className, 'Button--primary');
const TRANSPARENT_COLOR = className =>
  classList(className, 'Button--transparent');
