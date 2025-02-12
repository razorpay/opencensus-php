import { classList } from 'common/utils/rzp-utils';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import FormWizard from '../components/FormWizard';

export default function BaseForm(props) {
  const content = (
    <FormWizard
      isLoading={props.isLoading}
      isFormLocked={props.isFormLocked}
      isModalView={props.isModalView}
      onClose={props.onClose}
      disableSubmit={props.disableSubmit}
      history={props.history}
    />
  );

  if (props.isModalView) {
    return (
      <Modal className={classList('PaymentLink--CreateV2', 'animate-down')} showCloseBtn={false}>
        <ModalContent>{content}</ModalContent>
      </Modal>
    );
  }

  return <div className="StandAloneContainer">{content}</div>;
}
