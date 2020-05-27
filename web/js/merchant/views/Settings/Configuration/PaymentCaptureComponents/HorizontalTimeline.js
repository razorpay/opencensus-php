import { useState } from 'react';
const HorizontalTimeline = props => {
  const [showSummary, setShowSumary] = useState(true);
  const timelineItems = props.timelineItems;
  const showItem = (item, index) => {
    if (index < timelineItems.length - 1) {
      return (
        <div className="item_one" key={item.time}>
          <div className="title">{item.title}</div>
          <div className="line-container">
            <div className="circle" />
            <span className="line arrow-left" />
            <span className="line arrow-right" />
          </div>
          <div className="details">
            <div className="details_time">{item.time}</div>
            <div className="details_text">{item.description}</div>
          </div>
        </div>
      );
    } else {
      return (
        <div className="item_one" key={item.time}>
          <div className="title">{item.title}</div>
          <div className="line-container">
            <div className="circle" />
            <span
              className="line arrow-left"
              style={{ visibility: 'hidden' }}
            />
            <span
              className="line arrow-right"
              style={{ visibility: 'hidden' }}
            />
          </div>
          <div className="details">
            <div className="details_time" style={{ marginLeft: '-32%' }}>
              {item.time}
            </div>
          </div>
        </div>
      );
    }
  };
  return (
    <>
      <p
        className="horizontal-timeline-toggle"
        onClick={() => {
          setShowSumary(o => !o);
        }}
      >
        {showSummary ? (
          <span>
            Hide Summary <i className="i i-arrow-up" />
          </span>
        ) : (
          <span>
            Show Summary <i className="i i-arrow-down" />
          </span>
        )}
      </p>
      {showSummary && (
        <div className="horizontal-timeline">
          {timelineItems.map((item, index) => {
            return showItem(item, index);
          })}
        </div>
      )}
    </>
  );
};

export default HorizontalTimeline;
