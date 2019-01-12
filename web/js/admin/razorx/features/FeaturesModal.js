import { openModal, closeModal, notifySuccess } from 'common/modal';
import Form from 'ui/Form';
import Field, { CheckField } from 'ui/Field';
import { ModalContent } from 'component/Modal';
import JSONEdit from 'admin/razorx/JSONEdit';

const initJSONObj = {
  name: '',
  description: '',
  variants: [
    {
      name: '',
      description: '',
    },
    {
      name: '',
      description: '',
    },
  ],
};

export default class extends React.Component {
  onSubmit = data => {};

  isValid() {
    if (this.props.JSONView) {
      // Check if JSON is valid and all required params are there
    } else {
      // Check if all required params are there
    }

    return true;
  }

  render() {
    const { id, name, JSONView } = this.props;

    return (
      <ModalContent
        class="modal-features modal-json-edit"
        header={id ? `Edit Feature – ${name}` : 'Create Feature'}
      >
        <Form onSubmit={this.onSubmit}>
          {JSONView && <JSONEdit initialJSON={initJSONObj} />}
          <div class="footer">
            <button class="btn btn--primary" disabled={!this.isValid()}>
              Create
              <span class="spin-btn" />
            </button>
          </div>
        </Form>
      </ModalContent>
    );
  }
}
