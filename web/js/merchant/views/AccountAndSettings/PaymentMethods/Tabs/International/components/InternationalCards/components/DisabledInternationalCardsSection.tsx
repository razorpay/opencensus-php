import { Button, Link } from '@razorpay/blade/components';
import { DisabledInternationalCardsReasons } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import React from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import {
  StyledDisabledHeading,
  StyledDisabledInternationalCardsSection,
  StyledDisabledSubtitle,
  StyledNotActivatedTopSection,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/Styled';
import { trackIEEvent } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import { updateWebsitePathWithCta } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner';

interface Props extends RouteComponentProps {
  reason: null | DisabledInternationalCardsReasons;
}

const DisabledInternationalCardsSection = ({ reason, history }: Props): JSX.Element => {
  const renderContent = (): JSX.Element | null => {
    switch (reason) {
      case DisabledInternationalCardsReasons.NOT_ACTIVATED:
        return (
          <>
            <StyledNotActivatedTopSection>
              <StyledDisabledHeading>
                Complete your KYC to request for international cards
              </StyledDisabledHeading>
              <StyledDisabledSubtitle>
                You can only request for this after KYC is complete
              </StyledDisabledSubtitle>
            </StyledNotActivatedTopSection>

            <Button
              variant="secondary"
              size="small"
              type="button"
              onClick={() => {
                trackIEEvent({
                  objectName: 'Complete KYC',
                  actionName: 'Clicked',
                });
                history.push('/activation');
              }}
            >
              Complete KYC
            </Button>
          </>
        );
      case DisabledInternationalCardsReasons.RISK_FOH:
        return (
          <StyledNotActivatedTopSection>
            <StyledDisabledHeading>
              Submit the required document to request for international cards
            </StyledDisabledHeading>

            <StyledDisabledSubtitle>
              You’ll need to submit one of the following documents of your business to&nbsp;
              <Link href="mailto:riskfundsonhold@razorpay.com">riskfundsonhold@razorpay.com</Link>
              &nbsp;for our team to verify:
            </StyledDisabledSubtitle>
            {['Detailed invoices', 'Proof of delivery', 'Any other proof confirming the above'].map(
              (item) => (
                // Used &nbsp; because li and ul has default styles from the classes
                <StyledDisabledSubtitle key={item}>
                  &nbsp;&bull;&nbsp;&nbsp;{item}
                </StyledDisabledSubtitle>
              ),
            )}
            <StyledDisabledSubtitle>
              Once you share the required details, we’ll share an update within 72 hours. You’ll be
              able to receive collected payments in your bank account and request for international
              payments only after this is complete
            </StyledDisabledSubtitle>
          </StyledNotActivatedTopSection>
        );
      case DisabledInternationalCardsReasons.UNREGISTERED:
        return (
          <StyledNotActivatedTopSection>
            <StyledDisabledHeading>
              Your business type is not supported for international card payments
            </StyledDisabledHeading>
            <StyledDisabledSubtitle>
              Link your PayPal account to collect international payments
            </StyledDisabledSubtitle>
          </StyledNotActivatedTopSection>
        );
      case DisabledInternationalCardsReasons.NO_WEBSITE_DETAILS:
        return (
          <>
            <StyledNotActivatedTopSection>
              <StyledDisabledHeading>
                Update your website details to request for international payments
              </StyledDisabledHeading>
              <StyledDisabledSubtitle>
                Your website must be registered with Razorpay to request for international payments
              </StyledDisabledSubtitle>
            </StyledNotActivatedTopSection>
            <Button
              variant="secondary"
              size="small"
              type="button"
              onClick={() => {
                trackIEEvent({
                  objectName: 'Update Website Details',
                  actionName: 'Clicked',
                });
                history.push(updateWebsitePathWithCta);
              }}
            >
              Update Website Details
            </Button>
          </>
        );
      default:
        return null;
    }
  };

  return (
    <StyledDisabledInternationalCardsSection data-testid="disabled-international-cards-message">
      {renderContent()}
    </StyledDisabledInternationalCardsSection>
  );
};

export default withRouter(DisabledInternationalCardsSection);
