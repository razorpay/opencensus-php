import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Amount from 'common/ui/Amount';
import { fetchSubscriptionOffersUsage } from 'merchant/reducers/offers/offerDetails';

export default class SubscriptionUsageDetails extends React.Component {
  state = {
    isLoading: true,
    isError: false,
    data: {},
  };

  componentDidMount() {
    this.fetchSubscriptionOffersUsage(this.props.id);
  }

  fetchSubscriptionOffersUsage = (id) => {
    fetchSubscriptionOffersUsage(id)
      .then((resp) => {
        this.setState({
          isLoading: false,
          data: resp.data,
        });
      })
      .catch(() => {
        this.setState({
          isError: true,
        });
      });
  };

  render() {
    const {
      data: { offer_usage, active_on, total_discount },
      isLoading,
      isError,
    } = this.state;
    return (
      <div className="subscription-details">
        {isError ? (
          <div className="error">
            Oops, looks like an unexpected error occured for offer overview.Please refresh this page
            or try again after sometime.
          </div>
        ) : (
          <React.Fragment>
            <div>
              <div className="heading">Offer Usage</div>
              <div className="count">{isLoading ? <PlaceholderLoader /> : offer_usage}</div>
            </div>
            <div className="divider" />
            <div>
              <div className="heading">Active On</div>
              <div className="count">{isLoading ? <PlaceholderLoader /> : active_on}</div>
            </div>
            <div className="divider" />
            <div>
              <div className="heading">Total Discounts Applied</div>
              <div className="count">
                {isLoading ? <PlaceholderLoader /> : <Amount value={total_discount} />}
              </div>
            </div>
          </React.Fragment>
        )}
      </div>
    );
  }
}
