import { useState } from 'react';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import codSettingsRoutes from 'merchant/views/MagicCheckout/CODSettings/routes';
import { COD_ENGINES } from 'merchant/views/MagicCheckout/CODSettings/constants';

const TabNavItem = ({ id, title, activeTab, setActiveTab, isCODEngineEnabled, codEngineType }) => {
  const handleClick = () => {
    setActiveTab(id);
  };

  if ((!isCODEngineEnabled || codEngineType !== COD_ENGINES.BASIC) && id === 'allowlist') {
    return null;
  }

  return (
    <li
      onClick={handleClick}
      className={activeTab === id ? 'active scrollable-tab-header' : 'scrollable-tab-header'}
    >
      {title}
    </li>
  );
};

const TabContent = ({ id, activeTab, children, className, isCODEngineEnabled, codEngineType }) => {
  if ((!isCODEngineEnabled || codEngineType !== COD_ENGINES.BASIC) && id === 'allowlist') {
    return null;
  }

  return activeTab === id ? <div className={`tabContent ${className}`}>{children}</div> : null;
};

const CODSettings = ({ isRcod, isCODEngineEnabled, codEngineType }) => {
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
                isCODEngineEnabled={isCODEngineEnabled}
                codEngineType={codEngineType}
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
                isCODEngineEnabled={isCODEngineEnabled}
                codEngineType={codEngineType}
              />
            ))}
          </content>
        </div>
      </div>
    </SuspenseWithLoader>
  );
};

export default CODSettings;
