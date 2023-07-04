import { useEffect, useRef, useState } from 'react';
import { CountryCodeContainer } from 'common/components/CountryCodeInput/styled';
import {
  DEPTH_MAP,
  marginLeft,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/zoneUtils';
import { GLOBAL_KEY } from 'merchant/views/MagicCheckout/CODSettings/constants';
const ZoneItem = ({
  item,
  selectedZones,
  handleSelectedZones,
  countryZone,
  searchText = '',
  handleCollapse,
  collapsed,
  zone,
}) => {
  const inputRef = useRef(null);
  const isCountry = item.depth === DEPTH_MAP.COUNTRY;
  const isParent = item.depth !== DEPTH_MAP.STATE;
  const [showStates, setShowStates] = useState(collapsed);
  const toggleShowStates = () => {
    handleCollapse(item.code, item.index);
  };

  const isItemDisabled =
    zone && item.zone_name
      ? item.zone_name !== zone
      : zone && countryZone
      ? countryZone !== zone
      : countryZone || item.zone_name;

  const handleChange = (e) => {
    handleSelectedZones(item, e.target.checked);
  };

  const updateIndeterminateStatus = () => {
    if (isParent && item.total_selected > 0) {
      inputRef.current.indeterminate = item.total_selected < item.total_selectable_children;
    } else {
      inputRef.current.indeterminate = false;
    }
  };

  useEffect(() => {
    if (inputRef.current) {
      updateIndeterminateStatus();
    }
  }, [item.total_selected, inputRef.current]);

  useEffect(() => {
    setShowStates(collapsed);
  }, [collapsed]);

  const { states } = item;
  const hasStates = states && Object.keys(states).length > 0;

  const depth = searchText ? item.depth - 1 : item.depth;
  if (item.code === GLOBAL_KEY && searchText.length > 0) {
    return null;
  }
  if (item.visible_status === 'none') return null;
  return (
    <>
      <div
        style={{ marginLeft: marginLeft[depth] }}
        className={`${isItemDisabled ? 'disabled' : ''} modal-item`}
        data-testid="zone-item"
      >
        <div className="checkbox-wrapper">
          <input
            id={item.name}
            type="checkbox"
            ref={inputRef}
            checked={item.selected}
            className="modal-checkbox"
            onChange={handleChange}
          />
          {isCountry && (
            <CountryCodeContainer>
              <div className="country-code-input">
                <span className={`flag ${item.code.toLowerCase()}`} />
              </div>
            </CountryCodeContainer>
          )}
          <label htmlFor={item.name}>{item.name}</label>
        </div>
        <div>
          {hasStates && (
            <p onClick={toggleShowStates} className="states-trigger">
              {item.total_selected} of {item.total_children} states
              <i className={`i i-chevron-${showStates ? 'up' : 'down'}`} />
            </p>
          )}
          {isItemDisabled && (
            <p className="in-zone-text">In {item.zone_name || countryZone} zone</p>
          )}
        </div>
      </div>
      {hasStates && showStates && (
        <div className="states">
          {Object.keys(states).map((code) => {
            const state = item.states[code];
            return (
              <ZoneItem
                key={state.code}
                item={state}
                searchText={searchText}
                countryZone={item.zone_name}
                selectedZones={selectedZones}
                handleSelectedZones={handleSelectedZones}
                zone={zone}
              />
            );
          })}
        </div>
      )}
    </>
  );
};

export default ZoneItem;
