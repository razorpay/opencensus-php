import IpAddress from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy/ipAddress';
import Pincodes from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy/pincodes';

const RtoByGroup = () => {
  return (
    <div className="widget-row">
      <Pincodes />
      <IpAddress />
    </div>
  );
};

export default RtoByGroup;
