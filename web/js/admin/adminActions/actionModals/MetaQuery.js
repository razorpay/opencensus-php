import React, { Component } from 'react';

import Form from 'ui/Form';
import Table from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';
import { TextAreaField, SelectMode } from 'ui/Field';

import { notifySuccess, closeModal, openModal } from 'common/modal';
import { snakeToTitleCase } from 'common/util';
import { adminPost } from 'common/fetch';

export default class MetaQuery extends Component {
  static title = 'DB Meta Query';
  static permission = 'db_meta_query';

  state = {
    queryDump: null,
  };

  handleSubmit = body => {
    let { query, mode } = body;

    //trim escape space/newline characters
    query = query.trim();

    //reinitialize `query` ui upon request
    this.setState({ queryDump: null });

    return adminPost({
      url: `${mode}/db_meta_query`,
      data: { query },
    }).then(response => {
      if (response) {
        this.setState({
          queryDump: response,
        });
        notifySuccess('Query executed successfully.');
      }
    });
  };

  //generate fields based on api response for table view
  getFields = () => {
    const queryObj = this.state.queryDump[0];
    let fields = [];

    for (let prop in queryObj) {
      if (queryObj.hasOwnProperty(prop)) {
        // add `pre` tag for Create Table query
        if (prop === 'Create Table') {
          fields.push([
            snakeToTitleCase(prop),
            item => <pre class="code">{item[prop]}</pre>,
          ]);
        } else {
          fields.push([snakeToTitleCase(prop), item => item[prop]]);
        }
      }
    }

    return fields;
  };

  render() {
    const { queryDump } = this.state;

    return (
      <div class="meta-query" style={{ width: '1000px' }}>
        <Form class="full-elements full-span">
          <TextAreaField
            label="Query"
            name="query"
            helpMsg={
              <span>
                Enter a valid db meta query, currently only the following
                queries with prefixes are allowed
                <ul style={listStyle}>
                  <li>explain</li>
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
            onSubmit={this.handleSubmit}
          />
        </Form>
        <div class="query-dump">
          {queryDump && (
            //Generate table view for other db requests
            <Table
              animateRow={false}
              items={queryDump}
              fields={this.getFields()}
              indexFn={(_, idx) => idx}
            />
          )}
        </div>
      </div>
    );
  }
}

const listStyle = {
  marginLeft: '25px',
  listStyleType: 'decimal',
};
