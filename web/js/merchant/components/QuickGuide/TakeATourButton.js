import { connect } from 'react-redux';
import { handleProductQuickGuide } from 'merchant/modules/onboarding';

@connect(null, { handleProductQuickGuide })
export default class TakeATourButton extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  onClick = () => {
    this.context.confirm({
      header: 'Retake the Tour',
      message: 'Are you sure you want to take tour again?',
      affirmativeLabel: 'Yes',
      abortLabel: 'No',
      action: () => {
        this.props.handleProductQuickGuide({
          feature: this.props.feature,
          showOnboarding: true,
          isQuickGuideOpen: true,
          isTour: true,
        });

        window.rzpAnalytics({
          eventCategory: `Restart Tutorial (${this.props.feature})`,
          eventAction: `Need help? Take a Tour CTA `,
        });
      },
    });
  };

  render() {
    return (
      <span class="btn btn-link" onClick={this.onClick}>
        <i class="i i-lightbulb" />
        Need help? Take a tour
      </span>
    );
  }
}
