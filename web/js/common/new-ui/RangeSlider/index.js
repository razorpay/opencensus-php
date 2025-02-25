import React, { useEffect, useState } from 'react';

// TODO - Add onChange handler (onStart, onEnd -> drag)

const RangeSlider = ({ min, max, value }) => {
  const [offset, setOffset] = useState(0);
  useEffect(() => {
    // handle strings & numbers types
    const newValue = Number(value);
    const newMin = Number(min);
    const newMax = Number(max);

    const _offset = ((newValue - newMin) / (newMax - newMin)) * 100;
    setOffset(newValue > newMax ? 100 : _offset);
  }, [value]);
  return (
    <div className="RangeSlider--container">
      <div className="RangeSlider--track" style={{ width: `${offset}%` }} />
      <div className="RangeSlider--thumb" style={{ left: `${offset}%` }} />
    </div>
  );
};

export default RangeSlider;
