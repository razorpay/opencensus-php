import React from 'react';
import styled from 'styled-components';

import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import Input, { Label, Description } from 'common/new-ui/Input';
import { DocLink } from 'merchant/components/DocsLink';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

const _Modal = ({ className = '', ...restProps }) => (
  <Modal unmodifiedClassName={className} {...restProps} />
);

const StyledModal = styled(_Modal)`
  width: 400px;
  margin: 48px 0;
  overflow: visible;
  margin-top: -20px;
  @media (max-width: 767px) {
    width: 100%;
    height: 100%;
    margin: 0;
  }

  .Input-inlineLabel {
    vertical-align: top;
  }

  .Input--radioLabels {
    label {
      display: flex;
      margin-left: -15px;

      input {
        width: auto;
      }

      .Input-radio {
        min-width: 18px;
      }
    }
  }

  .Input--radio {
    label {
      margin-bottom: 12px;
    }
  }

  .doc-links {
    font-size: 13px;

    a:first-of-type {
      margin-right: 12px;
    }
  }
`;

const Title = styled.div`
  font-weight: 600;
  padding: 16px 24px 4px;
  font-size: 18px;
`;

const StyledForm = styled(Form)`
  @media (max-width: 767px) {
    height: 100vh;
  }

  main {
    @media (max-width: 767px) {
      height: inherit;
      overflow-y: auto;
      padding-bottom: 100px;
    }
  }
`;

const Section = styled.div`
  padding: 24px;

  .Input {
    margin: 0;
  }

  &:not(:last-of-type) {
    border-bottom: 1px solid #ececec;
  }

  .m-r {
    margin-right: 4px;
  }

  .i-info-outline {
    color: #536582;
    margin-left: 3px;
  }
`;

const Footer = styled.footer`
  padding: 16px 24px;
  background: #f6f6f6;
  border-top: 1px solid #e0e0e0;
  text-align: right;
  @media (max-width: 767px) {
    position: fixed;
    bottom: 0;
    width: 100%;
    text-align: center;
  }

  .Button--transparent {
    padding: 8px 20px;
  }

  button:last-of-type {
    margin-right: 0;
  }
`;

interface IReceiptSettingsProps {
  storefrontEntity: any;
  onSave: (formData) => void;
  onClose: () => void;
}

const StorefrontReceiptSettings = ({
  onClose,
  storefrontEntity,
  onSave,
}: IReceiptSettingsProps): React.ReactElement => {
  const handleSubmit = (formData) => {
    onSave(formData);

    onClose();
  };

  return (
    <ModalMask maskClosable={false}>
      <StyledModal onClose={onClose} showCloseBtn={false}>
        <ModalContent>
          <Title>
            <i className="i i-receipt mr-8" />
            Payment Receipts Settings
          </Title>

          <StyledForm onSubmit={handleSubmit}>
            <main>
              <Section>
                <Input.Radio
                  name="enable_custom_serial_number"
                  defaultValue={storefrontEntity.settings.enable_custom_serial_number || '0'}
                  options={[
                    {
                      label: (
                        <div>
                          <Label text="Send Receipts Automatically" />
                          <Description text="Receipts are emailed to customers immediately after payment." />
                        </div>
                      ),
                    },
                    {
                      label: (
                        <div>
                          <Label text="Don’t Send Receipts Automatically" />
                          <Description text="You may send receipts later from dashboard. Your own reference ID may be added too." />
                        </div>
                      ),
                    },
                  ]}
                  className="Input--vTop Input--theme"
                />

                <div className="doc-links">
                  <DocLink
                    href="https://razorpay.com/docs/payment-pages/receipt/#pdf-receipt-to-customers"
                    target="_blank"
                  >
                    Sample Receipt <i className="i i-external-link" />
                  </DocLink>
                  <DocLink href="https://razorpay.com/docs/payment-pages/receipt/" target="_blank">
                    Learn More <i className="i i-external-link" />
                  </DocLink>
                </div>
              </Section>
            </main>
            <Footer>
              <Button.Transparent type="button" onClick={onClose}>
                Cancel
              </Button.Transparent>
              <Button.Primary type="submit">Save</Button.Primary>
            </Footer>
          </StyledForm>
        </ModalContent>
      </StyledModal>
    </ModalMask>
  );
};

export default StorefrontReceiptSettings;
