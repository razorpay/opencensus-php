import { useEffect, useState } from 'react';
import { ActionListItemAsset } from '@razorpay/blade/components';
import qs from 'query-string';

import Dropdown from 'common/components/Dropdown';
import { useSplitzService } from 'common/splitz';
import ClickOutside from 'merchant/views/Navigator/components/ClickOutside';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';
import { isPaymentV2ParityFeatureEnabled } from 'merchant/views/Transactions/v2/common/utils';
import { useStore } from 'shell/commonStore';

const ProviderSelector = ({ name, providers, provider, setProvider, isLoading, onChange }) => {
  const user = useStore((state) => state.session.user);
  const splitz = useSplitzService();
  const isProviderSelectorForV2 = isPaymentV2ParityFeatureEnabled(splitz, user);
  const optionDisplayKey = isProviderSelectorForV2 ? 'title' : 'name';

  const [showList, setShowList] = useState(false);
  const [defaultOps, setDefaultOps] = useState();

  const options = [{ [optionDisplayKey]: 'All', value: '', gateway: '' }];
  const providerOps = providers?.filter((p) => p.Gateway != 'smart_router');
  providerOps?.forEach((p) => {
    options.push({
      [optionDisplayKey]: p.Provider_name || p.Gateway,
      value:
        p.Gateway === 'razorpay'
          ? isProviderSelectorForV2
            ? 'Razorpay'
            : 'razorpay'
          : p.Terminal_id,
      gateway: p.Gateway,
      ...(isProviderSelectorForV2 && !!gatewayLogos[p.Gateway]
        ? {
            leading: <ActionListItemAsset src={gatewayLogos[p.Gateway]} alt={p.Gateway} />,
          }
        : {}),
    });
  });

  useEffect(() => {
    const { terminal_id, settled_by } = qs.parse(location.search);
    const defaultOpsIdx =
      terminal_id || settled_by === 'Razorpay'
        ? options.findIndex((o) => o.value === (terminal_id || settled_by))
        : 0;
    setDefaultOps(defaultOpsIdx !== -1 ? [options[defaultOpsIdx]] : [options[0]]);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return isProviderSelectorForV2 ? (
    defaultOps ? (
      <Dropdown
        options={options}
        defaultOptions={defaultOps}
        prefixTitle="Processed by: "
        bottomSheetTitle="Processed by"
        onChange={onChange}
        isDisabled={isLoading}
      />
    ) : null
  ) : (
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
