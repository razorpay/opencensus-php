import { useState, useCallback } from 'react';
import { SHIPPING_PROVIDERS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';

const NativeShippingTab = ({ onNext }) => {
  const [shippingProvider, setShippingProvider] = useState(SHIPPING_PROVIDERS[0].name);
  const [view, setView] = useState(SHIPPING_PROVIDERS[0].view);
  const [isValid, setIsValid] = useState(false);

  const handleChange = useCallback(
    (e) => {
      const { value } = e.target;
      if (value === 'select') {
        setIsValid(false);
        return;
      }
      setIsValid(true);
      const selectedObj = SHIPPING_PROVIDERS.filter((item) => item.name === value) || [];
      if (selectedObj.length) {
        setView(selectedObj[0].view);
        setShippingProvider(value);
      }
    },
    [setView, setShippingProvider, setIsValid],
  );
  return (
    <div className="native-shipping-tab-selector bg-settings">
      <div className="display-flex align-center padding-16">
        <label className="font-normal">Shipping Service</label>
        <Input.Select
          name="platform"
          className="provider-dropdown"
          value={shippingProvider}
          options={SHIPPING_PROVIDERS}
          onChange={handleChange}
        />
      </div>
      <AsyncBtn.Primary
        disabled={!isValid}
        type="button"
        onClick={() => onNext(view)}
        className="settings-cta"
      >
        Next
      </AsyncBtn.Primary>
    </div>
  );
};

export default NativeShippingTab;
