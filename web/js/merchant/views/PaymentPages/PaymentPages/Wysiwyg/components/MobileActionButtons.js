import React, { useState } from 'react';

import Button from 'common/new-ui/Button';

import { isEmail, isPhone } from 'common/utils/validators';

// component to handle creation steps 1 and 2 in mobile view
export default function MobileActionButtons(props) {
  const [isDetailsView, setIsDetailsView] = useState(true);
  // disable button unless all required fields filled in and valid
  const isContinueButtonDisabled = !(
    isEmail(props.supportEmail) &&
    isPhone(props.supportContact) &&
    props.title
  );

  return (
    <div class="mobile-cta-container">
      {isDetailsView ? (
        <Button.Primary
          onClick={() => {
            setIsDetailsView(false);

            // code to slideup form view
            const formView = document.getElementById('main-view');
            formView.classList.add('slideup');
          }}
          disabled={isContinueButtonDisabled}
        >
          Continue <i class="i i-chevron-right" />
        </Button.Primary>
      ) : (
        <>
          <Button.Transparent
            onClick={() => {
              setIsDetailsView(true);

              // code to slidedown form view
              const formView = document.getElementById('main-view');
              formView.classList.remove('slideup');
            }}
          >
            <i class="i i-chevron-left" /> Previous
          </Button.Transparent>
          <div class="m-r" />
          <Button.Primary onClick={props.handlePublishPage}>Publish Page</Button.Primary>
        </>
      )}
    </div>
  );
}
