import React from 'react';
import { Link } from 'react-router-dom';
import { gatewayLogos } from './util';

export default class ProviderNewView extends React.Component {
  render() {
    const { provider } = this.props;
    return (
      <div className="col-xs-3 gateway-provider-col">
        <Link to={`/optimizer/provider/${provider.Terminal_id}`}>
          <div className="gateway-provider-block gateway-provider-block-bg">
            <div class="provider-img-holder">
              <img src={gatewayLogos[provider.Gateway.toLowerCase()]} />
            </div>
            <div className="gateway-provider-block--details">
              <h3>{provider.Provider_name}</h3>
              <div className="gateway-provider-block--details--methods">
                <p title={provider.Gateway_details['Payment Methods'].join(', ')}>
                  {provider.Gateway_details['Payment Methods'].join(', ')}
                </p>
              </div>
            </div>
          </div>
        </Link>
      </div>
    );
  }
}
