import React, { useCallback } from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getFormattedNumber } from 'merchant/views/Transactions/SuccessRate/helper';
import {
  ERROR_CATEGORIES,
  ERROR_CATEGORIES_VS_DISPLAY_TEXT,
  TOOLTIP_TEXT_VS_ERROR_CATEGORIES,
} from 'merchant/views/Transactions/SuccessRate/constants';

const TabPane = (props) => {
  const { isLoading, ariaLabel, selectedTab, onTabChange, tabData = {} } = props;

  const onChange = useCallback((e) => onTabChange(Number(e?.currentTarget?.id)), [onTabChange]);

  return (
    <div role="tablist" aria-label={ariaLabel} className="vtab__tablist" data-testid="tablist">
      {Object.values(ERROR_CATEGORIES).map((tabKey, idx) => {
        const isActive = idx === selectedTab;
        const title = ERROR_CATEGORIES_VS_DISPLAY_TEXT[tabKey];
        const infoText = TOOLTIP_TEXT_VS_ERROR_CATEGORIES[tabKey];
        const totalCount =
          tabData?.[tabKey]?.reduce((count, data) => count + (data?.count ?? 0), 0) ?? 0;

        return (
          <button
            key={`vtab__tabPane-${idx}`}
            id={idx}
            className={`vtab__tablist__tab${isActive ? ' selected' : ''}`}
            onClick={onChange}
            type="button"
            role="tab"
            aria-pressed={isActive}
            aria-controls={`vtab__tabContent-${idx}`}
            aria-label={`${tabKey}-tab-button`}
          >
            <div className="tab-card">
              <div className="tab-card__info">
                <div className="info-value">
                  {isLoading ? (
                    <PlaceholderLoader style={{ width: '30px', height: '16px' }} />
                  ) : (
                    <p className="rate">{getFormattedNumber(totalCount ?? 0)}</p>
                  )}
                </div>
                {isLoading ? (
                  <PlaceholderLoader style={{ height: '18px' }} />
                ) : (
                  <div className="info-label">
                    <p className="label-text">{title}</p>
                    {infoText && (
                      <span className="info-icon">
                        <i className="i i-info-outline" />
                        <Popover align="right">
                          <PopoverBody>
                            <div>{infoText}</div>
                          </PopoverBody>
                        </Popover>
                      </span>
                    )}
                  </div>
                )}
              </div>
              {isActive && <i className="i i-chevron-right" />}
            </div>
          </button>
        );
      })}
    </div>
  );
};

export default TabPane;
