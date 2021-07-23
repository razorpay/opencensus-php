import Input from 'common/new-ui/Input';

export const Step3 = ({
  steps,
  selectedProvider,
  providers,
  provider,
  validationErrors,
  changeGatewayDetails,
}) => {
  return (
    <div class="row">
      {selectedProvider &&
        providers[selectedProvider] &&
        Object.keys(providers[selectedProvider]).map((item, index) =>
          item !== 'Payment Methods' ? (
            item !== 'Gateway Name' ? (
              <div className="col-xs-12" key={index}>
                <div className="row">
                  <div className="col-xs-3">
                    <label for="name" className="title-left">
                      {item}
                    </label>
                  </div>
                  <div className="col-xs-6">
                    {!steps[3].edit ? (
                      <label class="provider-details-read-only">
                        {provider.Gateway_details[item] ? provider.Gateway_details[item] : ''}
                      </label>
                    ) : (
                      <>
                        <Input
                          id={item.toLowerCase()}
                          class="Input--vLeft"
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
                    <label for="name" className="title-left">
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
                  </div>
                </div>
              </div>
            </>
          ),
        )}
    </div>
  );
};
