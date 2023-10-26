import React from 'react';

interface MainContentProps {
  activeNav: string;
  render: (activeNav: string) => JSX.Element;
}

const MainContent: React.FC<MainContentProps> = ({ activeNav, render }) => {
  return <div className="tab-content col-sm-10 no-padding">{render(activeNav)}</div>;
};

export default MainContent;
