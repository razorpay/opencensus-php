import React from 'react';

import { withRouter } from 'common/deprecated/withRouter';
import EnumList from 'common/new-ui/Input/EnumList';
import { ModalContent } from 'common/new-ui/Modal';
import JSONEdit from 'razorx/components/JSONEdit';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Field, { TextAreaField } from 'razorx/components/ui/Field';
import Form from 'razorx/components/ui/Form';
import { rexPost, rexPut } from 'razorx/helpers/fetch';

import validatorJSON, { initJSONObj } from './validators';

class FeatureModal extends React.Component {
  state = { variants: this.props.data ? this.props.data.variants : [''] };

  onSubmit = (form) => {
    const isInvalid = this.isInvalid();

    if (!!isInvalid) {
      notifyError(isInvalid);
      return;
    }

    let data = form;
    if (form['json-value']) {
      data = JSON.parse(form['json-value']);
    }

    const isEdit = !!(this.props.data && this.props.data.id);
    const reqPayload = {
      ...data,
      variants: this.state.variants.map((v) => v.trim()),
    };

    if (reqPayload.notify && typeof reqPayload.notify === 'string') {
      reqPayload.notify = reqPayload.notify.split(',').map((n) => n.trim());
    }

    let requestFn = rexPost;
    let url = 'feature_flags';
    let successMsg = 'Feature is successfully created';

    if (isEdit) {
      requestFn = rexPut;
      url += `/${this.props.data.id}`;
      successMsg = `Feature ${this.props.data.id} is successfully updated`;
    }

    requestFn({ url, data: reqPayload }).then((resp) => {
      if (resp) {
        notifySuccess(successMsg);
        closeModal();

        if (!isEdit) {
          this.props.history.push(`/features_flags/${resp.id}`);
        } else {
          this.props.onEdit(resp);
        }
      }
    });
  };

  // eslint-disable-next-line consistent-return
  isInvalid() {
    if (this.props.JSONView) {
      // Check if JSON is valid and all required params are there
      return false;
    } else {
      if (!this.state.variants.length || !this.state.variants[0]) {
        return 'At least 1 variant must be added';
      }

      const trimmedVariants = this.state.variants.filter(
        (v, i) => this.state.variants.indexOf(v) === i,
      );

      if (trimmedVariants.length !== this.state.variants.length) {
        return 'Duplicate variants in the list';
      }
    }
  }

  JSONObj = (() => {
    let prepareObj = {};

    if (this.props.data) {
      Object.keys(initJSONObj).forEach((k) => {
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
                defaultValue={isEdit && data.notify ? data.notify.join(', ') : ''}
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
            <button class="btn btn--primary">
              {isEdit ? 'Update' : 'Create'}
              <span class="spin-btn" />
            </button>
          </div>
        </Form>
      </ModalContent>
    );
  }
}

export default withRouter(FeatureModal);
