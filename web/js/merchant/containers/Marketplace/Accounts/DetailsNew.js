import ActivationForm from 'merchant/containers/Activation';
import { ModalMask, Modal, ModalContent } from 'component/Modal';

export default function({ accountId, onSubmitSuccessCB, onClose }) {
  return (
    <div class="content-wrapper content-sm txn-details">Account Details</div>
  );
}

const HelpText = ({ msg, ...restProps }) => (
  <div class="help-text" {...restProps}>
    <i class="i i-info-outline" />
    <div>{msg}</div>
  </div>
);
