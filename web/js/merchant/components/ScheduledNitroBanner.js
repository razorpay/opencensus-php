import React, { Component } from 'react';
import RTracking from 'react-tracking';
import HubspotCAForm from 'common/ui/NotificationsDropdown/HubspotCAForm';

@RTracking(() => window.rzpQ.component('ScheduledNitroBanner'))
export default class ScheduledNitroBanner extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showHubSpotCAForm: false,
    };
  }

  componentDidMount() {
    const tracking = this.props.tracking;
    tracking.trackEvent(
      window.rzpQ.merchantActions().success(`${this.props.fromWhere}_display_promo_notification1`),
    );
  }

  trackEvents = (fromWhere) => {
    const tracking = this.props.tracking;

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`${fromWhere}_click_promo_notification1_cta1`),
    );
  };

  hideHubSpotCAForm = () => {
    this.setState({ showHubSpotCAForm: false });
  };

  render() {
    const { showHubSpotCAForm } = this.state;

    return (
      <div class="schedule-enable-container">
        Reduce transaction fee to 1.65% with a RazorpayX Current Account
        <a
          class="Button--secondary Button scheduled-btn-act btn-border"
          target="_blank"
          onClick={(e) => {
            this.setState({ showHubSpotCAForm: true });
            this.trackEvents(this.props.fromWhere);
          }}
        >
          Learn More
        </a>
        <HubspotCAForm
          shouldShowModal={showHubSpotCAForm}
          hideModal={this.hideHubSpotCAForm}
          fromWhere={this.props.fromWhere}
        />
      </div>
    );
  }
}
