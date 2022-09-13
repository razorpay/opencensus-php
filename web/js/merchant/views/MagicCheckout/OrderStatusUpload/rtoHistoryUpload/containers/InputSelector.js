import { useCallback, useState } from 'react';
import Input from 'common/new-ui/Input';
import { SHIPPING_PROVIDERS } from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/constants';

const InputSelector = ({ setInput, provider }) => {
  const [requiredMsg, setRequiredMsg] = useState('');

  const setField = useCallback(
    (e) => {
      setInput(e.target.value);
      setRequiredMsg('');
    },
    [setInput, setRequiredMsg],
  );

  const onCheck = () => {
    setRequiredMsg('Required');
  };

  return (
    <div className="provider-component">
      <div className="form-group input-selector" onBlur={!provider ? onCheck : null}>
        <label className="control-label control-type">Shipping Provider</label>
        <div className={`modal-select${requiredMsg ? ' error' : ''}`}>
          <Input.Select
            name="shippingProvider"
            value={provider ?? ''}
            options={SHIPPING_PROVIDERS}
            onChange={(e) => setField(e)}
            autoFocus
          />
        </div>
      </div>
      <p className={`field-msg${requiredMsg ? '' : ' hide-msg'}`}>{requiredMsg}</p>
    </div>
  );
};

export default InputSelector;
