import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { TOOLTIP_TEXT_VS_ERROR_CATEGORIES } from '../../constants';

const TabPane = (props) => {
  const { ariaLabel, selected, onTabChange, tabs } = props;

  return (
    <div role="tablist" aria-label={ariaLabel} className="vtab__tablist">
      {tabs?.map((tabDetails, idx) => {
        const { totalCount, label, errorDisplayText } = tabDetails;
        const toolTipText = TOOLTIP_TEXT_VS_ERROR_CATEGORIES[label];
        return (
          <button
            key={`vtab__tabPane-${idx}`}
            id={`vtab__tabPane-${idx}`}
            className={`vtab__tablist__tab ${idx === selected ? 'selected' : ''}`}
            onClick={() => onTabChange(idx)}
            role="tab"
            aria-clicked={idx === selected}
            aria-controls={`vtab__tabContent-${idx}`}
          >
            <div className="tab-card">
              <div className="tab-card__info">
                <div className="info-value">
                  <p className="rate">{totalCount}</p>
                </div>
                <div className="info-label">
                  <p className="label-text">{errorDisplayText}</p>
                  {toolTipText && (
                    <span className="info-icon">
                      <i className="i i-info-outline" />
                      <Popover align="top">
                        <PopoverBody>
                          <div>{toolTipText}</div>
                        </PopoverBody>
                      </Popover>
                    </span>
                  )}
                </div>
              </div>
              {idx === selected && <i className="i i-chevron-right" />}
            </div>
          </button>
        );
      })}
    </div>
  );
};

export default TabPane;
