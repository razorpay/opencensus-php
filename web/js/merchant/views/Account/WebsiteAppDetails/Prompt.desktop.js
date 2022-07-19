import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import { pages } from 'merchant/views/Account/WebsiteAppDetails/data';

function PromptDesktop() {
  return (
    <div className="website-app-details-container">
      <div className="prompt-container">
        <ModalHeader title="Update details about your website/app" />
        <div className="prompt-description">
          According to RBI guidelines, we require the following pages on your website/app:
        </div>
        <ul className="page-listing">
          {pages.map((page, idx) => {
            return <li key={`${page}_${idx}`}>{page}</li>;
          })}
        </ul>
        <div className="actions">
          <button className="btn btn-primary">Update</button>
        </div>
      </div>
    </div>
  );
}

export default PromptDesktop;
