import { useState } from 'react';
import magicIntelligenceRoutes from 'merchant/views/MagicCheckout/MagicIntelligenceRoutes';

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

const MagicIntelligence = ({ isRCOD }) => {
  const [activeTab, setActiveTab] = useState('delivery-tracking');

  return (
    <div className="tabs">
      <div className="tabbed-container" data-testid="magic-intelligence-tabbed-container">
        <header>
          {magicIntelligenceRoutes.map((item) => {
            if (isRCOD && !item.onRCOD) return null;

            return (
              <TabNavItem
                key={item.id}
                id={item.id}
                title={item.title}
                activeTab={activeTab}
                setActiveTab={setActiveTab}
              />
            );
          })}
        </header>
        <content>
          {magicIntelligenceRoutes.map((item) => {
            if (isRCOD && !item.onRCOD) return null;

            return (
              <TabContent
                key={item.id}
                id={item.id}
                activeTab={activeTab}
                className={item.className}
                children={item.component}
              />
            );
          })}
        </content>
      </div>
    </div>
  );
};

export default MagicIntelligence;
