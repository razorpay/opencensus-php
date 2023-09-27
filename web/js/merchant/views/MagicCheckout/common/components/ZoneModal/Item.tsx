import React, { ChangeEvent, useEffect, useRef, useState } from 'react';

import { CountryCodeContainer } from 'common/components/CountryCodeInput/styled';

import { DEPTH_MAP, GLOBAL_KEY, MARGIN_LEFT, VISIBLE_STATES } from './constants';
import { ZoneItem as StyledZoneItem, ZoneCheckboxWrapper } from './styles';
import { ZoneItemProps } from './types';

const ZoneItem = ({
  item,
  handleSelectedZones,
  countryZone,
  searchText = '',
  handleCollapse,
  collapsed,
  zone,
}: ZoneItemProps): JSX.Element | null => {
  const inputRef = useRef<HTMLInputElement>(null);
  const isCountry: boolean = item.depth === DEPTH_MAP.COUNTRY;
  const isParent: boolean = item.depth !== DEPTH_MAP.STATE;
  const [canShowStates, setCanShowStates] = useState<boolean | undefined>(collapsed);

  const toggleShowStates = () => {
    if (handleCollapse) {
      handleCollapse(item.code, item.index);
    }
  };

  // to disable selecting locations that are present in other zones
  const isItemDisabled: boolean =
    zone && item.zone_name
      ? item.zone_name !== zone
      : zone && countryZone
      ? countryZone !== zone
      : countryZone || item.zone_name;

  const isCountryFullySelected =
    isCountry && item.total_selectable_children === 0 && item.total_children > 0;

  const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
    handleSelectedZones(item, e.currentTarget.checked);
  };

  const updateIndeterminateStatus = () => {
    if (inputRef.current) {
      if (isParent && item.total_selected > 0) {
        inputRef.current.indeterminate = item.total_selected < item.total_selectable_children;
      } else {
        inputRef.current.indeterminate = false;
      }
    }
  };

  useEffect(() => {
    if (inputRef.current) {
      updateIndeterminateStatus();
    }
  }, [item.total_selected, inputRef.current]);

  useEffect(() => {
    setCanShowStates(collapsed);
  }, [collapsed]);

  const { states } = item;
  const hasStates = states && Object.keys(states).length > 0;

  const depth = searchText ? item.depth - 1 : item.depth;
  if (item.code === GLOBAL_KEY && searchText.length > 0) {
    return null;
  }

  if (item.visible_status === VISIBLE_STATES.NONE) return null;

  return (
    <>
      <StyledZoneItem
        isDisabled={isItemDisabled || isCountryFullySelected}
        style={{ marginLeft: MARGIN_LEFT[depth] }}
        data-testid="zone-item"
      >
        <ZoneCheckboxWrapper>
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
                <span className={`flag ${item?.code?.toLowerCase()}`} />
              </div>
            </CountryCodeContainer>
          )}
          <label htmlFor={item.name}>{item.name}</label>
        </ZoneCheckboxWrapper>
        <div>
          {hasStates && (
            <p onClick={toggleShowStates} className="states-trigger">
              {item.total_selected} of {item.total_children} states
              <i className={`i i-chevron-${canShowStates ? 'up' : 'down'}`} />
            </p>
          )}
          {isItemDisabled && (
            <p className="in-entity-text">In {item.zone_name || countryZone} zone</p>
          )}
        </div>
      </StyledZoneItem>
      {hasStates && canShowStates && (
        <div className="states">
          {Object.keys(states).map((code) => {
            const state = item.states[code];
            return (
              <ZoneItem
                key={state.code}
                item={state}
                searchText={searchText}
                countryZone={item.zone_name}
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
