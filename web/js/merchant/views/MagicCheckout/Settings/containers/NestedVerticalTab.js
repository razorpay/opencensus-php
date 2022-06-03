import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useState } from 'react';
import { updatePageView } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { TABS } from 'merchant/views/MagicCheckout/Settings/constants';

const NestedTabs = ({ settings }) => {
  const { platform } = settings;
  const [activeTab, setActiveTab] = useState(TABS[platform][0]);
  const { Component, label, tabHeading } = activeTab;
  return (
    <div className="magic-settings-tabs display-flex">
      <div className="tabs-container">
        {TABS[platform].map((item) => (
          <div
            className={`pointer padding-16 font-bold tabs-items ${
              label === item.label ? 'active' : ''
            }`}
            key={item.label}
            onClick={() => setActiveTab(item)}
          >
            {item.label}
          </div>
        ))}
      </div>
      <div className="tabs-content bg-white">
        {tabHeading ? (
          <div className="padding-16 font-20 font-bold tab-heading">{tabHeading}</div>
        ) : null}
        <div className={`tabs-component${!tabHeading ? ' tab-padding' : ''}`}>
          <div className="tabs-component-wrapper">
            <Component />
          </div>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updatePage: updatePageView,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(NestedTabs);
