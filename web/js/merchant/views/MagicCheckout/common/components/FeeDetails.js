import { RULE_TYPES } from 'merchant/views/MagicCheckout/constants';
import { useState, useEffect } from 'react';

const FeeDetails = ({ rule_type, flat, slabs, type, label }) => {
  const [slabsToDisplay, setSlabsToDisplay] = useState([]);
  const [remainingSlabsCount, setRemainingSlabsCount] = useState([]);
  const text =
    rule_type !== RULE_TYPES.SLABS ? `Flat ${label} fees for all orders` : `${label} Slabs`;

  const modifyDisplaySlabs = (endInd) => {
    const displaySlabs = slabs.slice(0, endInd);
    setRemainingSlabsCount(slabs?.length - displaySlabs?.length);
    setSlabsToDisplay(displaySlabs);
  };

  useEffect(() => {
    if (rule_type === RULE_TYPES.SLABS && slabs) {
      modifyDisplaySlabs(3);
    }
  }, [slabs]);

  const renderFlatRule = (fee) => (
    <div
      className={`display-flex justify-space-between c-fee-details fee-bg fee-slabs-table
      ${type === 'shipping' ? ' c-fee-details-shipping' : ''}`}
    >
      {fee !== RULE_TYPES.FREE && <span className="font-12 font-bold color-black">{text}</span>}
      <div className="display-flex flex-center">
        <i className="i i-rupee font-10 rupee-icon-fees-details" />
        {fee}
      </div>
    </div>
  );

  const renderSlabsRule = () => (
    <div className="c-fee-details no-padding fee-slabs-table">
      <div className="padding-8 fee-bg">
        <div className="padding-8 slabs-border color-black font-bold">{`${label} Slabs`}</div>
        <div className="padding-8 display-flex justify-space-between">
          <span className="color-black font-12">Order Range</span>
          <span className="color-black font-12">{`${label} Fee`}</span>
        </div>
      </div>
      {slabsToDisplay &&
        slabsToDisplay.map((item) => (
          <div
            key={item.gte}
            className="display-flex slabs-border padding-16 justify-space-between"
          >
            <div className="display-flex flex-center">
              {item.lte === Infinity ? (
                <div>
                  {'>'}
                  <i className="i i-rupee font-10 rupee-icon-fees-details" /> {item.gte}
                </div>
              ) : (
                <>
                  <i className="i i-rupee font-10 rupee-icon-fees-details" />
                  {item.gte} - <i className="i i-rupee font-10 rupee-icon-fees-details" />{' '}
                  {item.lte}
                </>
              )}
            </div>
            <div className="display-flex flex-center">
              <i className="i i-rupee font-10 rupee-icon-fees-details" />
              {item.fee}
            </div>
          </div>
        ))}
      {remainingSlabsCount > 0 ? (
        <div
          className="padding-16 pointer color-blue-1"
          onClick={() => modifyDisplaySlabs(slabs.length)}
        >
          View {remainingSlabsCount} more {label} slabs
        </div>
      ) : null}
    </div>
  );
  return (
    <div className="fees-details-container">
      {rule_type === RULE_TYPES.FLAT ? renderFlatRule(flat) : null}
      {rule_type === RULE_TYPES.SLABS ? renderSlabsRule() : null}
      {rule_type === RULE_TYPES.FREE ? renderFlatRule(RULE_TYPES.FREE) : null}
    </div>
  );
};

export default FeeDetails;
