import { useEffect, useState } from 'react';

import { ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import { DocLink } from 'merchant/components/DocsLink';

const successBody = (
  <div>You can now customize the URL of your payment pages to use your domain.</div>
);

const failureBody = (
  <div>
    <div>
      <b>Please wait for a few minutes and connect your domain again</b>. Do not undo the changes
      that you've made on your domain provider yet.
    </div>
    <br />
    <div>
      <DocLink href="https://razorpay.com/docs/payments/payment-pages/faqs#i-am-getting-a-message-that-my-domain">
        {' '}
        <b>
          Why did this happen? <i class="i i-external-link" />
        </b>
      </DocLink>
    </div>
  </div>
);

const statusMap = {
  success: {
    iconClass: 'i-check-circle-outline',
    title: 'Your domain is connected',
    body: successBody,
    buttonText: 'Okay, customize URL',
  },
  failure: {
    iconClass: 'i-warning-o',
    title: 'Your domain was not connected',
    body: failureBody,
    buttonText: "Okay, I'll try after an hour",
  },
};

const PropagationStatusModal = ({ status, closeModal }) => {
  const [staticData, setStaticData] = useState(statusMap.failure);

  useEffect(() => {
    setStaticData(statusMap[status || 'failure']);
  }, [status]);

  const handleClose = () => {
    closeModal();
  };

  const handleSubmit = () => {
    closeModal();

    // focusing on slug input if custom domain connection successful
    if (status === 'success') {
      const slugInputTag = document.querySelector('.custom-url input[name="slug"]');

      slugInputTag && slugInputTag.focus();
    }
  };

  return (
    <ModalContent>
      <div class="main-title">
        <div class="heading">
          <i className={`i ${staticData.iconClass}`} />
          {staticData.title}
        </div>
        <i className="i i-close" onClick={handleClose} />
      </div>
      <Form onSubmit={handleSubmit}>
        <main>
          {staticData.body}
          <br />
          <br />
        </main>
        <footer>
          <Button.Primary class="Save-btn" type="submit">
            {staticData.buttonText}
          </Button.Primary>
        </footer>
      </Form>
    </ModalContent>
  );
};

export default PropagationStatusModal;
