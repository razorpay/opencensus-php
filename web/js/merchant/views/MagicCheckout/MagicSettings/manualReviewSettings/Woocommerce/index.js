import CredentialsForm from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce/component/CredentialsForm';
import CommonModal from 'merchant/views/MagicCheckout/common/components/CommonModal';
import InfoContainer from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce/container/InfoContainer';

const WoocommerceModal = ({ platform, submitCredentials, modalDesc, customCloseModal }) => (
  <CommonModal
    infoClass=" woocommerce-demo-container"
    infoComponent={<InfoContainer platform={platform} />}
    inputComponent={
      <CredentialsForm
        platform={platform}
        submitCredentials={submitCredentials}
        modalDesc={modalDesc}
        customCloseModal={customCloseModal}
      />
    }
  />
);

export default WoocommerceModal;
