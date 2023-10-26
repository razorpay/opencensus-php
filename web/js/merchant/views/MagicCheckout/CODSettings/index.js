import { useState } from 'react';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import codSettingsRoutes from 'merchant/views/MagicCheckout/CODSettings/routes';

const TabNavItem = ({ id, title, activeTab, setActiveTab }) => {
  const handleClick = () => {
    setActiveTab(id);
  };

  return (
    <li
      onClick={handleClick}
      className={activeTab === id ? 'active scrollable-tab-header' : 'scrollable-tab-header'}
    >
      {title}
    </li>
  );
};

const TabContent = ({ id, activeTab, children, className }) => {
  return activeTab === id ? <div className={`tabContent ${className}`}>{children}</div> : null;
};

const CODSettings = ({ isRcod }) => {
  const [activeTab, setActiveTab] = useState('cod-engine');

  return (
    <SuspenseWithLoader type="center">
      <div className="tabs">
        <div className="tabbed-container" data-testid="cod-settings-tabbed-container">
          <header>
            {codSettingsRoutes.map((item) => (
              <TabNavItem
                key={item.id}
                id={item.id}
                title={isRcod ? item.rcodTitle || item.title : item.title}
                activeTab={activeTab}
                setActiveTab={setActiveTab}
              />
            ))}
          </header>
          <content>
            {codSettingsRoutes.map((item) => (
              <TabContent
                key={item.id}
                id={item.id}
                activeTab={activeTab}
                className={item.className}
                children={item.component}
              />
            ))}
          </content>
        </div>
      </div>
    </SuspenseWithLoader>
  );
};

export default CODSettings;
