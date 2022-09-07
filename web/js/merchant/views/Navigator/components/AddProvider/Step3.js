import Input from 'common/new-ui/Input';
import { WalletsMultiSelect } from './WalletsMultiSelect';

export const Step3 = ({
  steps,
  selectedProvider,
  providers,
  provider,
  validationErrors,
  changeGatewayDetails,
  changeGatewayWallets,
}) => {
  const walletOptions =
    providers?.[selectedProvider]?.['Payment Methods']?.meta_data?.wallet_metadata?.wallets || [];
  return (
    <div className="row">
      {selectedProvider &&
        providers[selectedProvider] &&
        Object.keys(providers[selectedProvider]).map((item, index) =>
          item !== 'Payment Methods' ? (
            item !== 'Gateway Name' ? (
              <div className="col-xs-12" key={index}>
                <div className="row">
                  <div className="col-xs-3">
                    <label for="name" className="title-left gateway-detail-title">
                      {item}
                    </label>
                  </div>
                  <div className="col-xs-6">
                    {!steps[3].edit ? (
                      <label className="provider-details-read-only">
                        {provider?.Gateway_details?.[item] ? provider.Gateway_details[item] : ''}
                      </label>
                    ) : (
                      <>
                        <Input
                          id={item.toLowerCase()}
                          className="Input--vLeft"
                          name={item.toLowerCase()}
                          type={
                            providers[selectedProvider][item].data_type === 'string'
                              ? 'text'
                              : 'number'
                          }
                          value={provider.Gateway_details[item]}
                          placeholder={providers[selectedProvider][item].data_value}
                          onChange={(e) => changeGatewayDetails(e, item)}
                        />
                        {validationErrors[item] && (
                          <div className="provider-details-validation-error">
                            {validationErrors[item]}
                          </div>
                        )}
                      </>
                    )}
                  </div>
                </div>
              </div>
            ) : null
          ) : (
            <>
              <div className="col-xs-12">
                <div className="row">
                  <div className="col-xs-3">
                    <label for="name" className="title-left gateway-detail-title">
                      Payment Methods
                    </label>
                  </div>
                  <div className="col-xs-9">
                    {providers[selectedProvider][item].data_value.map((method, index2) => (
                      <span className="payment-method-checkbox-span" key={index2}>
                        <Input.Check
                          fieldLabel={method}
                          checkboxMaskLabel={false}
                          key={index}
                          checked={
                            provider.Gateway_details['Payment Methods'].indexOf(method) !== -1
                          }
                          onChange={(e) => changeGatewayDetails(e, method)}
                          disabled={selectedProvider === 'paytm' && method === 'wallet'} // Paytm onboarding enabled wallet method by default
                        />
                      </span>
                    ))}
                  </div>
                </div>
              </div>
              <div className="col-xs-12">
                <div className="row">
                  <div className="col-xs-3" />
                  <div className="col-xs-6 select-payment-method-desc">
                    Select the payment methods to be enabled for the {selectedProvider}
                    {selectedProvider === 'paytm' && '. Paytm wallet will be enabled by default.'}
                  </div>
                </div>
              </div>
              {walletOptions?.length > 0 &&
                provider.Gateway_details['Payment Methods'].indexOf('wallet') !== -1 && (
                  <WalletsMultiSelect
                    walletOptions={walletOptions}
                    walletSelected={provider.Gateway_details.wallet_metadata.wallets}
                    changeGatewayWallets={changeGatewayWallets}
                    disabled={selectedProvider === 'paytm'} // For paytm wallets get enabled by default
                  />
                )}
            </>
          ),
        )}
    </div>
  );
};
