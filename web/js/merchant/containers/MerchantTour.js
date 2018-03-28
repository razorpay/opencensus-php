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

  setTourStep = activeTourStep => {
    this.setState({ activeTourStep });
  };

  setToLastInTour = () => {
    this.setTourStep(3);
  };

  gotoNextTourStep = () => {
    this.setTourStep(this.state.activeTourStep + 1);
  };

  render() {
    return (
      <div>
        <Tour
          tourActive={this.state.isTourActive}
          onFinish={() => {}}
          onSkip={() => {}}
          onStepChange={this.setTourStep}
          activeStep={this.state.activeTourStep}
          showOverlay={this.state.showOnboardingTour}
        >
          <TourStep
            to="#analytics-daterange-picker"
            align="bottom"
            className="datepicker-step"
          >
            <TourStepTitle>Date Range Presets</TourStepTitle>
            <TourStepBody>
              Now along with custom ranges, you can select from presets. Choose
              All Time to view your aggregates till date.
            </TourStepBody>
          </TourStep>
          <TourStep
            to="#profile-dropdown"
            align="bottom"
            className="profile-dropdown-step"
          >
            <TourStepTitle>
              You can always find the tour here for later reference.
            </TourStepTitle>
          </TourStep>
        </Tour>
      </div>
    );
  }
}
