import { connect } from 'react-redux';
import { handleProductQuickGuide } from 'merchant/modules/onboarding';

@connect(
  state => ({
    onboarding: state.onboarding.products,
  }),
  { handleProductQuickGuide }
)
export default class TakeATourButton extends React.Component {
  onClick = () => {
    this.props.handleProductQuickGuide({
      feature: this.props.feature,
      showOnboarding: false,
      isQuickGuideOpen: true,
      isTour: true,
    });

    window.rzpAnalytics({
      eventCategory: `Restart Tutorial (${this.props.feature})`,
      eventAction: `Need help? Take a Tour CTA `,
    });
  };

  render() {
    const currentOnboarding = this.props.onboarding[this.props.feature];

    if (currentOnboarding.isQuickGuideOpen) return null;

    return (
      <span class="btn btn-link" onClick={this.onClick}>
        <i class="i i-lightbulb" />
        Need help? Take a tour
      </span>
    );
  }
}
