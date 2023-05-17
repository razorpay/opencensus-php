import React, { useState } from 'react';
import Input from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import debounce from 'lodash/debounce';
import { SeamlessOption } from 'merchant/views/Navigator/components/Provider/SeamlessOption';
import SeamlessNote from 'merchant/views/Navigator/components/Provider/SeamlessNote';
import { popularGateways, gatewayLogos } from 'merchant/views/Navigator/components/util';
import {
  SEAMLESS_CONTENT,
  SEAMLESS_PROVIDERS,
  SEAMLESS_OPTIONS,
} from 'merchant/views/Navigator/constants';

/**
 * Instant(beta) -> optimizer_seamless_disabled is true, which means seamless option is disabled
 * Server-to-Server -> optimizer_seamless_disabled is false, which means seamless option is enabled
 */

export const Step1 = (props) => {
  const {
    steps,
    providers,
    loadingProviders,
    selectProvider,
    selectedProvider,
    changeGateway,
    toggleSeamless,
    isEdit,
    gatewayDetails,
  } = props;

  const [search, setSearch] = useState('');
  const [filteredProviders, setFilteredProviders] = useState({});

  const providersObjectKeys = Object.keys(providers) ?? [];
  const selectedProviderDetails = providers?.[selectedProvider] ?? {};
  const showSeamlessNote = selectedProvider && steps[1].edit;
  const seamlessOptionExist =
    SEAMLESS_PROVIDERS?.includes(selectedProvider) &&
    providers?.[selectedProvider]?.hasOwnProperty('optimizer_seamless_disabled');

  const filterOnSearch = (e) => {
    const val = e.target.value;
    if (val.trim() === '') {
      setSearch('');
      setFilteredProviders({});
    } else {
      const res = providersObjectKeys.reduce((obj, item) => {
        if (item?.toLowerCase()?.startsWith(val.toLowerCase())) {
          obj[item] = providers[item];
        }
        return obj;
      }, {});

      setSearch(val);
      setFilteredProviders(res);
    }
  };

  const renderProviderItem = (provider, index) => {
    const SELECTED_PROVIDER = providers?.[provider] || {};
    const PAYMENT_METHODS = SELECTED_PROVIDER?.['Payment Methods']?.data_value?.join(', ') ?? '';

    const onSelect = () => selectProvider(provider);

    return (
      <div
        key={`${provider}-${index}`}
        className="col-xs-4 gateway-provider-col"
        data-testid="gateway-provider"
      >
        <div className="gateway-provider-block" onClick={onSelect}>
          <div className="provider-img-holder">
            <img alt={provider} src={gatewayLogos[provider?.toLowerCase()]} />
          </div>
          <div className="gateway-provider-block--details">
            <h3>{SELECTED_PROVIDER?.['Gateway Name']?.data_value}</h3>
            <div className="gateway-provider-block--details--methods">
              <p title={PAYMENT_METHODS}>{PAYMENT_METHODS}</p>
            </div>
          </div>
        </div>
      </div>
    );
  };

  const renderProvidersList = () => {
    if (Object.keys(filteredProviders).length > 0) {
      return Object.keys(filteredProviders).map((provider, index) => {
        return renderProviderItem(provider, index);
      });
    }

    if (!search && providersObjectKeys.length > 0) {
      return (
        <>
          <div className="col-xs-12 popular-gateways-header my-2">
            <img
              alt="popular"
              src="https://cdn.razorpay.com/static/assets/merchant-dash/popular_provider.svg"
            />
            <span>Popular Gateways</span>
          </div>
          {popularGateways.map((provider, index) => renderProviderItem(provider, index))}
          <div className="col-xs-12 all-gateways-header mb-2">All Gateways</div>
          {providersObjectKeys.map((provider, index) => {
            return renderProviderItem(provider, index);
          })}
        </>
      );
    }

    return <div className="col-xs-12 all-gateways-header mb-2">No Providers Found</div>;
  };

  const handleGatewayChange = () => {
    setSearch('');
    setFilteredProviders({});
    changeGateway();
  };

  const onRadioChange = ({ target }) => {
    const parsedValue = target.value === 'true';
    toggleSeamless(parsedValue);
  };

  const radioFeedback = () => {
    const contentExist = SEAMLESS_CONTENT?.hasOwnProperty(selectedProvider);
    const seamlessRadioValue = gatewayDetails?.hasOwnProperty('optimizer_seamless_disabled');

    if (!(contentExist && seamlessRadioValue)) return null;

    return (
      <SeamlessNote
        type="info"
        seamlessDisabled={gatewayDetails?.optimizer_seamless_disabled}
        providers={providers}
        selectedProvider={selectedProvider}
        isEdit={isEdit}
      />
    );
  };

  if (loadingProviders) {
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  return (
    <div>
      <div className="row gateway-section-row">
        <div className="col-xs-2">
          <label for="description" className="title-left">
            Gateway
          </label>
        </div>

        <div className="col-xs-6">
          {!steps[1].edit && selectedProvider ? (
            <label className="provider-details-read-only" data-testid="provider-readOnly">
              <div className="provider-logo-holder">
                <img src={gatewayLogos?.[selectedProvider?.toLowerCase()]} alt="gateway-logo" />
              </div>
              {selectedProviderDetails?.['Gateway Name']?.data_value}
            </label>
          ) : selectedProvider ? (
            <>
              <div className="selected-gateway-provider" data-testid="selected-gateway">
                <div class="provider-img-holder">
                  <img src={gatewayLogos?.[selectedProvider?.toLowerCase()]} alt="gateway-logo" />
                </div>
                <div className="gateway-provider-block--details">
                  <h3>{selectedProviderDetails?.['Gateway Name']?.data_value}</h3>
                  <p className="gateway-provider-block--details--methods">
                    {selectedProviderDetails?.['Payment Methods']?.data_value?.join(', ')}
                  </p>
                </div>
              </div>
              {steps[1].edit && !isEdit && (
                <div className="change-gateway" onClick={handleGatewayChange}>
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
              aria-label="Search Gateway"
              class="Input--vLeft"
              onChange={debounce(filterOnSearch, 300)}
            />
          )}
        </div>
      </div>

      {!selectedProvider && (
        <div className="row list-providers-section">
          <div className="col-xs-10 col-xs-offset-2 p-0">
            <div className="row">{renderProvidersList()}</div>
          </div>
        </div>
      )}

      {showSeamlessNote && seamlessOptionExist && (
        <div className="row mt-1">
          <div className="col-xs-2">
            <label for="description" className="title-left mt-1">
              Integration type
            </label>
          </div>
          <div className="col-xs-10">
            <Input.Radio
              className="Input--vTop Input--capitalize"
              name="optimizer_seamless_disabled"
              size="small"
              noDefaultSelectedValue={true}
              defaultValue={gatewayDetails?.optimizer_seamless_disabled}
              options={SEAMLESS_OPTIONS}
              onChange={onRadioChange}
              description={radioFeedback}
            />
          </div>
        </div>
      )}

      {showSeamlessNote && !seamlessOptionExist && (
        <div className="row mt-1 mb-2">
          <div className="col-xs-10 col-xs-offset-2">
            <SeamlessOption
              type="warning"
              providers={providers}
              selectedProvider={selectedProvider}
            />
          </div>
        </div>
      )}
    </div>
  );
};
