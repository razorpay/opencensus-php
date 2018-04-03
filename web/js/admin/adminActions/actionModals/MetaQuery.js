import React, { Fragment } from 'react';

import Form from 'ui/Form';
import BaseModal from 'ui/BaseModal';
import AsyncButton from 'ui/AsyncButton';
import { TextAreaField, SelectMode } from 'ui/Field';
import { notifySuccess, closeModal, openModal } from 'common/modal';

import { adminPost } from 'common/fetch';

MetaQuery.title = 'DB Meta Query';
MetaQuery.permission = 'db_meta_query';

export default function MetaQuery() {
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

          return adminPost({
            url: `${mode}/db_meta_query`,
            data: body,
          }).then(response => {
            if (response) {
              openQueryModal(response);
              notifySuccess('Query executed successfully.');
            }
          });
        }}
      />
    </Form>
  );
}

const openQueryModal = queryDump => {
  const isCreateTableQuery =
    !!(queryDump && queryDump[0]['Create Table']) || false;

  openModal(
    <BaseModal header="Response">
      <pre class="code" style={{ width: '650px' }}>
        {isCreateTableQuery ? (
          <Fragment>
            {queryDump.map(query => (
              <div>
                Table: {query['Table']}
                <br />
                {query['Create Table']}
              </div>
            ))}
          </Fragment>
        ) : (
          JSON.stringify(queryDump, null, 4)
        )}
      </pre>
    </BaseModal>
  );
};

const listStyle = {
  marginLeft: '25px',
  listStyleType: 'decimal',
};
