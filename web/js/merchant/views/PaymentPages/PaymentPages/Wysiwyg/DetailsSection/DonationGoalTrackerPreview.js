import moment from 'moment';
import { useEffect, useState } from 'react';
import RangeSlider from 'common/new-ui/RangeSlider';
import Button from 'common/new-ui/Button';
import { getCurrency } from 'common/ui/Amount';
import { convertToLocale } from 'common/utils/rzp-utils';

const DonationGoalTrackerPreview = ({ tracker_type, is_active, meta_data, ...remainingProps }) => {
  if (is_active === '0') return null;
  return tracker_type === 'donation_supporter_based' ? (
    <SupportBasedGoalTrackerPreview {...meta_data} {...remainingProps} />
  ) : (
    <AmountBasedGoalTrackerPreview {...meta_data} {...remainingProps} />
  );
};

const SupportBasedGoalTrackerPreview = ({
  available_units,
  sold_units,
  display_available_units,
  display_sold_units,
  display_supporter_count,
  supporter_count,
  display_days_left,
  endDate,
  // goal_end_timestamp,
  isMain,
  editGoal = () => {},
  removeGoal = () => {},
}) => {
  const [daysLeft, setDaysLeft] = useState(0);
  useEffect(() => {
    if (display_days_left === '1') {
      const now = moment();
      const newDaysLeft = endDate.diff(now, 'days');
      setDaysLeft(newDaysLeft >= 0 ? newDaysLeft : 0);
    }
  }, [display_days_left, endDate]);
  if (display_sold_units === '1' && display_available_units === '1') {
    return (
      <>
        <div className={`goal-tracker--supporter-based ${isMain ? 'goal-tracker--main' : ''}`}>
          <div className="goal-tracker--supporter-based-top">
            <span className="goal-tracker--collected">
              {convertNumberToLongScaleString(sold_units)}
            </span>{' '}
            sold out of {convertNumberToLongScaleString(available_units)}
          </div>
          <div className="goal-tracker--supporter-based-slider">
            <RangeSlider min={0} max={available_units} value={sold_units} step={1} readOnly />
          </div>
          {(display_supporter_count === '1' || display_days_left === '1') && (
            <div className="goal-tracker--supporter-based-bottom">
              {display_supporter_count === '1' && (
                <div className="goal-tracker--supporter-count">
                  <i className="i i-people-outline" />{' '}
                  <span>{convertNumberToLongScaleString(supporter_count)}</span> supporters
                </div>
              )}
              {display_days_left === '1' && (
                <div className="goal-tracker--days-count">
                  <span>{daysLeft}</span> days left
                </div>
              )}
            </div>
          )}
        </div>
        {isMain && (
          <div className="goal--tracker-action-buttons">
            <Button type="button" class="Button--transparent" onClick={editGoal}>
              <i className="i i-edit-outline" /> Edit
            </Button>
            <div className="vertical-divider" />
            <Button type="button" class="Button--transparent" onClick={removeGoal}>
              <i className="i i-delete-outline" /> Remove
            </Button>
          </div>
        )}
      </>
    );
  }

  return (
    <>
      <div
        className={`goal-tracker--supporter-based goal-tracker--supporter-based-noslider ${
          isMain ? 'goal-tracker--main' : ''
        }`}
      >
        {(display_sold_units === '1' ||
          display_supporter_count === '1' ||
          display_days_left === '1') && (
          <div className="goal-tracker--supporter-based-bottom">
            {display_sold_units === '1' && (
              <div className="goal-tracker--sold-count">
                <span>{convertNumberToLongScaleString(sold_units)}</span> sold
              </div>
            )}
            {display_supporter_count === '1' && (
              <div className="goal-tracker--supporter-count">
                <span>{convertNumberToLongScaleString(supporter_count)}</span> supporters
              </div>
            )}
            {display_days_left === '1' && (
              <div className="goal-tracker--days-count">
                <span>{daysLeft}</span> days left
              </div>
            )}
          </div>
        )}
      </div>
      {isMain && (
        <div className="goal--tracker-action-buttons">
          <Button type="button" class="Button--transparent" onClick={editGoal}>
            <i className="i i-edit-outline" /> Edit
          </Button>
          <div className="vertical-divider" />
          <Button type="button" class="Button--transparent" onClick={removeGoal}>
            <i className="i i-delete-outline" /> Remove
          </Button>
        </div>
      )}
    </>
  );
};

const AmountBasedGoalTrackerPreview = ({
  goal_amount,
  collected_amount,
  display_supporter_count,
  supporter_count,
  display_days_left,
  // goal_end_timestamp,
  endDate,
  isMain,
  editGoal = () => {},
  removeGoal = () => {},
  currency,
  countryCode,
}) => {
  const [daysLeft, setDaysLeft] = useState(0);
  useEffect(() => {
    if (display_days_left === '1') {
      const now = moment();
      const newDaysLeft = endDate.diff(now, 'days');
      setDaysLeft(newDaysLeft >= 0 ? newDaysLeft : 0);
    }
  }, [display_days_left, endDate]);
  const currencySymbol = getCurrency(currency).symbol;

  return (
    <>
      <div className={`goal-tracker--amount-based ${isMain ? 'goal-tracker--main' : ''}`}>
        <div className="goal-tracker--amount-based-top">
          <span className="goal-tracker--collected">
            {currencySymbol} {convertToLocale(collected_amount, countryCode)}
          </span>{' '}
          of {currencySymbol} {convertToLocale(goal_amount, countryCode)} collected
        </div>
        <div className="goal-tracker--amount-based-slider">
          <RangeSlider min={0} max={goal_amount} value={collected_amount} step={1} readOnly />
        </div>
        {(display_supporter_count === '1' || display_days_left === '1') && (
          <div className="goal-tracker--amount-based-bottom">
            {display_supporter_count === '1' && (
              <div className="goal-tracker--supporter-count">
                <span>
                  <i className="i i-people-outline" />{' '}
                  {convertNumberToLongScaleString(supporter_count)}
                </span>{' '}
                supporters
              </div>
            )}
            {display_days_left === '1' && (
              <div className="goal-tracker--days-count">
                <span>{daysLeft}</span> days left
              </div>
            )}
          </div>
        )}
      </div>
      {isMain && (
        <div className="goal--tracker-action-buttons">
          <Button type="button" class="Button--transparent" onClick={editGoal}>
            <i className="i i-edit-outline" /> Edit
          </Button>
          <div className="vertical-divider" />
          <Button type="button" class="Button--transparent" onClick={removeGoal}>
            <i className="i i-delete-outline" /> Remove
          </Button>
        </div>
      )}
    </>
  );
};

function convertNumberToLongScaleString(value) {
  // Convert to Number (if string)
  value = Number(value);

  // if (value >= 1000000000000) {
  //   return `${(Math.floor(value / 100000000000) / 10).toFixed(1)}T`;
  // }
  // if (value >= 1000000000) {
  //   return `${(Math.floor(value / 100000000) / 10).toFixed(1)}B`;
  // }
  if (value >= 1000000) {
    return `${Math.floor(value / 100000) / 10}M`;
  }
  if (value >= 1000) {
    return `${Math.floor(value / 100) / 10}K`;
  }
  return value;
}

export default DonationGoalTrackerPreview;
