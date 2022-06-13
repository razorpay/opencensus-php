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

const MagicIntelligence = () => {
  const [activeTab, setActiveTab] = useState('delivery-tracking');

  return (
    <div className="tabs">
      <tabbed-container>
        <header>
          {magicIntelligenceRoutes.map((item) => (
            <TabNavItem
              key={item.id}
              id={item.id}
              title={item.title}
              activeTab={activeTab}
              setActiveTab={setActiveTab}
            />
          ))}
        </header>
        <content>
          {magicIntelligenceRoutes.map((item) => (
            <TabContent
              key={item.id}
              id={item.id}
              activeTab={activeTab}
              className={item.className}
              children={item.component}
            />
          ))}
        </content>
      </tabbed-container>
    </div>
  );
};

export default MagicIntelligence;
