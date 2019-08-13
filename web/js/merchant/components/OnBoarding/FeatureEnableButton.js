import { connect } from 'react-redux';

import { AsyncBtn } from 'component/Button';

import { classList } from 'common/util';

import { showNotification } from 'rzp/modules/notifications';

import { updateFeatures } from 'merchant/modules/config';
import { handleProductQuickGuide } from 'merchant/modules/onboarding';

@connect(
  state => {
    return {
      user: state.session.user,
      onboarding: state.onboarding,
    };
  },
  { updateFeatures, showNotification, handleProductQuickGuide }
)
export default class FeatureEnableButton extends React.Component {
  constructor(props) {
    super();

    this.state = {
      isSuccess: false,
    };
  }

  handleEnableFeature = () => {
    const data = {
      features: {
        [this.props.feature]: 1,
      },
    };

    return this.props
      .updateFeatures(data, this.props.user.current)
      .then(res => {
        this.props.showNotification({
          type: 'success',
          message: `${this.props.feature} has been enabled!`,
        });

        this.setState({
          isSuccess: true,
        });

        this.props.onClick && this.props.onClick(res);

        setTimeout(() => location.reload());
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.props.onClick && this.props.onClick(err);
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
