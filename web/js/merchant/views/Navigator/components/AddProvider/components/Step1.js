import React from 'react';
import Input from 'common/new-ui/Input';
import { SeamlessOption } from 'merchant/views/Navigator/components/Provider/SeamlessOption';
import SeamlessNote from 'merchant/views/Navigator/components/Provider/SeamlessNote';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';
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
    selectedProvider,
    filterProvidersOnSearch,
    filteredProviders,
    listProviders,
    changeGateway,
    toggleSeamless,
    isEdit,
    gatewayDetails,
  } = props;

  const selectedProviderDetails = providers?.[selectedProvider] ?? {};
  const showSeamlessNote = selectedProvider && steps[1].edit;
  const seamlessOptionExist =
    SEAMLESS_PROVIDERS?.includes(selectedProvider) &&
    providers?.[selectedProvider]?.hasOwnProperty('optimizer_seamless_disabled');

  const onRadioChange = (e) => toggleSeamless(JSON.parse(e?.target?.value));

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
            <label className="provider-details-read-only">
              <div className="provider-logo-holder">
                <img src={gatewayLogos?.[selectedProvider?.toLowerCase()]} alt="gateway-logo" />
              </div>
              {selectedProviderDetails?.['Gateway Name']?.data_value}
            </label>
          ) : selectedProvider ? (
            <>
              <div className="selected-gateway-provider">
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

      {!selectedProvider && (
        <div className="row list-providers-section">
          <div className="col-xs-10 col-xs-offset-2 p-0">
            <div className="row">{listProviders(filteredProviders)}</div>
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
