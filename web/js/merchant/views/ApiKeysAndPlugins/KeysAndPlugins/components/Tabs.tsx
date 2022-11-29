import React, { createContext, useContext, useState } from 'react';

type TabId = string | number;

interface TabContextProps {
  activeTab: TabId;
  setActiveTab: React.Dispatch<TabId>;
}

const TabContext = createContext<TabContextProps>({ activeTab: '', setActiveTab: () => {} });

interface TabProps {
  id: TabId;
  children: React.ReactNode;
}

export const Tab = ({ id, children }: TabProps): JSX.Element => {
  const { activeTab, setActiveTab } = useContext(TabContext);

  return (
    <div
      className={`tab-new ${id === activeTab ? 'active' : ''}`}
      data-testid={id === activeTab ? 'active-tab' : 'tab'}
      onClick={() => setActiveTab(id)}
    >
      {children}
    </div>
  );
};

export interface TabSwitcherProps {
  defaultTab: TabId;
  onChange?: (id: TabId) => void;
  children: React.ReactNode;
}

export const TabSwitcher = ({ defaultTab, onChange, children }: TabSwitcherProps): JSX.Element => {
  const [activeTab, setActiveTab] = useState(defaultTab);
  return (
    <TabContext.Provider
      value={{
        activeTab,
        setActiveTab: (id) => {
          setActiveTab(id);
          onChange?.(id);
        },
      }}
    >
      <div className="tab-switcher" data-testid="tab-switcher">
        {children}
      </div>
    </TabContext.Provider>
  );
};
