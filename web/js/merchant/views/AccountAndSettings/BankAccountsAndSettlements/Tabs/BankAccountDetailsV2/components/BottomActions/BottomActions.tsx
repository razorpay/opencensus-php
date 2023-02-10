import { Button } from '@razorpay/blade/components';
import { StyledDivider } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import React from 'react';
import { StyledBottomAction } from './styled';

const BottomActions = ({
  onClose,
  onSubmit,
  isSubmitDisabled,
  submitCTALabel,
}: {
  onClose: () => void;
  isSubmitDisabled: boolean;
  onSubmit: () => void;
  submitCTALabel: string;
}): JSX.Element => {
  return (
    <React.Fragment>
      <StyledDivider />
      <StyledBottomAction>
        <Button
          iconPosition="left"
          onClick={onClose}
          size="medium"
          type="button"
          variant="tertiary"
        >
          Cancel
        </Button>
        <Button
          iconPosition="left"
          onClick={onSubmit}
          size="medium"
          type="button"
          variant="primary"
          isDisabled={isSubmitDisabled}
          data-testid="bottomaction-submit"
        >
          {submitCTALabel}
        </Button>
      </StyledBottomAction>
    </React.Fragment>
  );
};

export default BottomActions;
