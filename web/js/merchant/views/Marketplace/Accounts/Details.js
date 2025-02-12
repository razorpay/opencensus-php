import ActivationForm from 'merchant/containers/Activation';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

export default function({ accountId, onSubmitSuccessCB, onClose }) {
  return (
    <ModalMask
      maskClosable={true}
      onClose={() => onClose(accountId)}
      className={'Account-Activation'}
    >
      <ActivationForm
        onClose={() => onClose(accountId)}
        accountId={accountId}
        callback={onSubmitSuccessCB}
        defaultMsg={
          <HelpText msg="Complete the details to Activate this account." />
        }
      />
    </ModalMask>
  );
}

const HelpText = ({ msg, ...restProps }) => (
  <div className="help-text" {...restProps}>
    <i className="i i-info-outline" />
    <div>{msg}</div>
  </div>
);
