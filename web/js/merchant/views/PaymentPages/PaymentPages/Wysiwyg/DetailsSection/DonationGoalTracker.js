import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import moment from 'moment';

import Button from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { Modal, ModalMask, ModalContent } from 'common/new-ui/Modal';
import FieldsDropdownWrapper from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldsDropdown';
import DonationGoalTrackerPreview from './DonationGoalTrackerPreview';
import AmountBasedModalContent from './AmountBasedModalContent';
import SupporterBasedModalContent from './SupporterBasedModalContent';
import BottomSheet from 'common/components/BottomSheet';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { parseGoalTrackerAmountValues } from './helpers';
import debounce from 'common/utils/debounce';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';
import FIELD_TYPES_MAP from './helpers/fieldTypes';
import { isMobileDevice } from 'merchant/components/Home/data';

const sampleData = {
  tracker_type: 'donation_amount_based',
  is_active: '1',
  meta_data: {
    goal_amount: '10000',
    collected_amount: '0', // don't send during api call
    display_supporter_count: '1',
    supporter_count: '0', // don't send during api call
    display_days_left: '1',
    goal_end_timestamp: '1657097182',
  },
};

const sampleData2 = {
  tracker_type: 'donation_supporter_based',
  is_active: '1',
  meta_data: {
    available_units: '100',
    display_available_units: '1',
    sold_units: '0', // don't send during api call
    display_sold_units: '1',
    display_supporter_count: '1',
    supporter_count: '0', // don't send during api call
    display_days_left: '1',
    goal_end_timestamp: '1657097182',
  },
};

