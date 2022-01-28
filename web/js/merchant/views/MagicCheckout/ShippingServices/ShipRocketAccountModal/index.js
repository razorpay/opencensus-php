import { useState } from 'react';
import DemoVideo from 'merchant/views/MagicCheckout/ShippingServices/ShipRocketAccountModal/components/DemoVideo';
import LinkAccount from 'merchant/views/MagicCheckout/ShippingServices/ShipRocketAccountModal/containers/LinkAccount';
import ShipRocketIcon from 'merchant/views/MagicCheckout/ShippingServices/assets/shiprocket.svg';

const ShipRocketModal = () => {
  const [step, setStep] = useState(0);
  return (
    <div className="row display-flex">
      <div className="magic-checkout-demo-container col-sm-6">
        <DemoVideo step={step} />
        <img src={ShipRocketIcon} alt="shiprocket-icon" className="shiprocket-modal-divider-icon" />
      </div>
      <div className="col-sm-6 bg-white">
        <LinkAccount step={step} setStep={setStep} />
      </div>
    </div>
  );
};

export default ShipRocketModal;
