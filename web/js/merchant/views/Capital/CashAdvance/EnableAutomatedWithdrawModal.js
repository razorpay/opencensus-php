import React from 'react';
import Amount from 'common/ui/Amount';
import { connect } from 'react-redux';
import Popover, { PopoverBody } from 'common/ui/Popover';
import SuccesfullyEnabledModal from './SuccessfullyEnabledModal';
import trackAutomatedCA from './ga/automated';
import { updateAutomatedLOCConfig } from 'merchant/reducers/capital/withdrawals';

const EnableAutomatedWithdrawModal = ({
  onClose,
  openModal,
  userID,
  interest,
  principle,
  max_withdraw_amount,
  end_day_limit,
  startAutomatedTagPulsating,
  updateAutomatedLOCConfig,
}) => {
  const handleEnableAutomatedWithdrawalsClick = () => {
    trackAutomatedCA.clickEnableAutomatedWithdrawal({});
    updateAutomatedLOCConfig({
      owner_id: userID,
      automated_loc: true,
    })
      .then((res) => {
        trackAutomatedCA.openSuccessfullyEnabledAutomatedWithdrawModal();
        startAutomatedTagPulsating();
        openModal({
          component: <SuccesfullyEnabledModal onClose={onClose} />,
          size: 'small',
        });
      })
      .catch((e) => {
        console.log(e);
      });
  };

  const handleModalClose = () => {
    trackAutomatedCA.clickCloseInEnableAutomatedModal({});
    onClose();
  };

  return (
    <div className="enable-automated-withdrawal-modal">
      <div className="cross-btn" onClick={handleModalClose}>
        <i class="i i-close" />
      </div>
      <div className="enable-automated-withdrawal-modal--heading">Enable Automated Withdraw</div>
      <div className="enable-automated-withdrawal-modal--description">
        Get funds for your business automatically from your available cash advance balance as soon
        as you repay your current withdrawal.
      </div>

      <div className="enable-automated-withdrawal-modal--subModal">
        <div className="enable-automated-withdrawal-image">
          <img src={require("assets/capital/enable-automated-withdrawal.svg")} />
        </div>
        <div className="enable-automated-withdrawal-modal--subModal-heading">
          Here’s how it works:
        </div>
        <div className="enable-automated-withdrawal-modal--subModal-list">
          <div className="round-circle" />
          <div className="enable-automated-withdrawal-modal--subModal-list-item">
            Withdraw maximum available amount upto{' '}
            <Amount
              className="enable-automated-withdrawal-modal--subModal-amount"
              value={Number(max_withdraw_amount)}
            />{' '}
            automatically
          </div>
        </div>
        <div className="enable-automated-withdrawal-modal--subModal-list">
          <div className="round-circle" />
          <div className="enable-automated-withdrawal-modal--subModal-list-item">
            Get the maximum repayment tenure upto{' '}
            <span className="list-text-3">{end_day_limit}</span>{' '}
            <span className="list-text-days">days</span> to pay it back
          </div>
        </div>
        <button
          class="btn btn-primary enable-withdrawals-btn"
          onClick={handleEnableAutomatedWithdrawalsClick}
        >
          Enable Automated Withdrawals
        </button>
      </div>

      <div className="enable-automated-withdrawal-modal--repayable-amount">
        <div className="flex">
          <div className="rounded-square" />
          <div className="enable-automated-withdrawal-modal--repayable-amount-text">
            Repayable Amount
            <span>
              <i
                onMouseOver={() => trackAutomatedCA.hoverInfoIconInEnableAutomatedModal()}
                className="i i-info-outline"
              />
              <Popover
                theme="dark"
                align="bottom"
                parentQuerySelector=".enable-automated-withdrawal-modal"
              >
                <PopoverBody>
                  <div className="enable-automated-withdrawal-modal--tooltip">
                    <div className="flex mb-6">
                      <div>Principal Repayable</div>
                      <div>
                        <Amount value={principle * 100} />
                      </div>
                    </div>
                    <div className="flex">
                      <div>Interest Repayable</div>
                      <div>
                        <Amount value={interest * 100} />
                      </div>
                    </div>
                    <div>(0.09% per day)</div>
                    <div className="total-repayable-amount flex">
                      <div>Total Repayable Amount</div>
                      <div>
                        <Amount value={(principle + interest) * 100} />
                      </div>
                    </div>
                  </div>
                </PopoverBody>
              </Popover>
            </span>
          </div>
        </div>
        <div>
          <Amount
            className="enable-automated-withdrawal-modal--amount"
            value={(principle + interest) * 100}
          />
        </div>
      </div>
      <div className="enable-automated-withdrawal-modal--repayable-text">
        The repayable amount of will be collected in equal instalments from your settlement balance.
      </div>
    </div>
  );
};

export default connect((state) => ({ userID: state.session.user.current }), {
  updateAutomatedLOCConfig,
})(EnableAutomatedWithdrawModal);
