import React from 'react';
import { Box, Button } from '@razorpay/blade/components';
import WithdrawCredit from './WithdrawCredit';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { useState } from 'react';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { useStore } from 'shell/commonStore';

interface WithdrawButtonProps {
  title: string;
  type: string;
  credits: number;
  submitHandler: () => void | (() => Promise<any>);
}

function WithdrawButton({ title, type, credits, submitHandler }: WithdrawButtonProps) {
  const {
    session: { user, mode },
  } = useStore();
  const [open, setOpen] = useState(false);

  const openModal = () => {
    setOpen(true);
  };

  const closeModal = () => {
    setOpen(false);
  };

  const {
    abExperiments: { pre_fund_withdrawal },
  } = useSplitzService();

  const isPrefundWithdrawalEnabled = isExperimentEnabled(pre_fund_withdrawal);

  const showWithdrawButton = isPrefundWithdrawalEnabled && user.isPgLegderReverseShadowEnabled;
  return (
    <>
      {showWithdrawButton ? (
        <Box
          width={{
            base: '100%',
            s: 'fit-content',
          }}
        >
          <Button isFullWidth variant="tertiary" isDisabled={mode !== 'live'} onClick={openModal}>
            Withdraw Funds
          </Button>
          {open ? (
            <WithdrawCredit
              title={title}
              type={type}
              closeModal={closeModal}
              open={open}
              credits={credits}
              submitHandler={submitHandler}
            />
          ) : null}
          {mode !== 'live' ? (
            <Popover align="bottom" theme="dark">
              <PopoverBody>
                You cannot withdraw {type} credits in test mode. Switch to live mode to withdraw
                credits.
              </PopoverBody>
            </Popover>
          ) : null}
        </Box>
      ) : null}
    </>
  );
}

export default WithdrawButton;
