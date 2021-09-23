import { classList } from 'common/utils/rzp-utils';

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
    isPanVerifactionFailed,
    isBusinessDetailsTab,
  } = _;

  return (
    <aside>
      <side-title>{title}</side-title>
      {description}
      <ul>
        {tabs.map((t, i) => {
          const isTabValid = tabsValidity && tabsValidity[i];

          let isActiveClass = i == activeTab && 'active';
          if (typeof activeTabContdition !== 'undefined') {
            // Is defined and is true
            isActiveClass = activeTabContdition && isActiveClass;
          }
          const canShowSuccessCheckbox = isPanVerifactionFailed ? i !== 2 : true;

          const isDisabled =
            typeof disableTabCondition === 'function' ? disableTabCondition(i) : false;

          return (
            <li
              class={classList(
                isActiveClass,
                isTabValid && 'text-success',
                isPanVerifactionFailed && isBusinessDetailsTab && i === 2 && 'text-danger',
                isDisabled && 'disabled',
              )}
              key={i}
              data-index={i}
              onClick={isDisabled ? undefined : tabClickHandler}
            >
              {!isDisabled && isTabValid && canShowSuccessCheckbox && (
                <i className="i-check text-success" />
              )}
              {isPanVerifactionFailed && isBusinessDetailsTab && i === 2 && (
                <i className="i i-error text-danger" />
              )}
              {typeof t === 'object' ? (
                <span class="li--broad">
                  {t.title}
                  <div class="description large">{t.desc}</div>
                </span>
              ) : (
                t
              )}
              {i === activeTab && <i className="i i-chevron-right" />}
            </li>
          );
        })}
        {moreTabs}
      </ul>
    </aside>
  );
};
