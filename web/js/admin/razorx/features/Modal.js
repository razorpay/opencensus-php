import {
  openModal,
  closeModal,
  notify,
  notifySuccess,
  notifyError,
} from 'common/modal';
import Form from 'ui/Form';
import Field, { TextAreaField } from 'ui/Field';
import { ModalContent } from 'component/Modal';
import JSONEdit from 'admin/razorx/JSONEdit';
import EnumList from 'component/Input/EnumList';
import { rexPost, rexPatch } from 'admin/razorx/fetch';
import { initJSONObj, validatorJSON } from './validators';

export default class extends React.Component {
  state = { variants: this.props.data ? this.props.variants : [''] };

  onSubmit = form => {
    if (!this.isValid()) {
      return;
    }

    const isEdit = !!(this.props.data && this.props.data.id);
    const reqPayload = {
      ...form,
      variants: this.state.variants.map(v => v.trim()),
    };

    if (reqPayload.notify && typeof reqPayload.notify === 'string') {
      reqPayload.notify = reqPayload.notify.split(',').map(n => n.trim());
    }

    let requestFn = rexPost,
      url = 'featureFlags',
      successMsg = 'Feature is successfully created';

    if (isEdit) {
      requestFn = rexPatch;
      url += `/${this.props.data.id}`;
      successMsg = `Feature ${this.props.data.id} is successfully updated`;
    }

    requestFn({ url, data: reqPayload }).then(data => {
      if (data && data.success) {
        notifySuccess(successMsg);
      }
    });
  };

  isValid() {
    if (this.props.JSONView) {
      // Check if JSON is valid and all required params are there
      return true;
    } else {
      if (!this.state.variants.length || !this.state.variants[0]) {
        return false;
      }
    }
  }

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

  onChangeEnumList = (enumList = []) => {
    let trimmedEnums = enumList.concat();

    trimmedEnums = trimmedEnums.reduce((r, o) => {
      if (o) {
        r.push(o);
      }

      return r;
    }, []);

    this.setState({ variants: trimmedEnums });
  };

  render() {
    const { data, JSONView } = this.props;
    const isEdit = !!(data && data.id);

    let header = isEdit ? `Edit Feature – ${data.id}` : 'Create Feature';

    if (JSONView) {
      header += ' (JSON)';
    }

    return (
      <ModalContent class="modal-features modal-json-edit" header={header}>
        <Form onSubmit={this.onSubmit} class="full-span full-elements">
          {JSONView ? (
            <JSONEdit initialJSON={initJSONObj} validatorJSON={validatorJSON} />
          ) : (
            <React.Fragment>
              <Field
                type="text"
                label="Name"
                name="name"
                placeholder="Feature Name"
                defaultValue={isEdit ? data.name : ''}
                required
              />
              <TextAreaField
                label="Description"
                name="description"
                placeholder="Feature Description"
                defaultValue={isEdit ? data.description : ''}
                required
              />
              <Field
                label="Slack Notify"
                placeholder="Comma separated list without @"
                defaultValue={isEdit ? data.notify.join(', ') : ''}
                type="text"
                name="notify"
              />
              <div class="sub-heading">Variants</div>
              <EnumList
                class="variants-list"
                onChange={this.onChangeEnumList}
                defaultValue={this.state.variants}
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
