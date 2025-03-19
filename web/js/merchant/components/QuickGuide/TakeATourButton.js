import { Component } from 'react';
import { connect } from 'react-redux';
import { handleProductQuickGuide } from 'merchant/reducers/onboarding';
import { bindActionCreators } from 'redux';
import PropTypes from 'prop-types';
import ShowWhen from 'merchant/components/ShowWhen';
class TakeATourButton extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  onClick = () => {
    if (this.props.onClick) this.props.onClick();

    this.context.confirm({
      header: 'Restart the Tour?',
      message: 'This tour will give you a quick guide on this product.',
      affirmativeLabel: 'Yes',
      abortLabel: 'No',
      action: () => {
        this.props.handleProductQuickGuide({
          feature: this.props.feature,
          showOnboarding: true,
          isQuickGuideOpen: true,
          isTour: true,
        });

        window.rzpAnalytics?.({
          eventCategory: `Restart Tutorial (${this.props.feature})`,
          eventAction: `Need help? Take a Tour CTA `,
        });

        if (this.props.onSuccess) this.props.onSuccess();
      },
      abort: () => {
        if (this.props.onAbort) this.props.onAbort();
      },
    });
  };

  render() {
    return (
      <ShowWhen additionalCondition={(user) => !user.isOrgAxis && !user.isProductTourScreenHidden}>
        <span className="btn btn-link" onClick={this.onClick}>
          <i className="i i-lightbulb" /> Need help? Take a tour
        </span>
      </ShowWhen>
    );
  }
}

export default connect(null, (dispatch) =>
  bindActionCreators({ handleProductQuickGuide }, dispatch),
)(TakeATourButton);
