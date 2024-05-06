import React from 'react';
import { Link } from 'react-router-dom';

import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

import { gatewayLogos, rzpGateways } from './util';

export default class ProviderNewView extends React.Component {
  viewItem = (provider) => {
    return (
      <div
        className={`gateway-provider-block gateway-provider-block-bg${
          rzpGateways.includes(provider?.Gateway?.toLowerCase()) ? ' gateway-no-cursor' : ''
        }`}
      >
        <div className="provider-img-holder">
          <img src={gatewayLogos[provider.Gateway.toLowerCase()]} />
        </div>
        <div className="gateway-provider-block--details">
          <h3>
            {provider.Provider_name ? provider.Provider_name : provider.Gateway}
            {!rzpGateways.includes(provider.Gateway.toLowerCase()) && (
              <img
                src="https://cdn.razorpay.com/static/assets/rewards/rewards_list_up_vector.svg"
                className="arrow-img arrow-hide"
              />
            )}
          </h3>
          <div className="gateway-provider-block--details--methods">
            {provider.Gateway_details['Payment Methods']?.length > 0 ? (
              <p title={provider.Gateway_details['Payment Methods'].join(', ')}>
                {provider.Gateway_details['Payment Methods'].join(', ')}
              </p>
            ) : (
              <p>pending</p>
            )}
          </div>
        </div>
      </div>
    );
  };

  trackEventOnView = () => {
    const { provider } = this.props;
    trackOptimizerEvents({
      objectName: 'provider view',
      actionName: 'click',
      properties: {
        provider_name: provider?.Provider_name,
        gateway: provider?.Gateway,
      },
    });
  };

  render() {
    const { provider } = this.props;
    return (
      <div className="col-xs-3 gateway-provider-col">
        {rzpGateways.includes(provider?.Gateway?.toLowerCase()) ? (
          this.viewItem(provider)
        ) : (
          <Link to={`/optimizer/provider/${provider?.Terminal_id}`} onClick={this.trackEventOnView}>
            {this.viewItem(provider)}
          </Link>
        )}
      </div>
    );
  }
}
