import React from 'react';
import BaseModal from 'ui/BaseModal';

import { notifyError } from 'common/modal';
import { adminFormUpload } from 'util/fetch';

import { FileField } from 'ui/Field';

export default ({ props }) => {
  function handleUpload(entityName, entityLabel, e) {
    adminFormUpload(
      {
        [entityName]: e.target.files[0],
      },
      '/admin/merchant/' + props.merchant.details.id + '/screenshot'
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
      {entitiesList.map(entity => (
        <FileField
          key={entity.name}
          accept="image/*"
          label={entity.label}
          name={entity.name}
          onChange={handleUpload.bind(null, entity.name, entity.label)}
        />
      ))}
    </BaseModal>
  );
};

/* Resources */
const entitiesList = [
  { label: 'Website homepage', name: 'business_website' },
  { label: 'About Page', name: 'website_about' },
  { label: 'Contact', name: 'website_contact' },
  { label: 'Privacy Policy', name: 'website_privacy' },
  { label: 'Terms & Conditions', name: 'website_terms' },
  { label: 'Refund Policy', name: 'website_refund' },
  { label: 'Pricing Policy', name: 'website_pricing' },
  { label: 'Customer Login', name: 'website_login' },
];
