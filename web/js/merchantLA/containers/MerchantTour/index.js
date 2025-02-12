import { Component } from 'react';
import { connect } from 'react-redux';

import { Tour, TourStep, TourStepTitle, TourStepBody } from 'common/ui/Tour';
import { setItem } from 'common/utils/localStorage';
import scrollTo from 'common/utils/scrollTo';
import { showOrHideTour } from 'merchant/reducers/session';
import * as ModalActions from 'merchant_common/reducers/modals';

import { trackSkipTour, trackFinishTour } from './ga';

class MerchantTour extends Component {
  state = {
    isTourActive: false,
    activeTourStep: 0,
    showOnboardingTour: false,
    isTourInterrupted: false,
  };

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.isTourVisible !== nextProps.isTourVisible) {
      nextProps.isTourVisible ? this.showTour() : this.closeTour();
    }
  }

  showTour = () => {
    this.props.closeModal();

    setItem('tour_shown', true);
    this.setState({
      isTourActive: true,
      showOnboardingTour: true,
    });
  };

  closeTour = () => {
    const { isTourInterrupted } = this.state;

    this.props.closeModal();

    scrollTo({ endPos: 0 });
    this.props.showOrHideTour(false);
    this.setState({ isTourActive: false, activeTourStep: 0 });

    if (!isTourInterrupted) {
      trackFinishTour();
    }
  };

  setTourStep = (activeTourStep, lastStep, isTourInterrupted) => {
    this.setState({ activeTourStep, isTourInterrupted });

    if (isTourInterrupted) {
      trackSkipTour(lastStep + 1);
    }
  };

  gotoNextTourStep = () => {
    this.setTourStep(this.state.activeTourStep + 1);
  };

  render() {
    const { isTourInterrupted } = this.state;

    return (
      <div>
        <Tour
          tourActive={this.state.isTourActive}
          onFinish={this.closeTour}
          onStepChange={this.setTourStep}
          activeStep={this.state.activeTourStep}
          showOverlay={this.state.showOnboardingTour}
        >
          <TourStep to="#analytics-daterange-picker">
            <TourStepTitle>Date Range</TourStepTitle>
            <TourStepBody>
              To begin with, you can select any date range to view its graphs.
            </TourStepBody>
          </TourStep>
          <TourStep
            to="#analytics-keymetrics-section"
            className="overview-section-step has-padded-lens"
          >
            <TourStepTitle>Overview of Payments</TourStepTitle>
            <TourStepBody>
              This section shows an overview of your payments. You can click on the cards to view
              detailed graphs.
            </TourStepBody>
          </TourStep>
          <TourStep to="#keymetrics-grouping" align="left">
            <TourStepTitle>Detailed Charts</TourStepTitle>
            <TourStepBody>
              You can also group the charts by Payment Methods or Platforms.
            </TourStepBody>
          </TourStep>
          <TourStep to="#keymetrics-download" align="left">
            <TourStepTitle>Quick Download</TourStepTitle>
            <TourStepBody>The charts can be downloaded as images or as CSV.</TourStepBody>
          </TourStep>
          <TourStep to="#payment-methods-treemap" align="top" className="has-padded-lens">
            <TourStepTitle>Payment Insights</TourStepTitle>
            <TourStepBody>
              This chart shows you a detailed breakdown of payments by Payment Methods.
            </TourStepBody>
          </TourStep>
          <TourStep to="#traffic-split" align="top">
            <TourStepTitle>Payment Split on Platforms</TourStepTitle>
            <TourStepBody>
              At last, here you can get an overview of payment data by Platform.
            </TourStepBody>
          </TourStep>
          {!isTourInterrupted ? (
            <TourStep to="#profile-dropdown" className="profile-dropdown-step">
              <TourStepTitle>Tour Complete!</TourStepTitle>
              <TourStepBody>You can always find the tour here for later reference.</TourStepBody>
            </TourStep>
          ) : (
            <TourStep to="#profile-dropdown" className="profile-dropdown-step">
              <TourStepTitle>Tour incomplete!</TourStepTitle>
              <TourStepBody>
                We reccommend that you complete the tour to make the most of the new features. You
                can find it here when you want to take it.
              </TourStepBody>
            </TourStep>
          )}
        </Tour>
      </div>
    );
  }
}

export default connect((state) => state.session, {
  showOrHideTour,
  ...ModalActions,
})(MerchantTour);
