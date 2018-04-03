import { Component, Children } from 'react';
import { connect } from 'react-redux';
import { Tour, TourStep, TourStepTitle, TourStepBody } from 'rzp/ui/Tour';
import * as ModalActions from 'rzp/modules/modals';
import LocalStorageService from 'rzp/utils/localStorage';
import { showOrHideTour } from 'merchant/modules/session';

@connect(state => state.session, {
  showOrHideTour,
  ...ModalActions,
})
export default class MerchantTour extends Component {
  state = {
    isTourActive: false,
    activeTourStep: 0,
    showOnboardingTour: false,
    tourInterrupted: false,
  };

  componentWillReceiveProps(nextProps) {
    if (this.props.isTourVisible !== nextProps.isTourVisible) {
      nextProps.isTourVisible ? this.showTour() : this.closeTour();
    }
  }

  showTour = () => {
    this.props.closeModal();

    LocalStorageService.setItem('tour_shown', true);
    this.setState({
      isTourActive: true,
      showOnboardingTour: true,
    });
  };

  closeTour = () => {
    this.props.closeModal();

    this.props.showOrHideTour(false);
    this.setState({ isTourActive: false, activeTourStep: 0 });
  };

  setTourStep = (activeTourStep, tourInterrupted) => {
    this.setState({ activeTourStep, tourInterrupted });
  };

  gotoNextTourStep = () => {
    this.setTourStep(this.state.activeTourStep + 1);
  };

  render() {
    const { tourInterrupted } = this.state;

    return (
      <div>
        <Tour
          tourActive={this.state.isTourActive}
          onFinish={this.closeTour}
          onSkip={() => {}}
          onStepChange={this.setTourStep}
          activeStep={this.state.activeTourStep}
          showOverlay={this.state.showOnboardingTour}
        >
          <TourStep to="#analytics-daterange-picker">
            <TourStepTitle>Date Range Presets</TourStepTitle>
            <TourStepBody>
              Now along with custom ranges, you can select from presets. Choose
              All Time to view your aggregates till date.
            </TourStepBody>
          </TourStep>
          <TourStep
            to="#analytics-keymetrics-section"
            className="overview-section-step has-padded-lens"
          >
            <TourStepTitle>Overview Section</TourStepTitle>
            <TourStepBody>
              <ul className="nav">
                <li>
                  - Payment Volume and Number of Payments indicate total number
                  of “Authorized” payments made in the selected time range.
                </li>
                <li>
                  - Saved Card Payments indicate the Percentage of saved card
                  payments, compared to all the card payments.
                </li>
                <li>
                  - Confused about what a term means? You can simply hover over
                  the tab to get a definition.
                </li>
                <li>
                  - Each of these tabs have a Graph section. More on this next.
                </li>
              </ul>
            </TourStepBody>
          </TourStep>
          <TourStep to="#keymetrics-grouping" align="left">
            <TourStepTitle>Smart Filters</TourStepTitle>
            <TourStepBody>
              <div>See how your payments fared by filtering your graphs:</div>
              <ul className="nav">
                <li>- By Total Volume</li>
                <li>- By Payment Methods</li>
                <li>- By Platforms</li>
              </ul>
            </TourStepBody>
          </TourStep>
          <TourStep to="#keymetrics-download" align="left">
            <TourStepTitle>Quick Download</TourStepTitle>
            <TourStepBody>
              You can export your graphs as CSV files or download them as images
              to use in your PPTs!
            </TourStepBody>
          </TourStep>
          <TourStep
            to="#payment-methods-treemap"
            align="top"
            className="has-padded-lens"
          >
            <TourStepTitle>Payment Insights</TourStepTitle>
            <TourStepBody>
              <ul className="nav">
                <li>
                  - <b>Gain insights</b> about your top revenue generating
                  payment methods.
                </li>
                <li>
                  - <b>Click tiles</b> to drill down the hierarchy of a
                  particular payment method.
                </li>
                <li>
                  - <b>Hover</b> to view information for smaller tiles.
                </li>
                <li>
                  - <b>Select filters</b> to view by Payment Volume or Number of
                  Payments
                </li>
                <li>
                  - <b>Download</b> as a CSV file or an Image
                </li>
              </ul>
            </TourStepBody>
          </TourStep>
          <TourStep to="#traffic-split" align="top">
            <TourStepTitle>Traffic Split on Platforms</TourStepTitle>
            <TourStepBody>
              See which platforms are contributing to your payment
              traffic.Filter by Payment volume or Number of Payments.
            </TourStepBody>
          </TourStep>
          {!tourInterrupted ? (
            <TourStep to="#profile-dropdown" className="profile-dropdown-step">
              <TourStepTitle>Tour Complete!</TourStepTitle>
              <TourStepBody>
                You can always find the tour here for later reference.
              </TourStepBody>
            </TourStep>
          ) : (
            <TourStep to="#profile-dropdown" className="profile-dropdown-step">
              <TourStepTitle>Tour incomplete!</TourStepTitle>
              <TourStepBody>
                We reccommend that you complete the tour to make the most of the
                new features. You can find it here when you want to take it.
              </TourStepBody>
            </TourStep>
          )}
        </Tour>
      </div>
    );
  }
}
