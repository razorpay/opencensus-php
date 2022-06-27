import InfoComponent from 'merchant/views/MagicCheckout/ShippingServices/DelhiveryAccountModal/components/InfoComponent';
import DelhiveryForm from 'merchant/views/MagicCheckout/ShippingServices/DelhiveryAccountModal/container/DelhiveryForm';
import DelhiveryIcon from 'merchant/views/MagicCheckout/ShippingServices/assets/delhivery.svg';

const DelhiveryModal = () => {
  return (
    <div className="delhiveryModal-container row display-flex">
      <div className="delhivery-info-container col-sm-6">
        <InfoComponent />
        <img src={DelhiveryIcon} alt="delhivery-icon" className="delhivery-modal-divider-icon" />
      </div>
      <div className="delhivery-form-container col-sm-6 bg-white">
        <DelhiveryForm />
      </div>
    </div>
  );
};

export default DelhiveryModal;
