import { openModal, closeModal, notifySuccess } from 'common/modal';
import Form from 'ui/Form';
import Field, { TextAreaField } from 'ui/Field';
import { ModalContent } from 'component/Modal';
import JSONEdit from 'admin/razorx/JSONEdit';
import EnumList from 'component/Input/EnumList';

const initJSONObj = {
  name: '',
  description: '',
  variants: ['// Eg: Array of strings'],
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
      const reg = new RegExp(/^[a-z0-9]+$/i);
      if (typeof v !== 'string') {
        errorMsg = 'Each variant must be a String';
        return false;
      } else if (!reg.test(v)) {
        errorMsg = 'variant can only contain Alphanumeric, - and _';
        return false;
      }
    });

    return errorMsg;
  },
};

export default class extends React.Component {
  state = { enum: [''] };
  onSubmit = data => {};

  isValid() {
    if (this.props.JSONView) {
      // Check if JSON is valid and all required params are there
    } else {
      // Check if all required params are there
    }

    return true;
  }

  onChangeEnumList = (enumList = []) => {
    let trimmedEnums = enumList.concat();

    trimmedEnums = trimmedEnums.reduce((r, o) => {
      if (o) {
        r.push(o);
      }

      return r;
    }, []);

    this.setState({ enum: trimmedEnums });
  };

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
              <EnumList
                class="variants-list"
                onChange={this.onChangeEnumList}
                defaultValue={this.state.enum}
                inputClass="square-pills label-semi-muted"
                addNewBtn={() => (
                  <button type="button" class="btn btn--pill">
                    <i class="i i-return-key" /> Add Variant
                  </button>
                )}
              />
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
