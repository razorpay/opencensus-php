import React, { Fragment } from 'react';

import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';

import { WalletsMultiSelect } from './WalletsMultiSelect';
import { getTPVOptions } from 'merchant/views/Navigator/components/AddProvider/util';

export function Step3({
  isEdit,
  selectedProvider,
  providers,
  provider,
  validationErrors,
  changeGatewayDetails,
  changeGatewayWallets,
}) {
  const selectedProviderDetails = providers?.[selectedProvider] || {};
  const { Gateway_details } = provider;
  const walletOptions =
    selectedProviderDetails?.['Payment Methods']?.meta_data?.wallet_metadata?.wallets || [];

  // Filter out the fields that are required in this step i.e step 3.
  const fields = Object.entries(selectedProviderDetails).reduce((acc, [label, value]) => {
    if (!['Gateway Name', 'optimizer_seamless_disabled'].includes(label)) {
      acc.push({ label, ...value });
    }
    return acc;
  }, []);

  return (
    <div className="row">
      {fields.map(({ label = '', data_type, data_value }) => {
        if (data_type === 'array') {
          if (label === 'Payment Methods') {
            return (
              <Fragment key={label}>
                <div className="col-xs-12">
                  <div className="row">
                    <div className="col-xs-3">
                      <label for="name" className="gateway-detail-title">
                        {label}
                      </label>
                    </div>
                    <div className="col-xs-9">
                      <div>
                        {data_value
                          .filter((method) => {
                            if (method === 'upi' && Gateway_details?.optimizer_seamless_disabled) {
                              return false;
                            }
                            return true;
                          })
                          .map((method) => (
                            <span className="payment-method-checkbox-span" key={method}>
                              <Input.Check
                                id={method}
                                fieldLabel={method}
                                checked={provider?.Gateway_details?.['Payment Methods']?.includes(
                                  method,
                                )}
                                onChange={(e) => changeGatewayDetails(e, method)}
                                disabled={
                                  !isEdit || (selectedProvider === 'paytm' && method === 'wallet')
                                } // Paytm onboarding enabled wallet method by default
                                autoRender
                              />
                            </span>
                          ))}
                      </div>
                      <p className="select-payment-method-desc">
                        Select the payment methods to be enabled for the {selectedProvider}
                        {selectedProvider === 'paytm' &&
                          '. Paytm wallet will be enabled by default.'}
                      </p>
                    </div>
                  </div>
                </div>

                {walletOptions?.length > 0 &&
                  provider?.Gateway_details?.['Payment Methods']?.includes('wallet') && (
                    <WalletsMultiSelect
                      walletOptions={walletOptions}
                      walletSelected={provider?.Gateway_details?.wallet_metadata?.wallets}
                      changeGatewayWallets={changeGatewayWallets}
                      disabled={selectedProvider === 'paytm'} // For paytm wallets get enabled by default
                    />
                  )}
              </Fragment>
            );
          } else if (label === 'TPV') {
            return (
              <div className="col-xs-12" key={label}>
                <div className="row tpv-field-wrapper">
                  <div className="col-xs-3">
                    <label for="name" className="gateway-detail-title">
                      <span>{label}</span>
                      <small className="help-content ml-4">
                        <i className="i i-info-circle" />
                        <Popover align="top" theme="dark">
                          <PopoverBody>
                            <div>
                              Third-Party Validation (TPV) of your customer’s bank accounts in
                              real-time. It is a mandatory requirement for merchants in the BFSI
                              (Banking, Financial Services and Insurance) sector.
                            </div>
                          </PopoverBody>
                        </Popover>
                      </small>
                    </label>
                  </div>

                  <div className="col-xs-9">
                    <Input.Radio
                      id={label}
                      defaultValue={provider.Gateway_details?.TPV ?? -1}
                      options={getTPVOptions(data_value)}
                      name={label.toLowerCase()}
                      onChange={changeGatewayDetails}
                      disabled={!isEdit}
                      autoRender
                    />
                  </div>
                </div>
              </div>
            );
          }
        }

        return (
          <div className="col-xs-12" key={label}>
            <div className="row">
              <div className="col-xs-3">
                <label for="name" className="gateway-detail-title">
                  {label}
                </label>
              </div>
              <div className="col-xs-6">
                {!isEdit ? (
                  <label className="provider-details-read-only">
                    {provider?.Gateway_details?.[label] || ''}
                  </label>
                ) : (
                  <>
                    <Input
                      id={label}
                      name={label?.toLowerCase()}
                      type={data_type === 'string' ? 'text' : 'number'}
                      value={provider?.Gateway_details?.[label] || ''}
                      placeholder={data_value}
                      onChange={changeGatewayDetails}
                    />
                    {validationErrors[label] && (
                      <div className="provider-details-validation-error">
                        {validationErrors[label]}
                      </div>
                    )}
                  </>
                )}
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
