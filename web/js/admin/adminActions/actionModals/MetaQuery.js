import React, { Component } from 'react';

import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { TextAreaField, SelectMode } from 'ui/Field';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'common/fetch';

export default class MetaQuery extends Component {
  static title = 'Meta Query';
  static permission = 'db_meta_query';

  state = {
    queryDump: null,
  };

  render() {
    return (
      <Form class="full-elements full-span">
        <TextAreaField
          label="Query"
          name="query"
          helpMsg={
            <span>
              Enter a valid db meta query, currently only the following queries
              with prefixes are allowed
              <ul style={listStyle}>
                <li>Explain select</li>
                <li>Show create table</li>
                <li>Show indexes from</li>
              </ul>
            </span>
          }
        />
        <SelectMode />
        {this.state.queryDump && (
          <div>
            Raw Data:
            <div class="code">
              {JSON.stringify(this.state.queryDump, null, 4)}
            </div>
          </div>
        )}
        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={body => {
            let mode = body.mode;

            delete body.mode;

            return adminPost({
              url: `${mode}/db_meta_query`,
              data: body,
            }).then(response => {
              if (response) {
                this.setState({
                  queryDump: response,
                });
                notifySuccess('Query executed successfully.');
              }
            });
          }}
        />
      </Form>
    );
  }
}

const listStyle = {
  marginLeft: '25px',
  listStyleType: 'decimal',
};
