import { classList } from 'common/util';

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
export const ModalAsideNav = _ => {
  const {
    size,
    title,
    description,
    tabs,
    moreTabs,
    tabsValidity,
    tabClickHandler,
    activeTab,
    activeTabContdition,
  } = _;

  return (
    <aside>
      <side-title>{title}</side-title>
      {description}
      <ul>
        {tabs.map((t, i) => {
          let isTabValid = tabsValidity && tabsValidity[i];

          let isActiveClass = i == activeTab && 'active';
          if (typeof activeTabContdition !== 'undefined') {
            // Is defined and is true
            isActiveClass = activeTabContdition && isActiveClass;
          }

          return (
            <li
              class={classList(isActiveClass, isTabValid && 'text-success')}
              key={i}
              data-index={i}
              onClick={tabClickHandler}
            >
              {isTabValid && <i class={'i-check text-success'} />}
              {do {
                if (typeof t === 'object') {
                  <span class="li--broad">
                    {t.label}
                    <div class="description large">{t.desc}</div>
                  </span>;
                } else {
                  t;
                }
              }}
            </li>
          );
        })}
        {moreTabs}
      </ul>
    </aside>
  );
};
