import React from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Text,
  Link,
} from '@razorpay/blade/components';

export const TnCModal = ({
  isOpen,
  setIsOpen,
  handleActivateNow,
  isLoading,
}: {
  isOpen: boolean;
  setIsOpen: (isOpen: boolean) => void;
  handleActivateNow: () => void;
  isLoading: boolean;
}) => {
  return (
    <Modal size="medium" isOpen={isOpen} onDismiss={() => setIsOpen(false)}>
      <ModalHeader
        title="Accept Terms & Conditions"
        subtitle="Review and accept the terms before setting up Buyer Protection widget for your store"
      />
      <ModalBody>
        <Box maxHeight="270px" overflow="auto">
          <Text size="small" color="surface.text.gray.subtle">
            By enabling Buyer Protection, you agree to the following terms: <br />
            <br />
            <br />
            These Additional Terms ("Terms") apply specifically to your use of Money Back Promise
            and supplement the Principal Agreement, which refers to either the Merchant Terms &
            Conditions available at{' '}
            <Link size="small" href="https://razorpay.com/terms/" target="_blank">
              https://razorpay.com/terms/
            </Link>{' '}
            or, if You have entered into a written Merchant Services Agreement with Razorpay
            Software Private Limited ("Razorpay"), the most recent such agreement. By opting for
            Money Back Promise, you acknowledge and agree that these Additional Terms will govern
            your use of this service. In the event of any conflict between these Additional Terms
            and the Principal Agreement, these Additional Terms shall prevail solely with respect to
            Money Back Promise. All other provisions of the Principal Agreement remain in full force
            and effect. <br />
            <br />
            <br />
            1. Razorpay will offer extended warranty coverage to your Customers for eligible
            products purchased through the Your platform/website, subject to the terms and
            conditions outlined by Razorpay. The warranties offered will be subject to Razorpay's
            terms and conditions, including but not limited to eligibility, coverage limits, and
            exclusions. <br />
            <br />
            2. You will ensure that all information provided to Razorpay and/or the Third Party
            Insurers, including Customer and product details, is accurate and up to date. <br />
            <br />
            3. You agree that the fees for Money Back Promise are non-cancellable and non-refundable
            and You are responsible for ensuring timely payment. <br />
            <br />
            4. No requests of any refunds shall be entertained by Razorpay whatsoever. Razorpay
            reserves the right to change, terminate or otherwise amend any fees and billing cycles,
            at its sole discretion, at any time. <br />
            <br />
            5. Your continued use of the Money Back Promise shall be deemed to be your irrevocable
            acceptance of such amendments. Razorpay reserves the right to change, supplement, alter
            any features on the Money Back Promise or any other functionality of the same including
            those that are subject to fees, at its sole discretion. <br />
            <br />
            6. You shall cooperate fully and in a timely manner with Razorpay for resolution of any
            claims raised by Customers, including but not limited to providing order details,
            shipping proof, communications, and any other relevant documents. <br />
            <br />
            7. You shall indemnify, defend, and hold harmless Razorpay, its affiliates, and its
            officers, directors, and employees from any claims, liabilities, losses, damages, or
            expenses (including reasonable legal fees) arising out of or in connection with: <br />
            any false or misleading representations made by You or Your Customers; <br />
            any fraud by You and/or your Customers; <br />
            any third-party claims arising out of the transaction between You and the Customer.{' '}
            <br />
            <br />
            8. Razorpay shall not be liable for any indirect, incidental, punitive, or consequential
            damages, or for any loss of profits or revenues, even if Razorpay was advised of the
            possibility of such damages. Razorpay's aggregate liability under these Terms shall not
            exceed the total fees received by Razorpay from You for the Money Back Promise service
            in the preceding (1) month. <br />
            <br />
            9. Razorpay reserves the right to suspend or terminate the Money Back Promise at any
            time, with or without cause. In such cases, any obligations accrued prior to termination
            shall survive. <br />
            <br />
            10. The Money Back Promise is a goodwill warranty program offered by Razorpay and does
            not constitute an insurance product. Nothing herein shall be construed as creating an
            agency, partnership, or joint venture between You and Razorpay, and You shall not
            represent otherwise. <br />
            <br />
          </Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" width="100%">
          <Button isLoading={isLoading} onClick={handleActivateNow}>
            Agree and activate
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
