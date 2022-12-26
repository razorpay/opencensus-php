import Input from 'common/new-ui/Input';
import { SeamlessOption } from 'merchant/views/Navigator/components/Provider/SeamlessOption';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';

export const Step1 = ({
  steps,
  providers,
  selectedProvider,
  filterProvidersOnSearch,
  filteredProviders,
  listProviders,
  changeGateway,
  isEdit,
}) => {
  return (
    <div class="row">
      <div className="col-xs-12">
        <div className="row gateway-section-row">
          <div className="col-xs-2">
            <label for="description" className="title-left">
              Gateway
            </label>
          </div>
          <div className="col-xs-6">
            {!steps[1].edit && selectedProvider ? (
              <label className="provider-details-read-only">
                <div className="provider-logo-holder">
                  <img src={gatewayLogos[selectedProvider.toLowerCase()]} alt="gateway-logo" />
                </div>
                {providers?.[selectedProvider]?.['Gateway Name']?.data_value}
              </label>
            ) : selectedProvider ? (
              <>
                <div className="selected-gateway-provider">
                  <div class="provider-img-holder">
                    <img src={gatewayLogos[selectedProvider.toLowerCase()]} alt="gateway-logo" />
                  </div>
                  <div className="gateway-provider-block--details">
                    <h3>{providers?.[selectedProvider]?.['Gateway Name']?.data_value}</h3>
                    <div className="gateway-provider-block--details--methods">
                      <p
                        title={providers[selectedProvider]['Payment Methods'].data_value.join(', ')}
                      >
                        {providers[selectedProvider]['Payment Methods'].data_value.join(', ')}
                      </p>
                    </div>
                  </div>
                </div>
                {steps[1].edit && !isEdit && (
                  <div className="change-gateway" onClick={changeGateway}>
                    <i className="i i-pencil-edit" /> Change Gateway
                  </div>
                )}
              </>
            ) : (
              <Input
                addonBefore={<i class="i i-search" />}
                type="text"
                name="gateway"
                placeholder="Search Gateway"
                class="Input--vLeft"
                onChange={(e) => {
                  filterProvidersOnSearch(e.target.value);
                }}
              />
            )}
          </div>
        </div>
      </div>
      {!selectedProvider && (
        <div className="col-xs-12">
          <div className="row list-providers-section">
            <div className="col-xs-2" />
            <div className="col-xs-10">
              <div className="row">{listProviders(filteredProviders)}</div>
            </div>
          </div>
        </div>
      )}
      {selectedProvider && steps[1].edit && !isEdit && (
        <SeamlessOption providers={providers} selectedProvider={selectedProvider} />
      )}
    </div>
  );
};
