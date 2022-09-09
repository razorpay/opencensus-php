const MainContent = (props) => {
  const { activeNav, render } = props;
  return <div className="tab-content col-sm-10 no-padding">{render(activeNav)}</div>;
};

export default MainContent;
