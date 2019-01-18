import { openModal, closeModal, notifySuccess } from 'common/modal';
import Form from 'ui/Form';
import Field, { TextAreaField } from 'ui/Field';
import { ModalContent } from 'component/Modal';
import JSONEdit from 'admin/razorx/JSONEdit';
import EnumList from 'component/Input/EnumList';

const initJSONObj = {
  name: '',
  description: '',
  notify: ['// Eg: Array of Slack identifiers without @'],
  variants: ['// Eg: Array of strings'],
};

const validatorJSON = {
  name: function(val) {
    if (!val || typeof val !== 'string') {
      return 'name must be non-empty String';
    }
  },
  description: function(val) {
    if (!val || typeof val !== 'string') {
      return 'description must be non-empty String';
    }
  },
  notify: function(val) {
    if (val) {
      let errorMsg;
      if (!(val instanceof Array)) {
        errorMsg = 'notify must be an Array';
      }
      val.forEach(v => {
        if (v.indexOf('@') > -1) {
          errorMsg = '@ is not required in notify Array';
          return false;
        }
      });

      return errorMsg;
    }
  },
  variants: function(val) {
    if (!val || !(val instanceof Array || !val.length)) {
      return 'variants must be a non-empty Array';
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

  JSONObj = (() => {
    let prepareObj = {};

    if (this.props.data) {
      Object.keys(initJSONObj).forEach(k => {
        prepareObj[k] = this.props.data[k];
      });
    } else {
      prepareObj = { ...initJSONObj };
    }

    return prepareObj;
  })();

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
    const { data, JSONView } = this.props;
    let header = data ? 'Edit Feature' : 'Create Feature';

    if (JSONView) {
      header += ' (JSON)';
    }
    if (data) {
      header += ` – ${data.id}`;
    }

    return (
      <ModalContent class="modal-features modal-json-edit" header={header}>
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
              <Field
                label="Slack Notify"
                placeholder="Comma separated list without @"
                type="text"
                name="notify"
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
