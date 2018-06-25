import ActivationForm from 'merchant/containers/Activation/new';
import { ModalMask, Modal, ModalContent } from 'component/Modal';

export default function({ accountId, onSubmitSuccessCB, onClose }) {
  return (
    <ModalMask
      maskClosable={true}
      onClose={() => onClose(accountId)}
      class={'Account-Activation'}
    >
      <ActivationForm
        onClose={() => onClose(accountId)}
        accountId={accountId}
        callback={onSubmitSuccessCB}
      />
    </ModalMask>
  );
}
