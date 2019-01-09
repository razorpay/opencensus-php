import { openModal, closeModal, notifySuccess } from 'common/modal';
import Form from 'ui/Form';
import Field, { CheckField } from 'ui/Field';
import { ModalContent } from 'component/Modal';

export default class extends React.Component {
  onSubmit = data => {};

  render() {
    const { id, name } = this.props;

    return (
      <ModalContent header={id ? `Edit Feature – ${name}` : 'Create Feature'}>
        <Form onSubmit={this.onSubmit} />
      </ModalContent>
    );
  }
}
