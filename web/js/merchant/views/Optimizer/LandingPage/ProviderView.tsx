import React from 'react';
import { Link } from 'react-router-dom';

import { trackOptimizerEvents } from 'merchant/views/Optimizer/track';
import { gatewayLogos, RZP_GATEWAYS } from 'merchant/views/Optimizer/utils';

import { Provider } from 'merchant/views/Optimizer/types';

interface ProviderViewProps {
  provider: Provider;
}

export const ProviderView = ({ provider }: ProviderViewProps): JSX.Element => {
  const viewItem = (providerDetail) => {
    return (
      <div
        className={`gateway-provider-block gateway-provider-block-bg${
          RZP_GATEWAYS.includes(providerDetail?.Gateway?.toLowerCase()) ? ' gateway-no-cursor' : ''
        }`}
      >
        <div className="provider-img-holder">
          <img
            src={
              providerDetail.Gateway_details?.image_url ??
              gatewayLogos[providerDetail.Gateway.toLowerCase()]
            }
            alt={`${providerDetail.Gateway}-logo`}
            data-testid="gateway-logo"
          />
        </div>
        <div className="gateway-provider-block--details">
          <h3>
            {providerDetail.Provider_name ? providerDetail.Provider_name : providerDetail.Gateway}
            {!RZP_GATEWAYS.includes(providerDetail.Gateway.toLowerCase()) && (
              <img
                src="https://cdn.razorpay.com/static/assets/rewards/rewards_list_up_vector.svg"
                className="arrow-img arrow-hide"
                alt="arrow-icon"
              />
            )}
          </h3>
          <div className="gateway-provider-block--details--methods">
            {(RZP_GATEWAYS.includes(providerDetail.Gateway.toLowerCase()) ||
              providerDetail.Status === 'activated') &&
            providerDetail.Gateway_details['Payment Methods']?.length > 0 ? (
              <p title={providerDetail.Gateway_details['Payment Methods'].join(', ')}>
                {providerDetail.Gateway_details['Payment Methods'].join(', ')}
              </p>
            ) : (
              <p>pending</p>
            )}
          </div>
        </div>
      </div>
    );
  };

  const trackEventOnView = () => {
    trackOptimizerEvents({
      objectName: 'provider view',
      actionName: 'click',
      properties: {
        provider_name: provider?.Provider_name,
        gateway: provider?.Gateway,
      },
    });
  };

  return (
    <div className="col-xs-3 gateway-provider-col">
      {RZP_GATEWAYS.includes(provider?.Gateway?.toLowerCase()) ? (
        viewItem(provider)
      ) : (
        <Link to={`/optimizer/provider/${provider?.Terminal_id}`} onClick={trackEventOnView}>
          {viewItem(provider)}
        </Link>
      )}
    </div>
  );
};
