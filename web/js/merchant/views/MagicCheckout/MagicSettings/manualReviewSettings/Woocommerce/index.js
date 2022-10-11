import CredentialsForm from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce/component/CredentialsForm';
import CommonModal from 'merchant/views/MagicCheckout/common/components/CommonModal';
import InfoContainer from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce/container/InfoContainer';

const WoocommerceModal = ({ platform, setCodOrderControl }) => (
  <CommonModal
    infoClass=" woocommerce-demo-container"
    infoComponent={<InfoContainer platform={platform} />}
    inputComponent={<CredentialsForm platform={platform} setCodOrderControl={setCodOrderControl} />}
  />
);

export default WoocommerceModal;
