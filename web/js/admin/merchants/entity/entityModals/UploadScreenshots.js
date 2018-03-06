import React from 'react';
import BaseModal from 'ui/BaseModal';

import { notifyError } from 'common/modal';
import { adminFormUpload } from 'common/fetch';

import { FileField } from 'ui/Field';

export default ({ merchantId }) => {
  function handleUpload(entityName, entityLabel, e) {
    adminFormUpload(
      {
        [entityName]: e.target.files[0],
      },
      '/admin/merchant/' + merchantId + '/screenshot'
    )
      .then(response => {
        if (response.data.success) {
          notifySuccess(entityLabel + ' Screenshot uploaded successfully.');
          closeModal();
        } else {
          response.data.errors.map(error => notifyError(error));
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <BaseModal header="Upload Screenshots">
      <div style={{ width: '350px' }}>
        {entitiesList.map(entity => (
          <FileField
            key={entity.name}
            accept="image/*"
            label={entity.label}
            name={entity.name}
            onChange={handleUpload.bind(null, entity.name, entity.label)}
          />
        ))}
      </div>
    </BaseModal>
  );
};

/* Resources */
const entitiesList = [{ label: 'Website homepage', name: 'business_website' }];
