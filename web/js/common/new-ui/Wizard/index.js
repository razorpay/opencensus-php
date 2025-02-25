import React from 'react';
import { classList } from 'common/utils/rzp-utils';
import sideBarBottomImage from '../../../../icons/merchant/sidebar-bottom.svg';

/*
 * @props
 *   {String}, title:
 *   {String}, description:
 *   {Array}, tabs:
 *   {Array}, tabsValidity:
 *   {Function}, tabClickHandler:
 *   {String}, activeTab:
 *   {Boolean}, activetTabContdition:
 * */
export const ModalAsideNav = (_) => {
  const {
    title,
    description,
    tabs,
    moreTabs,
    tabsValidity,
    tabClickHandler,
    activeTab,
    activeTabContdition,
    disableTabCondition,
    isFieldVerificationFailed = false,
    isBankVerificationFailed = false,
    isActivationFormFullView = false,
    activationFormMilestone,
    saveAndExitForm = () => {},
    isTabChangeDisabled,
  } = _;

  const onLogoClick = () => {
    const segmentObjectName = 'rzp logo';
    saveAndExitForm(segmentObjectName);
  };

  return (
    <aside className="side-bar">
      <div className="side-bar-container">
        {isActivationFormFullView ? (
          <div className="img-container">
            {activationFormMilestone ? (
              <a onClick={onLogoClick}>
                <img src="/img/logo_black.png" />
              </a>
            ) : (
              <img src="/img/logo_black.png" />
            )}
          </div>
        ) : (
          <>
            <side-title>{title}</side-title>
            {description}
          </>
        )}
        <ul>
          {tabs.map((t, i) => {
            const isTabValid = tabsValidity && tabsValidity[i];

            let isActiveClass = i == activeTab && 'active';
            if (typeof activeTabContdition !== 'undefined') {
              // Is defined and is true
              isActiveClass = activeTabContdition && isActiveClass;
            }
            const canShowSuccessCheckbox =
              isFieldVerificationFailed && i === 2
                ? i !== 2
                : isBankVerificationFailed && i === 3
                ? i !== 3
                : true;

            const isDisabledCondition =
              typeof disableTabCondition === 'function' ? disableTabCondition(i) : false;
            const isDisabled = isDisabledCondition || isTabChangeDisabled;

            const hasError =
              (isFieldVerificationFailed && i === 2) || (isBankVerificationFailed && i === 3);

            return (
              <li
                className={classList(
                  isActiveClass,
                  isTabValid && 'text-success',
                  hasError && 'text-danger',
                  isDisabled && 'disabled',
                )}
                key={i}
                data-index={i}
                onClick={isDisabled ? undefined : tabClickHandler}
              >
                {!isDisabled && isTabValid && canShowSuccessCheckbox && (
                  <i className="i-check text-success" />
                )}
                {hasError && <i className="i i-error text-danger" />}
                {typeof t === 'object' ? (
                  <span className="li--broad">
                    {t.title}
                    <div className="description large">{t.desc}</div>
                  </span>
                ) : (
                  t
                )}
                {i === activeTab && <i className="i i-chevron-right" data-index={i} />}
              </li>
            );
          })}
          {moreTabs}
        </ul>
        {isActivationFormFullView && (
          <div className="footer-img-container">
            <img src={sideBarBottomImage} />
          </div>
        )}
      </div>
    </aside>
  );
};