@connect(
  (state) => ({
    currency: state.wysiwyg.paymentPageEntity?.currency || 'INR',
    user: state.session.user,
  }),
  {
    showNotification,
    closeModal,
    openModal,
  },
)
@RTracking(() => window.rzpQ.component('PaymentButtonDetails'))
export default class DonationGoalTracker extends React.PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  state = this.initState();

  componentWillUnmount() {
    // remove resize event listner on component unmount
    window.removeEventListener('scroll', this.handleModalPosition);
  }

  initState() {
    return {
      tracker_type: this.props.goal_tracker?.tracker_type || 'donation_amount_based',
      is_active: this.props.goal_tracker?.is_active || '0',
      isEditable: false,
      meta_data: this.props.goal_tracker?.meta_data
        ? parseGoalTrackerAmountValues(this.props.goal_tracker.meta_data)
        : null,
      endDate:
        this.props.goal_tracker?.meta_data && this.props.goal_tracker.meta_data.goal_end_timestamp
          ? moment.unix(this.props.goal_tracker.meta_data.goal_end_timestamp)
          : moment().add(30, 'days').set({ hour: 23, minute: 59, seconds: 59 }),
      isBottomSheetOpen: false, // for showing details in mobile view
    };
  }

  handleClick = () => {
    this.setState({ isEditable: true, isBottomSheetOpen: true }, () => {
      this.handleModalPosition();
      // add resize event listner on modal open
      window.addEventListener('resize', this.handleModalPosition);
    });
  };

  handleModalPosition = debounce(() => {
    const goalTrackerMainNode = document.querySelector('#goal-tracker-details');
    if (goalTrackerMainNode) {
      // set top & left position of modals (based on main preview)
      const rect = goalTrackerMainNode.getBoundingClientRect();

      const modalPreviewNode = document.querySelector('.Modal-container--goal-tracker-preview');
      if (modalPreviewNode) {
        modalPreviewNode.style.top = `${rect.top + 12}px`; // margin-top of 12px
        modalPreviewNode.style.left = `${rect.left}px`;
        modalPreviewNode.style.width = `${rect.width}px`;

        // if mobile, open on top as details shown in bottom sheet
        if (isMobileDevice()) {
          modalPreviewNode.style.top = '100px';
        }

        // displaying the modal only after setting the position, helps avoid the jerky movement
        modalPreviewNode.style.display = 'block';
      }

      const modalEditorNode = document.querySelector('.Modal-container--goal-tracker');
      if (modalEditorNode) {
        modalEditorNode.style.top = `${rect.top}px`;
        modalEditorNode.style.left = `${rect.left + rect.width + 20}px`; // 416px is width of modal + 20px spacing
      }
    }
  }, 100);

  handleClose = () => {
    // reset local state, update reducer & close modal
    const newState = this.initState();
    this.setState(newState);
    // remove resize event listner on modal close
    window.removeEventListener('scroll', this.handleModalPosition);
  };

  onSelect = (e) => {
    // set default values & open dual modals
    const { key } = e;

    track.wysiwyg.addGoalTrackerBtnType(key);

    if (key === 'donation_amount_based') {
      this.setState({
        tracker_type: 'donation_amount_based',
        meta_data: this.props.goal_tracker
          ? generateGoalTrackerMetadata(this.props.goal_tracker.meta_data, key)
          : sampleData.meta_data,
        is_active: '1',
      });

      this.handleClick();
    } else {
      this.setState({
        tracker_type: 'donation_supporter_based',
        meta_data: this.props.goal_tracker
          ? generateGoalTrackerMetadata(this.props.goal_tracker.meta_data, key)
          : sampleData2.meta_data,
        is_active: '1',
      });

      this.handleClick();
    }
  };

  onEndDateChange = (endDate) => {
    this.setState({ endDate });
  };

  onMetaDataChange = (key, value) => {
    this.setState((prevState) => ({
      meta_data: {
        ...prevState.meta_data,
        [key]: value,
      },
    }));
  };

  handleSubmit = () => {
    const { tracker_type, is_active, meta_data, endDate } = this.state;

    let request;
    track.wysiwyg.saveGoalTracker(
      this.props.goal_tracker &&
        this.props.goal_tracker.meta_data &&
        !!this.props.goal_tracker.meta_data.goal_end_timestamp_formatted,
    );

    if (tracker_type === 'donation_amount_based') {
      const {
        goal_amount,
        display_supporter_count,
        display_days_left,
        collected_amount,
        supporter_count,
      } = meta_data;
      request = {
        tracker_type: 'donation_amount_based',
        is_active,
        meta_data: {
          goal_amount: String(Number(goal_amount) * 100),
          collected_amount,
          supporter_count,
          display_supporter_count,
          display_days_left,
          goal_end_timestamp: endDate.unix(),
        },
      };
    } else {
      const {
        available_units,
        display_available_units,
        display_sold_units,
        display_supporter_count,
        display_days_left,
        sold_units,
        supporter_count,
      } = meta_data;
      request = {
        tracker_type: 'donation_supporter_based',
        is_active,
        meta_data: {
          available_units,
          display_available_units,
          display_sold_units,
          display_supporter_count,
          display_days_left,
          goal_end_timestamp: endDate.unix(),
          sold_units,
          supporter_count,
        },
      };
    }

    // api call / modify store
    this.props.updateData({
      target: {
        name: 'goal_tracker',
        value: request,
      },
    });
    this.setState({ isEditable: false, isBottomSheetOpen: false });
  };

  editGoal = () => {
    track.wysiwyg.editGoalTrackerBtn(
      this.props.goal_tracker &&
        this.props.goal_tracker.meta_data &&
        !!this.props.goal_tracker.meta_data.goal_end_timestamp_formatted,
    );
    this.handleClick();
  };

  removeGoal = () => {
    // reset state
    // deactivate modal in redux/api
    this.context.confirm({
      header: 'Remove Goal Tracker?',
      message: (
        <>
          Are you sure you want to remove the Goal Tracker from this Payment Page?
          <br />
          <br />
          Please note, if required, Goal Tracker can be added back again. The progress of your goals
          will be retained if it is added back again.
        </>
      ),
      affirmativeLabel: 'Yes, Remove',
      affirmativePendingLabel: 'Removing',
      abortLabel: 'No, Don’t!',
      action: () => {
        track.wysiwyg.deleteGoalTracker(
          this.props.goal_tracker &&
            this.props.goal_tracker.meta_data &&
            !!this.props.goal_tracker.meta_data.goal_end_timestamp_formatted,
        );
        // update redux & local state
        this.props.updateData({
          target: {
            name: 'goal_tracker',
            value: { ...this.props.goal_tracker, is_active: '0' },
          },
        });
        this.setState({ is_active: '0', meta_data: null });
      },
      abort: () => {
        // cancel tracker
      },
    });
  };

  render() {
    const { isEditable, meta_data, endDate, is_active, tracker_type, isBottomSheetOpen } =
      this.state;
    const { currency, user } = this.props;

    const isMobile = isMobileDevice();

    return (
      <div
        id="goal-tracker-details"
        className={is_active === '1' && !isEditable ? 'is-active' : ''}
      >
        {!isEditable && is_active !== '1' ? (
          <GoalTrackerDropdown
            onSelect={this.onSelect}
            countryCode={user.merchant.country_code}
            trigger={
              <>
                <Button.Transparent
                  class="btn-link btn-goal-tracker"
                  onClick={track.wysiwyg.addGoalTrackerBtn}
                >
                  + Add a Goal Tracker <i className="i i-info-outline" />{' '}
                  <span class="badge bg-success">NEW</span>
                </Button.Transparent>
                <Popover className="rzp-tooltip-donation-tracking" align="top" theme="dark">
                  <PopoverBody>
                    <div className="rzp-tooltip-title">Whats a Goal Tracker?</div>
                    Setup and show progress of tangible goals on your Payment Page and help your
                    audience visualise how their contributions are going.
                    <br />
                    <br />
                    This can also help convert potential customers and supporters by viewing progess
                    of your goals and existing supporters.
                  </PopoverBody>
                </Popover>
                <div class="title-underline" />
              </>
            }
          />
        ) : isEditable ? (
          <div className="goal-tracker--blank-preview" />
        ) : (
          <DonationGoalTrackerPreview
            meta_data={meta_data}
            is_active={is_active}
            tracker_type={tracker_type}
            endDate={endDate}
            isMain={true}
            editGoal={this.editGoal}
            removeGoal={this.removeGoal}
            currency={currency}
          />
        )}
        {isEditable && (
          <ModalMask>
            <Modal className="goal-tracker-preview" showCloseBtn={false}>
              <ModalContent>
                <DonationGoalTrackerPreview
                  meta_data={meta_data}
                  is_active={is_active}
                  tracker_type={tracker_type}
                  endDate={endDate}
                  currency={currency}
                />
              </ModalContent>
            </Modal>
            {/*
              goal tracker settings shown in modal for desktop and in a bottom sheet for mobile
            */}
            {!isMobile ? (
              <Modal onClose={this.handleClose} className="goal-tracker">
                <ModalContent>
                  {tracker_type === 'donation_amount_based' ? (
                    <AmountBasedModalContent
                      handleClose={this.handleClose}
                      endDate={endDate}
                      onEndDateChange={this.onEndDateChange}
                      meta_data={meta_data}
                      onMetaDataChange={this.onMetaDataChange}
                      handleSubmit={this.handleSubmit}
                      currency={currency}
                    />
                  ) : (
                    <SupporterBasedModalContent
                      handleClose={this.handleClose}
                      endDate={endDate}
                      onEndDateChange={this.onEndDateChange}
                      meta_data={meta_data}
                      onMetaDataChange={this.onMetaDataChange}
                      handleSubmit={this.handleSubmit}
                    />
                  )}
                </ModalContent>
              </Modal>
            ) : (
              <BottomSheet
                isOpen={isBottomSheetOpen}
                isControlled
                className="goal-tracker--bottom-sheet paymentpage-container-goal-tracker"
                onDismiss={this.handleClose}
                isBlocking={false}
              >
                <div class="Modal-container--goal-tracker">
                  <ModalContent>
                    {tracker_type === 'donation_amount_based' ? (
                      <AmountBasedModalContent
                        handleClose={this.handleClose}
                        endDate={endDate}
                        onEndDateChange={this.onEndDateChange}
                        meta_data={meta_data}
                        onMetaDataChange={this.onMetaDataChange}
                        handleSubmit={this.handleSubmit}
                        currency={currency}
                      />
                    ) : (
                      <SupporterBasedModalContent
                        handleClose={this.handleClose}
                        endDate={endDate}
                        onEndDateChange={this.onEndDateChange}
                        meta_data={meta_data}
                        onMetaDataChange={this.onMetaDataChange}
                        handleSubmit={this.handleSubmit}
                      />
                    )}
                  </ModalContent>
                </div>
              </BottomSheet>
            )}
          </ModalMask>
        )}
      </div>
    );
  }
}

const GoalTrackerDropdown = ({ trigger, onSelect, countryCode }) => {
  const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];
  return (
    <FieldsDropdownWrapper
      beforeOptionsTxt="Which goal would you like to track?"
      type="goal-tracker"
      options={[FIELD_TYPES.donation_amount_based, FIELD_TYPES.donation_supporter_based]}
      trigger={trigger}
      onSelect={onSelect}
      showInfo
    />
  );
};

function generateGoalTrackerMetadata(meta_data, key) {
  // preserve few fields from stored api response
  if (key === 'donation_amount_based') {
    return {
      ...sampleData.meta_data,
      goal_amount: meta_data.goal_amount || sampleData.meta_data.goal_amount,
    };
  } else {
    return {
      ...sampleData2.meta_data,
      available_units: meta_data.available_units || sampleData2.meta_data.available_units,
    };
  }
}
