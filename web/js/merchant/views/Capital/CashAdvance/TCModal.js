import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import GenericPanel, { PanelBody } from 'merchantLA/components/Home/GenericPanel';
import Table from 'common/ui/Table/Index';

function TCModal({ onClose, trackGA, dueDate, amountSummary, destinationAccountDetails }) {
  const { principle, interest, roi } = amountSummary;

  const repaymentAmount = principle + interest;
  const {
    data: {
      account_attributes: { account_number, ifsc_code },
    },
  } = destinationAccountDetails;

  return (
    <div class="withdrawals-tc-modal">
      <ModalHeader class="header" title="Terms and Conditions" />
      <div>
        <GenericPanel class="panel-default content-wrapper">
          <PanelBody class="no-padding">
            <p class="tc-content">
              This annexure (“Annexure”) to Personal Loan Agreement dated {moment().format('ll')}{' '}
              (“Original Agreement”) <br />
              BETWEEN
              <br />
              <strong>APOLLO FINVEST (INDIA) LIMITED</strong>, a company incorporated under the
              provisions of the Companies Act, 1956, having its registered office at Unit No. 803,
              Blue Moon, 8th Floor, Veera Industrial Estate, New Link Road, Opp. Laxmi Industrial
              Est, Mumbai, Maharashtra - 400053 (hereinafter referred to as the ‘Lender’ or ‘NBFC’,
              which expression shall, unless repugnant to the context or meaning thereof, be deemed
              to mean and include its successors-in-interest and permitted assigns);
              <br />
              <strong>AND</strong> <br />
              RAZORPAY SOFTWARE PRIVATE LIMITED, a company incorporated under the provisions of the
              Companies Act, 1956, having its registered office at 1st Floor, SJR Cyber, 22, Laskar
              Hosur Road, Adugodi, Bangalore -560030 (hereinafter “Razorpay”, which expression
              shall, unless repugnant to the context, include its successors and assigns) of the
              Third Part.
              <br />
              <strong>AND</strong> <br />
              <strong>The Borrower</strong>, material particulars whereof are described and set out
              in Schedule I of this Annexure, Is made at the place and on the date as set out in
              Schedule I of this Annexure. The Lender, the Service Provider and the Borrower are
              hereinafter collectively referred to as “the Parties” and individually as “the Party”.
              <br />
              <strong>WHEREAS:</strong> <br />
              <div class="m-l p-l">
                A. The Parties have entered into the Original Agreement for granting of loan to the
                Borrower. In accordance with the Original Agreement, the NBFC may disburse
                additional amount of the loan to the Borrower after a separate request for
                additional amount of loan is made by the Borrower.
              </div>
              <div class="m-l p-l">
                B. Accordingly, the Parties hereby agree to execute this Annexure to capture the
                terms and conditions with respect to the additional amount of the loan being
                obtained by the Borrower, the details of which are more particularly provided in
                Schedule I of this Annexure (“Additional Loan”). <br />
              </div>
              NOW THEREFORE, in consideration of the mutual covenants, terms and conditions set
              forth herein, and other good and valuable consideration (the adequacy of which are
              hereby mutually acknowledged), the Parties with the intent to be legally bound hereby
              agree as follows:
              <div class="m-l p-l">
                1. The Lender hereby agrees to disburse and the Borrower hereby agrees to borrow
                Loan for the amount described in Schedule I of this Annexure. The amount of Loan
                shall be released into the Borrower’s bank account described in Schedule I of this
                Annexure.
              </div>
              <div class="m-l p-l">
                2. Interest rate, fees and charges in respect of Loan are set in Schedule I of this
                Annexure.
              </div>
              <div class="m-l p-l">
                3. All other terms and conditions of the Original Agreement shall remain unchanged
                and shall equally apply to drawdown obtained by the Borrower.
              </div>
            </p>
            <Table
              class="m-all"
              rows={[
                {
                  title: 'Drawdown Amount',
                  value: <Amount value={principle * 100} currency={'INR'} />,
                },
                {
                  title: 'Rate of Interest per annnum',
                  value: roi * 365 + '%',
                },
                {
                  title: 'Repayment Amount',
                  value: <Amount value={repaymentAmount * 100} currency={'INR'} />,
                },
                {
                  title: 'Repayment Due Date',
                  value: moment(dueDate).format('LL'),
                },
                {
                  title: 'Account Number',
                  value: account_number,
                },
                {
                  title: 'IFSC Code',
                  value: ifsc_code,
                },
              ]}
              columns={[
                { title: 'Particulars', value: ({ title }) => title },
                {
                  title: 'Details',
                  value: ({ value }) => value,
                },
              ]}
            />
          </PanelBody>
        </GenericPanel>
        <div class="modal-footer">
          <Button.Primary
            class="no-margin pull-right Button--small"
            onClick={() => {
              trackGA({
                eventAction: 'Close | Terms and Conditions',
              });
              onClose();
            }}
          >
            Close Terms and Conditions
          </Button.Primary>
        </div>
      </div>
    </div>
  );
}

export default TCModal;
