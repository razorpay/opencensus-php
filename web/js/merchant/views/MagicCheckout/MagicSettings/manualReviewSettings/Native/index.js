import NativeCredentialsForm from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Native/component/NativeCredentialForm';
import CommonModal from 'merchant/views/MagicCheckout/common/components/CommonModal';
import InfoModal from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Native/component/InfoModal';
import { MANUAL_REVIEW_MODAL } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const NativeModal = ({ platform, submitCredentials }) => {
  const { infoHeader, instructions } = MANUAL_REVIEW_MODAL[platform] || {};

  return (
    <CommonModal
      inputClass="native-input-form"
      infoClass=" native-info-container"
      infoComponent={<InfoModal heading={infoHeader} instructions={instructions} />}
      inputComponent={
        <NativeCredentialsForm platform={platform} submitCredentials={submitCredentials} />
      }
    />
  );
};

export default NativeModal;
