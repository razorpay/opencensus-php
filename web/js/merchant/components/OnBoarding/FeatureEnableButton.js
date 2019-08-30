import { connect } from 'react-redux';

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
    fetchUser,
    saveOnboarding,
    updateFeatures,
    showNotification,
    handleProductQuickGuide,
  }
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
        business_model: '',
      });
    }

    return saveOnboarding
      .then(() => {
        return this.props.fetchUser();
      })
      .then(res => {
        this.setState({
          isSuccess: true,
        });

        this.props.onClick && this.props.onClick(res);
      })
      .catch(err => {
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
