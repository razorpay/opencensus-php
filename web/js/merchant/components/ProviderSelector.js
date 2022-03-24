import { useState } from 'react';
import ClickOutside from 'merchant/views/Navigator/components/ClickOutside';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';

const ProviderSelector = ({ name, providers, provider, setProvider }) => {
  const [showList, setShowList] = useState(false);

  const options = [
    {
      name: 'All',
      value: '',
      gateway: '',
    },
  ];
  const providerOps = providers?.filter((p) => p.Gateway != 'smart_router');
  providerOps?.forEach((p) => {
    const val = p.Gateway === 'razorpay' ? p.Gateway : p.Terminal_id;
    options.push({
      name: p.Provider_name || p.Gateway,
      value: val,
      gateway: p.Gateway,
    });
  });

  return (
    <ClickOutside
      onClickOutside={() => {
        setShowList(false);
      }}
    >
      <div className="provider-selector">
        <input
          readOnly
          type="text"
          size="half_big"
          className="Input--vTop form-control"
          name={name}
          value={provider.name}
          onFocus={() => setShowList(true)}
        />
        {showList && (
          <div className="input-select">
            <ul className="unlisted">
              {options.map((o, index) => {
                return (
                  <li
                    key={index}
                    onClick={() => {
                      setProvider(o);
                      setShowList(false);
                    }}
                  >
                    <div>
                      <div className="row">
                        <div className="col-xs-10">
                          {o.name !== 'All' && (
                            <div className="recommended-provider-img-block">
                              <img src={gatewayLogos[o.gateway]} />
                            </div>
                          )}
                          <b className="optn-text">{o.name}</b>
                        </div>
                        <div className="col-xs-2">
                          {provider.value === o.value ? (
                            <i className="i i-tick select-tick" />
                          ) : null}
                        </div>
                      </div>
                    </div>
                  </li>
                );
              })}
            </ul>
          </div>
        )}
      </div>
    </ClickOutside>
  );
};

export default ProviderSelector;
