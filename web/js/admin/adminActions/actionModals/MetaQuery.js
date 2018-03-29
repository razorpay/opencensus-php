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
                <li>explain select</li>
                <li>show create table</li>
                <li>show indexes from</li>
              </ul>
            </span>
          }
        />
        <SelectMode />
        <AsyncButton
          text="Execute"
          class="btn"
          pendingClass="small spinner"
          onSubmit={body => {
            let mode = body.mode;

            delete body.mode;

            this.setState({
              queryDump: null,
            });

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
        <br />
        <br />
        {this.state.queryDump && (
          <div>
            Raw Data:
            <div class="code">
              {JSON.stringify(this.state.queryDump, null, 4)}
            </div>
          </div>
        )}
      </Form>
    );
  }
}

const listStyle = {
  marginLeft: '25px',
  listStyleType: 'decimal',
};
