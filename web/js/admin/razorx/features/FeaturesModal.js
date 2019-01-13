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

const validatorJSON = {
  name: function(val) {
    if (!val || typeof val !== 'string') {
      return 'name must be non-empty string';
    }
  },
  description: function(val) {
    if (!val || typeof val !== 'string') {
      return 'description must be non-empty string';
    }
  },
  variants: function(val) {
    if (!val || !(val instanceof Array || !val.length)) {
      return 'variants must be a non-empty array';
    }

    let errorMsg;

    val.forEach(v => {
      const isNameInvalid = !v.name || typeof v.name !== 'string';
      if (isNameInvalid) {
        errorMsg = 'variant name must be a non-empty string';
        return false;
      }

      const isDescInvalid = !v.description || typeof v.description !== 'string';
      if (isDescInvalid) {
        errorMsg = 'variant description must be a non-empty string';
        return false;
      }
    });

    return errorMsg;
  },
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
          {JSONView && (
            <JSONEdit initialJSON={initJSONObj} validatorJSON={validatorJSON} />
          )}
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
