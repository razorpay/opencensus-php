import { openModal, closeModal, notifySuccess } from 'common/modal';
import Form from 'ui/Form';
import Field, { TextAreaField } from 'ui/Field';
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
        errorMsg = 'Name must be a non-empty string';
        return false;
      }

      const isDescInvalid = !v.description || typeof v.description !== 'string';
      if (isDescInvalid) {
        errorMsg = 'Description must be a non-empty string';
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
        <Form onSubmit={this.onSubmit} class="full-span full-elements">
          {JSONView ? (
            <JSONEdit initialJSON={initJSONObj} validatorJSON={validatorJSON} />
          ) : (
            <React.Fragment>
              <Field
                label="Name"
                placeholder="Feature Name"
                type="text"
                name="name"
                required
              />
              <Field
                label="Description"
                type="text"
                name="description"
                placeholder="Feature Description"
                required
              />
              <div class="sub-heading">Variants</div>
              <Field.Group label="Variant 1" class="collapse-space" required>
                <Field
                  type="text"
                  name="variant[0][name]"
                  placeholder="Name"
                  required
                />
                <TextAreaField
                  name="variant[0][description]"
                  placeholder="Description"
                  defaultValue=""
                  required
                />
              </Field.Group>
              <Field.Group label="Variant 1" class="collapse-space" required>
                <Field
                  type="text"
                  name="variant[0][name]"
                  placeholder="Name"
                  required
                />
                <TextAreaField
                  name="variant[0][description]"
                  placeholder="Description"
                  defaultValue=""
                  required
                />
              </Field.Group>
              <button type="button" class="btn btn--pill">
                <i class="i i-plus" /> Variant
              </button>
              <div style={{ marginTop: 24 }} />
            </React.Fragment>
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
