import { Component } from 'react';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import Field, {
  SelectField,
  SelectMode,
  CheckField,
  FromField,
  ToField,
} from 'ui/Field';

import { adminPost } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

export default class ESIndexing extends Component {
  static title = 'ES Indexing';
  static permissions = 'es_write_operation';

  state = {
    action: 'create',
  };

  onActionChange = ({ target }) => {
    this.setState({
      action: target.value,
    });
  };

  onSubmit = ({ action, ...body }) => {
    const url = action === 'create' ? '/es/index_create' : '/es/index';
    if (body.hasOwnProperty('--skip')) {
      body['--skip'] = Number(body['--skip']);
    }

    if (body.hasOwnProperty('--take')) {
      body['--take'] = Number(body['--take']);
    }

    if (body['--start_at']) {
      body['--start_at'] = moment(body['--start_at'], 'DD/MM/YYYY').format('X');
    }

    if (body['--end_at']) {
      body['--end_at'] = moment(body['--end_at'], 'DD/MM/YYYY').format('X');
    }

    return adminPost({
      url: body.mode + url,
      data: body,
    }).then(response => {
      if (response) {
        notifySuccess('Your request was successfull');
        closeModal();
      }
    });
  };

  render() {
    return (
      <Form class="full-span">
        <SelectField
          label="Action"
          name="action"
          onChange={this.onActionChange}
          defaultValue={this.state.action}
        >
          <option value="create">Create/Recreate an ES Index</option>
          <option value="perform">Perform indexing on ES index</option>
        </SelectField>

        <SelectMode />

        <Field name="entity" label="Entity" required />
        {this.state.action === 'create' && (
          <>
            <Field name="index_prefix" label="Index Prefix" required />

            <Field name="type_prefix" label="Type Prefix" required />

            <CheckField name="--reindex" label="Delete Existing Index" />
          </>
        )}
        {this.state.action === 'perform' && (
          <>
            <Field type="number" label="Skip" name="--skip" />

            <Field type="number" label="Take" name="--take" />

            <FromField name="--start_at" label="Start At" />

            <ToField name="--end_at" label="End At" />
          </>
        )}

        <AsyncButton
          type="submit"
          text="Submit"
          class="btn"
          pendingClass="small spinner"
          onSubmit={this.onSubmit}
        />
      </Form>
    );
  }
}
